<?php

header('Content-Type: application/json; charset=UTF-8');

function jsonResponse(
    bool $success,
    string $message,
    mixed $data = null,
    int $statusCode = 200
): never {
    http_response_code($statusCode);

    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);

    exit;
}