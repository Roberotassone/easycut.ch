<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) { header("Location: ../login.html"); exit; }

$userId = (int)$_SESSION['user_id'];
$bookingId = (int)($_POST['booking_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($bookingId <= 0 || $rating < 1 || $rating > 5) {
  header("Location: ../bookings.php");
  exit;
}

$m = db();

// booking deve essere del client e completato
$stmt = $m->prepare("SELECT client_id, stylist_id, status FROM bookings WHERE id=? LIMIT 1");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();

if (!$b) { header("Location: ../bookings.php"); exit; }
if ((int)$b['client_id'] !== $userId) { header("Location: ../bookings.php"); exit; }
if ($b['status'] !== 'completed') { header("Location: ../bookings.php"); exit; }

// evita doppioni
$stmt = $m->prepare("SELECT id FROM reviews WHERE booking_id=? LIMIT 1");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
if ($exists) { header("Location: ../bookings.php"); exit; }

$stylistId = (int)$b['stylist_id'];

$stmt = $m->prepare("INSERT INTO reviews (booking_id, client_id, stylist_id, rating, comment, created_at)
                     VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("iiiis", $bookingId, $userId, $stylistId, $rating, $comment);
$stmt->execute();

header("Location: ../profile-view.php?id=" . $stylistId);
exit;
