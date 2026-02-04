<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['ok' => false]);
  exit;
}

echo json_encode([
  'ok' => true,
  'user_id' => $_SESSION['user_id'],
  'role' => $_SESSION['role'] ?? 'user'
]);
