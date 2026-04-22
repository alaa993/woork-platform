# Agent ingestion & daily summaries

## 1. إرسال الأحداث من الوكيل

تستقبل المنصة `POST /api/agent/ingest` (راجع [`app/Http/Controllers/ApiAgentController.php`](../app/Http/Controllers/ApiAgentController.php)) مع:

- رأس `Authorization: Bearer {{WOORK_RESULTS_TOKEN}}`.
- JSON body يعرض `organization_id`, `employee_id`, `room_id`، و`events` يحتوي كل حدث على `type`, `started_at`, `ended_at`.

مثال (Postman مُحضر في `docs/Woork.postman_collection.json`):

```json
{
  "organization_id": 1,
  "employee_id": 1,
  "room_id": 1,
  "events": [
    { "type": "work_active", "started_at": "2025-10-29T09:00:00Z", "ended_at": "2025-10-29T10:00:00Z" }
  ]
}
```

كل حدث يُترجم إلى سجل في جدول `events` (بمدة `duration_seconds` محسوبة). الوكيل مسؤول عن تنفيذ تحليل الفيديو داخل الذاكرة وتحويل السلوكيات إلى هذه الأحداث قبل الإرسال.

## 2. توليد `DailySummary`

- الخدمة `App\Services\DailySummaryGenerator` تجمع الأحداث لليوم (أو التاريخ المطلوب) وتحوّلها إلى `daily_summaries`.
- Job جديد `GenerateDailySummariesJob` يستدعي الخدمة ويُجدول في `app/Console/Kernel.php` ليعمل كل ساعة، فتصبح بيانات `DailySummary` جاهزة لكل منظمة حتى بدون طلب يدوي.
- نفس الخدمة تستدعى عند `GET /api/reports/daily` (المحمية بـ Sanctum) لتحديث اليوم قبل عرض النتائج، ثم تُعيد الـJSON من `daily_summaries`.

## 3. التحقق والاختبار

- بعد إرسال الأحداث بواسطة الوكيل، يمكن عمل استعلام إلى `/api/reports/daily?date=YYYY-MM-DD` للحصول على اللقطة الرقمية لكل موظف.
- تأكد من أن `WOORK_RESULTS_TOKEN` في `.env` يطابق ما يُستخدم في الوكيل.
- راقب الجدولة عبر `php artisan schedule:work` و`php artisan queue:work` في البيئة الإنتاجية لاتمام التجميع والتنبيهات.

## 3. تنبيهات ذكية مبنية على السياسات

- `GenerateAlertsJob` يستخدم `DailySummaryGenerator` نفسه للتأكد من أن التنبيهات تعتمد على بيانات حديثة، ثم يقرأ القيم من `policy->thresholds` (أو الافتراضيات في `config/woork.php`) قبل إنشاء التنبيه.  
- يمكن لكل منظمة تهيئة العتبات التالية: `long_idle_minutes`, `phone_max_minutes`, `leave_max_minutes` وغيرها (أضفها في JSON عمود `thresholds`).  
- يُمكن أيضًا تحديد القنوات من خلال `policy->visibility['alert_channels']` (مثلاً `["in_app","email","slack"]`)؛ حالياً يُخزن `Alert` داخل التطبيق، بينما القنوات الأخرى تُسجل في السجل (log) كنموذج لقنوات لاحقة.  
- يخلق `AlertDispatcher` سجلًا ذا `kind`, `level`, `message`, و`rules` موثقة لكل تجاوز، وتتكرر هذه العملية كل ساعة جنبًا إلى جنب مع `GenerateDailySummariesJob`.  
