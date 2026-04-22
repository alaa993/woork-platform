# Woork AI Agent (OpenCV + YOLO)

خدمة منفصلة لتحويل الفيديو إلى أحداث رقمية دون حفظ الفيديو.
ترسل النتائج إلى API `POST /api/agent/ingest`.

## المتطلبات
- Python 3.10+
- OpenCV

## التشغيل
1) نسخ ملف الإعداد:
```
cp config.example.yml config.yml
```
2) (اختياري) تنزيل النموذج تلقائياً:
```
./scripts/setup_model.sh
```
3) عدّل `config.yml` بالقيم الصحيحة (RTSP, IDs, token).
4) ثبّت المتطلبات:
```
pip install -r requirements.txt
```
5) شغّل الوكيل:
```
python agent.py --config config.yml
```

## النموذج (YOLOv8 ONNX)
- ضع النموذج بصيغة ONNX في مسار `detector.model_path`.
- ضع ملف الأصناف في `detector.classes_path` (سطر لكل اسم صنف).
- تأكد أن الأصناف تحتوي على `person` و `cell phone` أو عدّل `person_class_names` و `phone_class_names`.

## التتبع
- `tracker.type` يدعم: `bytetrack` أو `deepsort` أو `simple`.
- **ByteTrack** يعتمد على مكتبة `supervision`.
- **DeepSORT** يعتمد على مكتبة `deep-sort-realtime`.

## تتبع ديناميكي (بدون مناطق)
- إذا تركت `zone` فارغة لكل موظف، سيتم تعيين تتبع ديناميكي للأشخاص داخل الكاميرا.
- يتم توزيع الأشخاص المكتشفين على الموظفين بالترتيب.

## معايرة الحركة (الحساسية)
تحت `motion` لكل كاميرا:
- `min_motion_area`: الحد الأدنى للحركة بالبكسل.
- `motion_ratio_threshold`: نسبة الحركة المطلوبة بالنسبة لمساحة المنطقة.
- `bg_history` / `bg_var_threshold` / `bg_detect_shadows`: إعدادات خلفية الحركة.
- `blur_ksize`, `erode_iter`, `dilate_iter`: تنعيم وتقليل الضجيج.

## ملاحظات
- لا يتم حفظ الفيديو.
- الأحداث تتضمن: `work_active`, `idle`, `phone`, `away`.

## التوافق مع Woork
تأكد أن `results_token` يطابق `WOORK_RESULTS_TOKEN` في Laravel.

