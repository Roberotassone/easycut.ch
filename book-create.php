<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) { header("Location: ../login.html"); exit; }
$clientId = (int)$_SESSION['user_id'];

$stylistId = (int)($_POST['stylist_id'] ?? 0);
$service = trim($_POST['service_name'] ?? '');
$price = (float)($_POST['service_price'] ?? 0);
$dateTime = trim($_POST['date_time'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($stylistId <= 0 || $service === '' || $dateTime === '') {
  header("Location: ../dashboard.php");
  exit;
}

// datetime-local => "YYYY-MM-DDTHH:MM"
$dateTime = str_replace('T', ' ', $dateTime) . ':00';

$m = db();

$stmt = $m->prepare("INSERT INTO bookings (client_id, stylist_id, service_name, service_price, date_time, notes, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, 'requested', NOW())");
$stmt->bind_param("iisdss", $clientId, $stylistId, $service, $price, $dateTime, $notes);
$stmt->execute();

header("Location: ../bookings.php");
exit;
