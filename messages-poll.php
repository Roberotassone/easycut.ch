<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) { http_response_code(401); exit; }

$userId = (int)$_SESSION['user_id'];
$bookingId = (int)($_GET['booking_id'] ?? 0);
if ($bookingId <= 0) { exit; }

$m = db();

// check permessi
$stmt = $m->prepare("SELECT client_id, stylist_id FROM bookings WHERE id=? LIMIT 1");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();
if (!$b) exit;

if ($userId !== (int)$b['client_id'] && $userId !== (int)$b['stylist_id']) exit;

// messaggi
$stmt = $m->prepare("
  SELECT m.*, u.name AS sender_name
  FROM messages m
  JOIN users u ON u.id=m.sender_id
  WHERE m.booking_id=?
  ORDER BY m.id ASC
  LIMIT 300
");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

header('Content-Type: text/html; charset=utf-8');

foreach ($messages as $msg) {
  $mine = ((int)$msg['sender_id'] === $userId);
  ?>
  <div style="display:flex;justify-content:<?= $mine ? 'flex-end' : 'flex-start' ?>;margin:8px 0">
    <div style="max-width:75%;
                padding:10px 12px;border-radius:14px;
                border:1px solid rgba(0,0,0,.08);
                <?= $mine ? 'background:rgba(0,255,255,.10)' : 'background:rgba(255,105,180,.10)' ?>">
      <div class="small"><b><?= htmlspecialchars($msg['sender_name']) ?></b> • <?= htmlspecialchars($msg['created_at']) ?></div>
      <div><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
    </div>
  </div>
  <?php
}
