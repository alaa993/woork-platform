from __future__ import annotations

import json
from dataclasses import asdict
from pathlib import Path
from typing import Any, Optional

try:
    import sqlite3
except Exception:  # noqa: BLE001
    sqlite3 = None

from .models import CameraConfig, EventPayload, RuntimeState


class LocalStateStore:
    def __init__(self, path: str) -> None:
        self.path = Path(path)
        self.path.parent.mkdir(parents=True, exist_ok=True)
        self.mode = "json"
        self.conn = None
        self.json_path = self.path.with_suffix(".json")

        if sqlite3 is not None:
            try:
                self.conn = sqlite3.connect(self.path)
                self.conn.row_factory = sqlite3.Row
                self.mode = "sqlite"
                self._init_schema()
            except Exception:  # noqa: BLE001
                self.conn = None
                self.mode = "json"

        if self.mode == "json":
            self._ensure_json_state()

    def _init_schema(self) -> None:
        if self.conn is None:
            return
        self.conn.executescript(
            """
            CREATE TABLE IF NOT EXISTS kv_store (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL
            );
            CREATE TABLE IF NOT EXISTS queued_events (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                payload TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            """
        )
        self.conn.commit()

    def _ensure_json_state(self) -> None:
        if self.json_path.exists():
            return
        self._write_json_state(
            {
                "kv": {},
                "events": [],
                "next_event_id": 1,
            }
        )

    def _read_json_state(self) -> dict[str, Any]:
        self._ensure_json_state()
        data = json.loads(self.json_path.read_text(encoding="utf-8"))
        if not isinstance(data, dict):
            data = {}
        data.setdefault("kv", {})
        data.setdefault("events", [])
        data.setdefault("next_event_id", 1)
        return data

    def _write_json_state(self, payload: dict[str, Any]) -> None:
        self.json_path.write_text(json.dumps(payload, indent=2), encoding="utf-8")

    def load_runtime_state(self) -> RuntimeState:
        token = self._get("token")
        agent_device_id = self._get("agent_device_id")
        organization_id = self._get("organization_id")
        cameras_raw = self._get("cameras")

        cameras: list[CameraConfig] = []
        if cameras_raw:
            for item in json.loads(cameras_raw):
                cameras.append(CameraConfig(**item))

        return RuntimeState(
            token=token,
            agent_device_id=int(agent_device_id) if agent_device_id else None,
            organization_id=int(organization_id) if organization_id else None,
            cameras=cameras,
        )

    def persist_runtime_state(self, state: RuntimeState) -> None:
        self._set("token", state.token)
        self._set("agent_device_id", str(state.agent_device_id) if state.agent_device_id else None)
        self._set("organization_id", str(state.organization_id) if state.organization_id else None)
        self._set("cameras", json.dumps([asdict(camera) for camera in state.cameras]))

    def enqueue_event(self, event: EventPayload) -> None:
        payload = json.dumps(asdict(event))

        if self.mode == "sqlite" and self.conn is not None:
            self.conn.execute(
                "INSERT INTO queued_events (payload) VALUES (?)",
                (payload,),
            )
            self.conn.commit()
            return

        state = self._read_json_state()
        event_id = int(state.get("next_event_id", 1))
        state["events"].append({"id": event_id, "payload": json.loads(payload)})
        state["next_event_id"] = event_id + 1
        self._write_json_state(state)

    def list_queued_events(self, limit: int = 100) -> list[tuple[int, dict[str, Any]]]:
        if self.mode == "sqlite" and self.conn is not None:
            rows = self.conn.execute(
                "SELECT id, payload FROM queued_events ORDER BY id ASC LIMIT ?",
                (limit,),
            ).fetchall()
            return [(row["id"], json.loads(row["payload"])) for row in rows]

        state = self._read_json_state()
        rows = state.get("events", [])[:limit]
        return [(int(row["id"]), dict(row["payload"])) for row in rows]

    def delete_queued_events(self, ids: list[int]) -> None:
        if not ids:
            return

        if self.mode == "sqlite" and self.conn is not None:
            placeholders = ",".join("?" for _ in ids)
            self.conn.execute(f"DELETE FROM queued_events WHERE id IN ({placeholders})", ids)
            self.conn.commit()
            return

        state = self._read_json_state()
        remove_ids = {int(item) for item in ids}
        state["events"] = [row for row in state.get("events", []) if int(row["id"]) not in remove_ids]
        self._write_json_state(state)

    def queued_events_count(self) -> int:
        if self.mode == "sqlite" and self.conn is not None:
            row = self.conn.execute("SELECT COUNT(*) AS count FROM queued_events").fetchone()
            return int(row["count"]) if row else 0

        state = self._read_json_state()
        return len(state.get("events", []))

    def trim_oldest_queued_events(self, keep_latest: int) -> int:
        current = self.queued_events_count()
        excess = max(0, current - keep_latest)
        if excess == 0:
            return 0

        if self.mode == "sqlite" and self.conn is not None:
            rows = self.conn.execute(
                "SELECT id FROM queued_events ORDER BY id ASC LIMIT ?",
                (excess,),
            ).fetchall()
            ids = [int(row["id"]) for row in rows]
            self.delete_queued_events(ids)
            return len(ids)

        state = self._read_json_state()
        state["events"] = state.get("events", [])[excess:]
        self._write_json_state(state)
        return excess

    def _get(self, key: str) -> Optional[str]:
        if self.mode == "sqlite" and self.conn is not None:
            row = self.conn.execute("SELECT value FROM kv_store WHERE key = ?", (key,)).fetchone()
            return None if row is None else row["value"]

        state = self._read_json_state()
        value = state.get("kv", {}).get(key)
        return None if value is None else str(value)

    def _set(self, key: str, value: Optional[str]) -> None:
        if self.mode == "sqlite" and self.conn is not None:
            if value is None:
                self.conn.execute("DELETE FROM kv_store WHERE key = ?", (key,))
            else:
                self.conn.execute(
                    """
                    INSERT INTO kv_store (key, value) VALUES (?, ?)
                    ON CONFLICT(key) DO UPDATE SET value = excluded.value
                    """,
                    (key, value),
                )
            self.conn.commit()
            return

        state = self._read_json_state()
        if value is None:
            state["kv"].pop(key, None)
        else:
            state["kv"][key] = value
        self._write_json_state(state)
