<?php

return [
    'ice_servers' => [
        // STUN сервера (бесплатные от Google)
        ['urls' => ['stun:stun.l.google.com:19302', 'stun:stun1.l.google.com:19302']],
        
        // Ваш основной TURN сервер
        [
            'urls' => [env('TURN_SERVER_URL')],
            'username' => env('TURN_SERVER_USER'),
            'credential' => env('TURN_SERVER_PASS'),
        ],
    ],
];