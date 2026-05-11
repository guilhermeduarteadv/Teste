<?php
return [
    'cnj' => [
        'base_url' => $_ENV['CNJ_API_URL'] ?? 'https://api-publica.datajud.cnj.jus.br',
        'api_key'  => $_ENV['CNJ_API_KEY'] ?? '',
        'timeout'  => 30,
        'retry'    => 3,
        'endpoints' => [
            'process_by_oab'    => '/api_publica_{tribunal}/query',
            'process_by_number' => '/api_publica_{tribunal}/query',
        ],
        'tribunais' => [
            'TJAC', 'TJAL', 'TJAP', 'TJAM', 'TJBA', 'TJCE', 'TJDF',
            'TJES', 'TJGO', 'TJMA', 'TJMT', 'TJMS', 'TJMG', 'TJPA',
            'TJPB', 'TJPR', 'TJPE', 'TJPI', 'TJRJ', 'TJRN', 'TJRS',
            'TJRO', 'TJRR', 'TJSC', 'TJSP', 'TJSE', 'TJTO',
            'TRF1', 'TRF2', 'TRF3', 'TRF4', 'TRF5', 'TRF6',
            'TST', 'STJ', 'STF', 'TSE', 'STM',
        ],
    ],
    'mail' => [
        'host'       => $_ENV['MAIL_HOST'] ?? '',
        'port'       => $_ENV['MAIL_PORT'] ?? 587,
        'username'   => $_ENV['MAIL_USERNAME'] ?? '',
        'password'   => $_ENV['MAIL_PASSWORD'] ?? '',
        'from'       => $_ENV['MAIL_FROM'] ?? 'noreply@juriscontrol.com.br',
        'encryption' => 'tls',
    ],
];
