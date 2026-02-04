<?php
require_once __DIR__ . '/db.php';

$token = $_GET['token'] ?? '';
if (!$token) { die("Token non valido."); }

$m = db();

$stmt = $m->prepare("SELECT user_id FROM email_verifications WHERE token=? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();

if (!$r) { die("Link non valido o scaduto."); }

$userId = (int)$r['user_id'];

// attiva
$stmt = $m->prepare("UPDATE users SET email_verified_at=NOW() WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();

// rimuovi token
$stmt = $m->prepare("DELETE FROM email_verifications WHERE user_id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();

header("Location: /login.html?verified=1");
exit;
