from __future__ import annotations

from dataclasses import dataclass
from typing import Dict, List, Optional, Tuple

import numpy as np


@dataclass
class TrackerResult:
    tracks: Dict[int, Tuple[int, int, int, int]]


class TrackerError(RuntimeError):
    pass


class BaseTracker:
    def update(self, detections: List[Tuple[int, int, int, int, float]], frame=None) -> TrackerResult:
        raise NotImplementedError


class SimpleTracker(BaseTracker):
    def __init__(self, iou_threshold: float, max_missed: int):
        self.iou_threshold = iou_threshold
        self.max_missed = max_missed
        self.tracks: Dict[int, dict] = {}
        self.next_id = 1

    def update(self, detections: List[Tuple[int, int, int, int, float]], frame=None) -> TrackerResult:
        assigned = set()
        track_ids = list(self.tracks.keys())
        det_indices = list(range(len(detections)))

        matches = []
        for tid in track_ids:
            tbox = self.tracks[tid]["bbox"]
            best_iou = 0.0
            best_j = None
            for j in det_indices:
                iou = self._iou(tbox, detections[j][:4])
                if iou > best_iou:
                    best_iou = iou
                    best_j = j
            if best_j is not None and best_iou >= self.iou_threshold:
                matches.append((tid, best_j, best_iou))

        matches.sort(key=lambda x: x[2], reverse=True)
        used_tracks = set()
        used_dets = set()
        for tid, j, _ in matches:
            if tid in used_tracks or j in used_dets:
                continue
            used_tracks.add(tid)
            used_dets.add(j)
            self.tracks[tid]["bbox"] = detections[j][:4]
            self.tracks[tid]["missed"] = 0

        for tid in track_ids:
            if tid not in used_tracks:
                self.tracks[tid]["missed"] += 1

        for j in det_indices:
            if j not in used_dets:
                self.tracks[self.next_id] = {"bbox": detections[j][:4], "missed": 0}
                self.next_id += 1

        to_delete = [tid for tid, tr in self.tracks.items() if tr["missed"] > self.max_missed]
        for tid in to_delete:
            del self.tracks[tid]

        return TrackerResult({tid: tr["bbox"] for tid, tr in self.tracks.items()})

    @staticmethod
    def _iou(box_a: Tuple[int, int, int, int], box_b: Tuple[int, int, int, int]) -> float:
        ax, ay, aw, ah = box_a
        bx, by, bw, bh = box_b
        ax2, ay2 = ax + aw, ay + ah
        bx2, by2 = bx + bw, by + bh
        inter_x1 = max(ax, bx)
        inter_y1 = max(ay, by)
        inter_x2 = min(ax2, bx2)
        inter_y2 = min(ay2, by2)
        if inter_x2 <= inter_x1 or inter_y2 <= inter_y1:
            return 0.0
        inter_area = (inter_x2 - inter_x1) * (inter_y2 - inter_y1)
        area_a = aw * ah
        area_b = bw * bh
        return inter_area / max(area_a + area_b - inter_area, 1)


class DeepSortTracker(BaseTracker):
    def __init__(self, max_age: int, n_init: int, max_iou_distance: float):
        try:
            from deep_sort_realtime.deepsort_tracker import DeepSort
        except Exception as exc:
            raise TrackerError("deep-sort-realtime is not installed") from exc
        self.tracker = DeepSort(
            max_age=max_age,
            n_init=n_init,
            max_iou_distance=max_iou_distance,
        )

    def update(self, detections: List[Tuple[int, int, int, int, float]], frame=None) -> TrackerResult:
        raw_dets = [([x, y, w, h], conf, "person") for x, y, w, h, conf in detections]
        tracks = self.tracker.update_tracks(raw_dets, frame=frame)
        results = {}
        for tr in tracks:
            if not tr.is_confirmed():
                continue
            x1, y1, x2, y2 = tr.to_ltrb()
            results[int(tr.track_id)] = (int(x1), int(y1), int(x2 - x1), int(y2 - y1))
        return TrackerResult(results)


class ByteTrackTracker(BaseTracker):
    def __init__(self, track_thresh: float, track_buffer: int, match_thresh: float):
        try:
            import supervision as sv
        except Exception as exc:
            raise TrackerError("supervision is not installed") from exc
        self.sv = sv
        self.tracker = sv.ByteTrack(
            track_thresh=track_thresh,
            track_buffer=track_buffer,
            match_thresh=match_thresh,
        )

    def update(self, detections: List[Tuple[int, int, int, int, float]], frame=None) -> TrackerResult:
        if not detections:
            return TrackerResult({})
        xyxy = []
        confidences = []
        for x, y, w, h, conf in detections:
            xyxy.append([x, y, x + w, y + h])
            confidences.append(conf)
        dets = self.sv.Detections(
            xyxy=np.array(xyxy),
            confidence=np.array(confidences),
            class_id=np.zeros(len(confidences), dtype=int),
        )
        tracked = self.tracker.update_with_detections(dets)
        results = {}
        for bbox, tid in zip(tracked.xyxy, tracked.tracker_id):
            x1, y1, x2, y2 = bbox
            results[int(tid)] = (int(x1), int(y1), int(x2 - x1), int(y2 - y1))
        return TrackerResult(results)


def build_tracker(tracker_type: str, tracker_cfg: dict) -> BaseTracker:
    if tracker_type == "deepsort":
        return DeepSortTracker(
            max_age=int(tracker_cfg.get("max_age", 30)),
            n_init=int(tracker_cfg.get("n_init", 3)),
            max_iou_distance=float(tracker_cfg.get("max_iou_distance", 0.7)),
        )
    if tracker_type == "bytetrack":
        return ByteTrackTracker(
            track_thresh=float(tracker_cfg.get("track_thresh", 0.5)),
            track_buffer=int(tracker_cfg.get("track_buffer", 30)),
            match_thresh=float(tracker_cfg.get("match_thresh", 0.8)),
        )
    return SimpleTracker(
        iou_threshold=float(tracker_cfg.get("iou_threshold", 0.3)),
        max_missed=int(tracker_cfg.get("max_missed", 15)),
    )
