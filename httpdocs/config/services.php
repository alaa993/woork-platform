<?php
return [
  'whatsapp' => [
    'driver' => env('WHATSAPP_DRIVER','log'),
    'standingtech' => [
      'url'   => env('STANDINGTECH_URL','https://gateway.standingtech.com/api/v4/sms/send'),
      'token' => env('STANDINGTECH_TOKEN'),
      'sender_id' => env('STANDINGTECH_SENDER'),   // <-- هكذا
      'type'  => env('STANDINGTECH_TYPE','whatsapp'),
      'lang'  => env('STANDINGTECH_LANG','en'),
    ],
  ],

  'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'price_basic_monthly' => env('STRIPE_PRICE_BASIC_MONTHLY'), // price_xxx IDs
    'price_pro_monthly'   => env('STRIPE_PRICE_PRO_MONTHLY'),
  ],
];
