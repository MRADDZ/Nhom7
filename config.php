<?php
const DB_NAME = 'crud_ai';
const DB_USER = 'root';
const DB_PASSWORD = '';
const DB_HOST = 'localhost';
const PORT = 3306;
const DB_CHARSET = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST .
        ";port=" . PORT .
        ";dbname=" . DB_NAME .
        ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {


    http_response_code(500);

    header("Content-Type: application/json; charset=utf-8");

    echo json_encode([
        "success" => false,
        "message" => "Không kết nối được với database. Hãy kiểm tra MySQL."
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// Nap API key tu file rieng (file nay khong day len GitHub)
if (file_exists(__DIR__ . "/config_ai.php")) {
    require_once __DIR__ . "/config_ai.php";
}