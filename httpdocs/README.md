# Woork SaaS v1 (Single Package)

All-in-one Laravel-style scaffold with WhatsApp OTP, SaaS, AR/EN/TR, responsive UI.

See `config/woork.php`, routes, migrations, and views.

## Stage 4 — Marketing & Messaging

- Expanded the landing page (multi-language) with a pipeline section that highlights the agent ingestion, adaptive alerts, and privacy-first operations.
- Documented the agent flow, daily summary generation, and policies-driven alerts in `docs/agent.md`.
- Adjusted `resources/lang/*/public.php` to include the new marketing copy and CTA for the enhanced service stack.


## Production .env additions (v3)
```
WHATSAPP_DRIVER=standingtech
STANDINGTECH_URL=https://gateway.standingtech.com/api/v4/sms/send
STANDINGTECH_TOKEN=YOUR_TOKEN
STANDINGTECH_SENDER=Woork
STANDINGTECH_TYPE=whatsapp
STANDINGTECH_LANG=en

STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
STRIPE_PRICE_BASIC_MONTHLY=price_xxx
STRIPE_PRICE_PRO_MONTHLY=price_xxx
```
Enable queues + scheduler:
```
php artisan queue:work
php artisan schedule:work
```
