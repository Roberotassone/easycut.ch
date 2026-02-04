<?php
require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $mysqli->set_charset('utf8mb4');
    echo "✅ CONNESSIONE AL DATABASE OK";
} catch (Exception $e) {
    echo "❌ DB ERROR: " . $e->getMessage();
}
