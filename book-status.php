<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) { header("Location: ../login.html"); exit; }
if (($_SESSION['role'] ?? '') !== 'stylist') { header("Location: ../dashboard.php"); exit; }

$userId = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);
$s = trim($_GET['s'] ?? '');

$allowed = ['confirmed','declined','completed'];
if ($id <= 0 || !in_array($s, $allowed, true)) {
  header("Location: ../bookings.php");
  exit;
}

$m = db();
$stmt = $m->prepare("UPDATE bookings SET status=? WHERE id=? AND stylist_id=?");
$stmt->bind_param("sii", $s, $id, $userId);
$stmt->execute();

header("Location: ../bookings.php");
exit;
