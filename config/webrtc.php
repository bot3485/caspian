<?php
return [
    'ice_servers' => [
        [
            'urls' => [
                'stun:85.132.8.162:3478',
                'stun:chatroulette.linkpc.net:3478'
            ],
        ],
        [
            'urls' => [
                'turn:85.132.8.162:3478?transport=udp',
                'turn:85.132.8.162:3478?transport=tcp'
            ],
            'username' => 'turn_user',
            'credential' => 'T1I0nE886NsvfIkYBA3w',
        ],
    ],
];