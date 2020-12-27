<?php

return [
    // Incoming Webhook URL
    'url' => env('SLACK_URL', 'https://hooks.slack.com/services/XXX/XXX/XXX'),

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