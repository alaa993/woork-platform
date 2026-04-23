from __future__ import annotations

import json
import os
import subprocess
import sys
import threading
import time
import uuid
from pathlib import Path
from tkinter import BOTH, END, LEFT, RIGHT, Button, Entry, Frame, Label, StringVar, Text, Tk, messagebox
from tkinter.ttk import Notebook
from typing import Optional

from .diagnostics import run_doctor
from .runtime import AgentRuntime, setup_file_logging
from .settings import load_settings


DEFAULT_CONFIG = {
    "cloud_base_url": "https://woork.site",
    "device_name": "Branch PC",
    "device_uuid": f"woork-{uuid.uuid4()}",
    "os": "windows",
    "version": "1.0.0",
    "state_path": "./state/agent-state.sqlite",
    "models_dir": "./models",
    "sync_interval_seconds": 60,
    "heartbeat_interval_seconds": 30,
    "runtime_loop_interval_seconds": 1.0,
    "max_camera_workers": 8,
    "max_queued_events": 5000,
    "flush_batch_size": 100,
    "request_timeout_seconds": 20,
    "request_retries": 3,
    "retry_backoff_seconds": 2.0,
}


class WoorkControlApp:
    def __init__(self, config_path: str) -> None:
        self.config_path = Path(config_path)
        self.config_path.parent.mkdir(parents=True, exist_ok=True)
        self._ensure_config()

        self.runtime_process: Optional[subprocess.Popen] = None

        self.root = Tk()
        self.root.title("Woork Agent")
        self.root.geometry("760x560")
        self.root.minsize(680, 480)

        self.cloud_url = StringVar()
        self.device_name = StringVar()
        self.device_uuid = StringVar()
        self.pairing_token = StringVar()
        self.server_status = StringVar(value="Unknown")
        self.agent_status = StringVar(value="Stopped")
        self.camera_status = StringVar(value="No cameras synced")

        self._load_config_to_vars()
        self._build_ui()
        self._refresh_status_loop()

    def run(self) -> None:
        self.root.mainloop()

    def _ensure_config(self) -> None:
        if self.config_path.exists():
            data = json.loads(self.config_path.read_text(encoding="utf-8"))
            if data.get("device_uuid") in {None, "", "replace-with-real-uuid"}:
                data["device_uuid"] = f"woork-{uuid.uuid4()}"
                self.config_path.write_text(json.dumps(data, indent=2), encoding="utf-8")
            return

        self.config_path.write_text(json.dumps(DEFAULT_CONFIG, indent=2), encoding="utf-8")

    def _load_config_to_vars(self) -> None:
        data = json.loads(self.config_path.read_text(encoding="utf-8"))
        self.cloud_url.set(data.get("cloud_base_url", DEFAULT_CONFIG["cloud_base_url"]))
        self.device_name.set(data.get("device_name", DEFAULT_CONFIG["device_name"]))
        self.device_uuid.set(data.get("device_uuid", DEFAULT_CONFIG["device_uuid"]))

    def _save_config(self) -> None:
        data = json.loads(self.config_path.read_text(encoding="utf-8"))
        data["cloud_base_url"] = self.cloud_url.get().strip().rstrip("/")
        data["device_name"] = self.device_name.get().strip() or "Branch PC"
        data["device_uuid"] = self.device_uuid.get().strip() or "replace-with-real-uuid"
        self.config_path.write_text(json.dumps(data, indent=2), encoding="utf-8")
        self._log("Configuration saved.")

    def _build_ui(self) -> None:
        notebook = Notebook(self.root)
        notebook.pack(fill=BOTH, expand=True, padx=12, pady=12)

        setup_tab = Frame(notebook)
        status_tab = Frame(notebook)
        logs_tab = Frame(notebook)

        notebook.add(setup_tab, text="Setup")
        notebook.add(status_tab, text="Status")
        notebook.add(logs_tab, text="Logs")

        self._build_setup_tab(setup_tab)
        self._build_status_tab(status_tab)
        self._build_logs_tab(logs_tab)

    def _build_setup_tab(self, parent: Frame) -> None:
        rows = [
            ("Cloud URL", self.cloud_url),
            ("Device name", self.device_name),
            ("Device UUID", self.device_uuid),
            ("Pairing token", self.pairing_token),
        ]

        for index, (label, var) in enumerate(rows):
            Label(parent, text=label, anchor="w").grid(row=index, column=0, sticky="we", padx=12, pady=8)
            Entry(parent, textvariable=var, width=70).grid(row=index, column=1, sticky="we", padx=12, pady=8)

        parent.grid_columnconfigure(1, weight=1)

        actions = Frame(parent)
        actions.grid(row=len(rows), column=0, columnspan=2, sticky="we", padx=12, pady=16)

        Button(actions, text="Save Settings", command=self._on_save).pack(side=LEFT, padx=4)
        Button(actions, text="Pair Device", command=self._on_pair).pack(side=LEFT, padx=4)
        Button(actions, text="Run Doctor", command=self._on_doctor).pack(side=LEFT, padx=4)

        Label(
            parent,
            text="After pairing, add cameras in Woork Cloud, then start the agent.",
            anchor="w",
        ).grid(row=len(rows) + 1, column=0, columnspan=2, sticky="we", padx=12, pady=8)

    def _build_status_tab(self, parent: Frame) -> None:
        labels = [
            ("Server", self.server_status),
            ("Agent", self.agent_status),
            ("Cameras", self.camera_status),
        ]

        for index, (label, var) in enumerate(labels):
            Label(parent, text=label, font=("Segoe UI", 11, "bold")).grid(row=index, column=0, sticky="w", padx=12, pady=10)
            Label(parent, textvariable=var).grid(row=index, column=1, sticky="w", padx=12, pady=10)

        actions = Frame(parent)
        actions.grid(row=len(labels), column=0, columnspan=2, sticky="we", padx=12, pady=20)

        Button(actions, text="Start Agent", command=self._on_start).pack(side=LEFT, padx=4)
        Button(actions, text="Stop Agent", command=self._on_stop).pack(side=LEFT, padx=4)
        Button(actions, text="Sync Now", command=self._on_sync).pack(side=LEFT, padx=4)
        Button(actions, text="Send Heartbeat", command=self._on_heartbeat).pack(side=LEFT, padx=4)

    def _build_logs_tab(self, parent: Frame) -> None:
        self.log_text = Text(parent, height=20)
        self.log_text.pack(fill=BOTH, expand=True, padx=12, pady=12)

        actions = Frame(parent)
        actions.pack(fill=BOTH, padx=12, pady=4)
        Button(actions, text="Clear", command=lambda: self.log_text.delete("1.0", END)).pack(side=RIGHT)

    def _on_save(self) -> None:
        try:
            self._save_config()
            messagebox.showinfo("Woork Agent", "Settings saved.")
        except Exception as exc:  # noqa: BLE001
            messagebox.showerror("Woork Agent", str(exc))

    def _on_pair(self) -> None:
        self._run_background("Pairing device", self._pair_device)

    def _on_doctor(self) -> None:
        self._run_background("Running doctor", self._run_doctor)

    def _on_sync(self) -> None:
        self._run_background("Syncing cameras", lambda: self._runtime().sync_config())

    def _on_heartbeat(self) -> None:
        self._run_background("Sending heartbeat", lambda: self._runtime().heartbeat_once())

    def _on_start(self) -> None:
        if self._is_windows_service_installed():
            self._run_background("Starting Windows service", self._start_windows_service)
            return

        if self.runtime_process and self.runtime_process.poll() is None:
            self._log("Agent is already running.")
            return

        self._save_config()
        log_path = str(self.config_path.parent / "logs" / "agent.log")
        self.runtime_process = subprocess.Popen(
            [
                sys.executable,
                "-m",
                "woork_agent.cli",
                "run",
                "--config",
                str(self.config_path),
            ],
            stdout=open(log_path, "a", encoding="utf-8"),
            stderr=subprocess.STDOUT,
        )
        self.agent_status.set("Running")
        self._log(f"Agent started. Logs: {log_path}")

    def _on_stop(self) -> None:
        if self._is_windows_service_installed():
            self._run_background("Stopping Windows service", self._stop_windows_service)
            return

        if self.runtime_process and self.runtime_process.poll() is None:
            self.runtime_process.terminate()
            self.runtime_process = None
            self.agent_status.set("Stopped")
            self._log("Agent stopped.")
        else:
            self._log("Agent is not running from this control app.")

    def _runtime(self) -> AgentRuntime:
        self._save_config()
        log_path = str(self.config_path.parent / "logs" / "control.log")
        setup_file_logging(log_path)
        return AgentRuntime(str(self.config_path))

    def _pair_device(self) -> None:
        token = self.pairing_token.get().strip()
        if not token:
            raise RuntimeError("Pairing token is required.")
        runtime = self._runtime()
        runtime.pair(token)
        self._log("Device paired successfully.")
        self._refresh_status()

    def _run_doctor(self) -> None:
        self._save_config()
        result = run_doctor(load_settings(str(self.config_path)))
        self._log(json.dumps(result, indent=2))
        if result.get("ok"):
            messagebox.showinfo("Woork Agent", "Doctor passed.")
        else:
            messagebox.showwarning("Woork Agent", "Doctor found issues. See Logs tab.")

    def _run_background(self, title: str, fn) -> None:
        self._log(f"{title}...")

        def worker() -> None:
            try:
                fn()
                self._log(f"{title} completed.")
            except Exception as exc:  # noqa: BLE001
                self._log(f"{title} failed: {exc}")
                messagebox.showerror("Woork Agent", str(exc))

        threading.Thread(target=worker, daemon=True).start()

    def _refresh_status_loop(self) -> None:
        self._refresh_status()
        self.root.after(5000, self._refresh_status_loop)

    def _refresh_status(self) -> None:
        try:
            runtime = AgentRuntime(str(self.config_path))
            state = runtime.state
            self.server_status.set("Paired" if state.token else "Not paired")
            service_state = self._windows_service_state()
            if service_state:
                self.agent_status.set(f"Service {service_state}")
            elif self.runtime_process and self.runtime_process.poll() is None:
                self.agent_status.set("Running")
            elif not state.token:
                self.agent_status.set("Waiting for pairing")
            else:
                self.agent_status.set("Stopped")

            if state.cameras:
                statuses = runtime.camera_runtime.statuses()
                online = sum(1 for item in statuses if item.get("stream_status") == "online")
                self.camera_status.set(f"{online}/{len(state.cameras)} online")
            else:
                self.camera_status.set("No cameras synced")
        except Exception as exc:  # noqa: BLE001
            self.server_status.set("Config error")
            self._log(f"Status refresh error: {exc}")

    def _log(self, message: str) -> None:
        timestamp = time.strftime("%H:%M:%S")
        self.log_text.insert(END, f"[{timestamp}] {message}\n")
        self.log_text.see(END)

    def _is_windows_service_installed(self) -> bool:
        return bool(self._windows_service_state())

    def _windows_service_state(self) -> Optional[str]:
        if os.name != "nt":
            return None

        try:
            result = subprocess.run(
                ["sc", "query", "woork-agent"],
                capture_output=True,
                text=True,
                timeout=5,
                check=False,
            )
        except Exception:
            return None

        if result.returncode != 0:
            return None

        output = result.stdout.upper()
        if "RUNNING" in output:
            return "Running"
        if "STOPPED" in output:
            return "Stopped"
        if "START_PENDING" in output:
            return "Starting"
        if "STOP_PENDING" in output:
            return "Stopping"

        return "Installed"

    def _start_windows_service(self) -> None:
        self._save_config()
        result = subprocess.run(["sc", "start", "woork-agent"], capture_output=True, text=True, check=False)
        if result.returncode not in {0, 1056}:
            raise RuntimeError(result.stderr or result.stdout or "Failed to start Woork Agent service")
        self._log(result.stdout.strip() or "Woork Agent service started.")
        self._refresh_status()

    def _stop_windows_service(self) -> None:
        result = subprocess.run(["sc", "stop", "woork-agent"], capture_output=True, text=True, check=False)
        if result.returncode not in {0, 1062}:
            raise RuntimeError(result.stderr or result.stdout or "Failed to stop Woork Agent service")
        self._log(result.stdout.strip() or "Woork Agent service stopped.")
        self._refresh_status()


def main() -> None:
    config_path = sys.argv[1] if len(sys.argv) > 1 else "config.json"
    WoorkControlApp(config_path).run()


if __name__ == "__main__":
    main()
