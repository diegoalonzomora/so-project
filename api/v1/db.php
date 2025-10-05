<?php
header('Content-Type: application/json; charset=utf-8');

$conn = mysqli_connect(
    '20.63.12.72',
    'ecommerce_admin',
    'project1',
    'Cloudbeds'
);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión: ' . mysqli_connect_error()]);
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');
?>