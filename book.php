<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) {
  header("Location: ../login.html");
  exit;
}

$clientId = (int)$_SESSION['user_id'];

$stylistId = (int)($_POST['stylist_id'] ?? 0);
$service = trim($_POST['service_name'] ?? '');
$price = (float)($_POST['service_price'] ?? 0);
$date = $_POST['date_time'] ?? '';
$notes = trim($_POST['notes'] ?? '');

if ($stylistId <= 0 || $service === '' || $price <= 0 || $date === '') {
  die("Dati non validi.");
}

$m = db();

$stmt = $m->prepare("
  INSERT INTO bookings
  (client_id, stylist_id, service_name, service_price, date_time, notes, status, payment_status)
  VALUES (?, ?, ?, ?, ?, ?, 'pending', 'unpaid')
");
$stmt->bind_param(
  "iisds s",
  $clientId,
  $stylistId,
  $service,
  $price,
  $date,
  $notes
);

$stmt->execute();

// 👉 QUI IN FUTURO: email di notifica al parrucchiere

header("Location: ../dashboard.php?booked=1");
exit;
