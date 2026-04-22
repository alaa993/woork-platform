Woork Full Views Pack (i18n EN/AR/TR) — v2
=========================================
Includes translated blades for:
- public: landing, login
- dashboard: index
- rooms: index/create/edit
- cameras: index/create/edit
- employees: index/create/edit
- alerts: index
- policies: index (PUT policies.update)
- subscription: index (billing.checkout / billing.portal)
- settings: index (language switch)
- export: daily (link to CSV)
- partials/flash
- layouts/app + layouts/public (larger logo, i18n-ready)
- resources/css/woork-crossbrowser.css

How to install:
1) Unzip into your Laravel project root (will create/replace only these files).
2) Import CSS helper (optional) in resources/css/app.css:
   @import "./woork-crossbrowser.css";
3) Build assets if needed: npm run build
4) Clear caches: php artisan cache:clear view:clear config:clear

Notes:
- Forms rely on your existing controllers/routes exactly as you shared.
- Employees/Cameras forms expect $rooms when rendering (supply from controller).
- Feel free to adjust titles or add validation messages to lang files.
