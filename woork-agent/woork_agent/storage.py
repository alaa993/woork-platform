from __future__ import annotations

import json
import sqlite3
from dataclasses import asdict
from pathlib import Path
from typing import Any

from .models import CameraConfig, EventPayload, RuntimeState


class LocalStateStore:
    def __init__(self, path: str) -> None:
        self.path = Path(path)
        self.path.parent.mkdir(parents=True, exist_ok=True)
        self.conn = sqlite3.connect(self.path)
        self.conn.row_factory = sqlite3.Row
        self._init_schema()

    def _init_schema(self) -> None:
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
        self.conn.execute(
            "INSERT INTO queued_events (payload) VALUES (?)",
            (json.dumps(asdict(event)),),
        )
        self.conn.commit()

    def list_queued_events(self, limit: int = 100) -> list[tuple[int, dict[str, Any]]]:
        rows = self.conn.execute(
            "SELECT id, payload FROM queued_events ORDER BY id ASC LIMIT ?",
            (limit,),
        ).fetchall()
        return [(row["id"], json.loads(row["payload"])) for row in rows]

    def delete_queued_events(self, ids: list[int]) -> None:
        if not ids:
            return
        placeholders = ",".join("?" for _ in ids)
        self.conn.execute(f"DELETE FROM queued_events WHERE id IN ({placeholders})", ids)
        self.conn.commit()

    def queued_events_count(self) -> int:
        row = self.conn.execute("SELECT COUNT(*) AS count FROM queued_events").fetchone()
        return int(row["count"]) if row else 0

    def trim_oldest_queued_events(self, keep_latest: int) -> int:
        current = self.queued_events_count()
        excess = max(0, current - keep_latest)
        if excess == 0:
            return 0

        rows = self.conn.execute(
            "SELECT id FROM queued_events ORDER BY id ASC LIMIT ?",
            (excess,),
        ).fetchall()
        ids = [int(row["id"]) for row in rows]
        self.delete_queued_events(ids)
        return len(ids)

    def _get(self, key: str) -> str | None:
        row = self.conn.execute("SELECT value FROM kv_store WHERE key = ?", (key,)).fetchone()
        return None if row is None else row["value"]

    def _set(self, key: str, value: str | None) -> None:
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
