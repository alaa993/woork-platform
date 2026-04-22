from __future__ import annotations

import argparse
import json

from .diagnostics import print_json, run_benchmark, run_doctor
from .model_registry import inspect_models
from .runtime import AgentRuntime, setup_logging


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Woork Edge Agent")
    parser.add_argument("--verbose", action="store_true")

    subparsers = parser.add_subparsers(dest="command", required=True)

    pair = subparsers.add_parser("pair", help="Pair this device with Woork Cloud")
    pair.add_argument("--config", required=True)
    pair.add_argument("--pairing-token", required=True)

    sync = subparsers.add_parser("sync", help="Fetch latest camera configuration")
    sync.add_argument("--config", required=True)

    heartbeat = subparsers.add_parser("heartbeat", help="Send one heartbeat")
    heartbeat.add_argument("--config", required=True)

    enqueue = subparsers.add_parser("queue-sample", help="Queue a sample event")
    enqueue.add_argument("--config", required=True)
    enqueue.add_argument("--camera-id", required=True, type=int)
    enqueue.add_argument("--employee-id", required=True, type=int)
    enqueue.add_argument("--room-id", required=True, type=int)
    enqueue.add_argument("--type", required=True)

    flush = subparsers.add_parser("flush", help="Upload queued events")
    flush.add_argument("--config", required=True)

    run = subparsers.add_parser("run", help="Run sync + heartbeat loop forever")
    run.add_argument("--config", required=True)

    inspect_cmd = subparsers.add_parser("inspect-models", help="Inspect local detector model bundles")
    inspect_cmd.add_argument("--config", required=True)

    doctor_cmd = subparsers.add_parser("doctor", help="Run local readiness checks for this device")
    doctor_cmd.add_argument("--config", required=True)

    benchmark_cmd = subparsers.add_parser("benchmark", help="Run a synthetic runtime benchmark")
    benchmark_cmd.add_argument("--config", required=True)
    benchmark_cmd.add_argument("--seconds", type=float, default=5.0)
    benchmark_cmd.add_argument("--width", type=int, default=960)
    benchmark_cmd.add_argument("--height", type=int, default=540)

    return parser


def main() -> None:
    parser = build_parser()
    args = parser.parse_args()
    setup_logging(args.verbose)

    runtime = AgentRuntime(args.config)

    if args.command == "pair":
        runtime.pair(args.pairing_token)
    elif args.command == "sync":
        runtime.sync_config()
    elif args.command == "heartbeat":
        runtime.heartbeat_once()
    elif args.command == "queue-sample":
        runtime.queue_sample_event(args.camera_id, args.employee_id, args.room_id, args.type)
    elif args.command == "flush":
        runtime.flush_events()
    elif args.command == "run":
        runtime.run_forever()
    elif args.command == "inspect-models":
        print(json.dumps(inspect_models(runtime.settings.models_dir), indent=2))
    elif args.command == "doctor":
        print_json(run_doctor(runtime.settings))
    elif args.command == "benchmark":
        print_json(
            run_benchmark(
                runtime.settings,
                seconds=args.seconds,
                width=args.width,
                height=args.height,
            )
        )


if __name__ == "__main__":
    main()
