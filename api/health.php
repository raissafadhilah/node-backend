<?php

require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/config/response.php';

jsonResponse(
    true,
    'Node API berhasil berjalan.',
    [
        'status' => 'ok'
    ]
);