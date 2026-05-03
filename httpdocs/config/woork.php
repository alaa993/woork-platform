<?php

return [
    'save_video' => env('WOORK_PRIVACY_SAVE_VIDEO', false),
    'results_token' => env('WOORK_RESULTS_TOKEN', 'changeme-results-token'),
    'available_locales' => ['en', 'ar', 'tr'],
    'default_locale' => env('WOORK_DEFAULT_LANG', 'en'),
    // Set to 0 to keep trial subscriptions open-ended. Set back to 14 for a normal 14-day trial.
    'trial_days' => env('WOORK_TRIAL_DAYS'),
    'thresholds' => [
        'long_idle_minutes' => 25,
        'phone_max_minutes' => 20,
        'leave_max_minutes' => 30,
        'camera_offline_after_minutes' => 5,
        'camera_warning_after_minutes' => 3,
        'detector_fallback' => true,
        'phone_detection_unavailable' => true,
    ],
];
