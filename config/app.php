<?php
return [
    'name'     => $_ENV['APP_NAME'] ?? 'JurisControl',
    'env'      => $_ENV['APP_ENV'] ?? 'production',
    'debug'    => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'url'      => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'America/Sao_Paulo',
    'key'      => $_ENV['APP_KEY'] ?? '',
    'version'  => '1.0.0',
    'session'  => [
        'lifetime' => (int)($_ENV['SESSION_LIFETIME'] ?? 120),
        'name'     => $_ENV['SESSION_NAME'] ?? 'juriscontrol_session',
    ],
    'storage' => [
        'path'      => $_ENV['STORAGE_PATH'] ?? __DIR__ . '/../storage',
        'documents' => $_ENV['STORAGE_PATH'] . '/documents',
        'temp'      => $_ENV['STORAGE_PATH'] . '/temp',
    ],
    'log_path' => $_ENV['LOG_PATH'] ?? __DIR__ . '/../logs',
    'allowed_extensions' => ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'],
    'allowed_mimes' => [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],
    'max_upload_size' => 20 * 1024 * 1024, // 20MB
];
