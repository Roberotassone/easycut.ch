<?php
require_once __DIR__ . '/db.php';

$token = $_GET['token'] ?? '';
if (!$token) die("BAD_TOKEN");

$stmt = db()->prepare("UPDATE users
                       SET email_verified_at = NOW(), verify_token = NULL
                       WHERE verify_token = ? AND email_verified_at IS NULL");
$stmt->execute([$token]);

if ($stmt->affected_rows > 0) {
  header("Location: ../login.html?verified=1");
  exit;
}

echo "LINK_NON_VALIDO_O_GIA_USATO";
