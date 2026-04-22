#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MODEL_DIR="$ROOT_DIR/models"
mkdir -p "$MODEL_DIR"

MODEL_URL="https://github.com/ultralytics/assets/releases/download/v0.0.0/yolov8n.onnx"
CLASSES_URL="https://raw.githubusercontent.com/ultralytics/ultralytics/main/ultralytics/cfg/datasets/coco.names"

MODEL_PATH="$MODEL_DIR/yolov8n.onnx"
CLASSES_PATH="$MODEL_DIR/coco.names"

if [[ ! -f "$MODEL_PATH" ]]; then
  echo "Downloading YOLOv8n ONNX..."
  curl -L "$MODEL_URL" -o "$MODEL_PATH"
fi

if [[ ! -f "$CLASSES_PATH" ]]; then
  echo "Downloading COCO class names..."
  curl -L "$CLASSES_URL" -o "$CLASSES_PATH"
fi

echo "Model: $MODEL_PATH"
echo "Classes: $CLASSES_PATH"
