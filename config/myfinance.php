<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Polling interval in seconds for real-time frontend notification polling.
    | Default is 15 seconds.
    |
    */
    'notification_poll_interval' => (int) env('NOTIFICATION_POLL_INTERVAL', 15),

    /*
    |--------------------------------------------------------------------------
    | Financial Alerts Thresholds
    |--------------------------------------------------------------------------
    */
    'low_balance_threshold' => (float) env('LOW_BALANCE_THRESHOLD', 200000), // Saldo di bawah Rp 200.000
    'debt_due_days_ahead' => (int) env('DEBT_DUE_DAYS_AHEAD', 3), // Pengingat H-3 sebelum jatuh tempo
];

