<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) { header("Location: ../login.html"); exit; }

$userId = (int)$_SESSION['user_id'];
$bookingId = (int)($_POST['booking_id'] ?? 0);
$body = trim($_POST['body'] ?? '');

if ($bookingId <= 0 || $body === '') { header("Location: ../bookings.php"); exit; }

$m = db();

// verifica autorizzazione
$stmt = $m->prepare("SELECT client_id, stylist_id FROM bookings WHERE id=? LIMIT 1");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();

if (!$b) { header("Location: ../bookings.php"); exit; }
if ((int)$b['client_id'] !== $userId && (int)$b['stylist_id'] !== $userId) {
  http_response_code(403);
  echo "Non autorizzato";
  exit;
}

$stmt = $m->prepare("INSERT INTO messages (booking_id, sender_id, body) VALUES (?,?,?)");
$stmt->bind_param("iis", $bookingId, $userId, $body);
$stmt->execute();

header("Location: ../messages.php?booking_id=" . $bookingId);
exit;
