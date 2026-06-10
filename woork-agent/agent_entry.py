from __future__ import annotations

import json
import sys


def _run_preflight() -> int:
    try:
        import socket  # noqa: F401
        import ssl  # noqa: F401

        print(json.dumps({"ok": True, "message": "Python networking modules loaded."}))
        return 0
    except Exception as exc:  # noqa: BLE001
        print(json.dumps({"ok": False, "error": str(exc)}))
        return 1


if __name__ == "__main__":
    if len(sys.argv) >= 2 and sys.argv[1] == "preflight":
        raise SystemExit(_run_preflight())

    from woork_agent.cli import main

    main()
