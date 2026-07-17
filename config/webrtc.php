<?php
return [
    'ice_servers' => [
        [
            'urls' => [
                'stun:85.132.8.162:3478',
                'stun:chatroulette.linkpc.net:3478',
                'stun:iphone-main.rnjet.com:3478', // Дополнительный публичный STUN для стабильности iOS
            ],
        ],
        [
            'urls' => [
                // Стандартные порты
                'turn:85.132.8.162:3478?transport=udp',
                'turn:85.132.8.162:3478?transport=tcp',
                'turn:chatroulette.linkpc.net:3478?transport=udp',
                'turn:chatroulette.linkpc.net:3478?transport=tcp',
                // TURNS (TLS) — критично для обхода блокировок в iOS и корпоративных сетях
                // Если у тебя настроен SSL в Coturn, это обеспечит 100% коннект
                'turns:85.132.8.162:5349?transport=tcp',
                'turns:chatroulette.linkpc.net:5349?transport=tcp',
            ],
            'username' => 'turn_user',
            'credential' => 'T1I0nE886NsvfIkYBA3w',
        ],
    ],
];