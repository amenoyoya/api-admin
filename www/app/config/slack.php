<?php

return [
    // Incoming Webhook URL
    'url' => env('SLACK_URL', ['SELECT slack_webhook_url FROM uniq_settings WHERE id = :id', ['id' => 1], 'slack_webhook_url']),

    // チャンネル設定
    'default' => 'notice',
    'channels' => [
        'notice' => [
            'username' => 'Notice',
            'icon' => ':ghost:',
            'channel' => '#general',
        ],
        'error' => [
            'username' => 'Error',
            'icon' => ':scream:',
            'channel' => '#general',
        ],
    ],
];