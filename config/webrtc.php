<?php

return [
    'ice_servers' => [
        [
            'urls' => ['stun:85.132.8.162:3478'],
        ],
        [
            'urls' => [
                'turn:85.132.8.162:3478?transport=udp', // Существующая запись
                'turn:85.132.8.162:3478?transport=tcp', // Добавляем поддержку TCP
            ],
            'username' => 'turn_user',
            'credential' => 'T1I0nE886NsvfIkYBA3w',
        ],
    ],
];