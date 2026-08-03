<?php
return [
    'ice_servers' => [
        [
            'urls' => [
                'stun:85.132.8.162:3478',
                'stun:chatroulette.linkpc.net:3478',
                'stun:iphone-main.rnjet.com:3478',
            ],
        ],
        [
            'urls' => [
                'turn:85.132.8.162:3478?transport=udp',
                'turn:85.132.8.162:3478?transport=tcp',
                'turn:chatroulette.linkpc.net:3478?transport=udp',
                'turn:chatroulette.linkpc.net:3478?transport=tcp',
                'turns:85.132.8.162:5349?transport=tcp',
                'turns:chatroulette.linkpc.net:5349?transport=tcp',
            ],
            'username' => env('TURN_USERNAME', 'turn_user'),
            'credential' => env('TURN_CREDENTIAL', 'D|66BY&,Rw{5K46jl@#o'),
        ],
    ],
];