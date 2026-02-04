<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_role('client');

$u = current_user();
$service_id = (int)($_POST['service_id'] ?? 0);
$start_at = trim($_POST['start_at'] ?? '');
$address = trim($_POST['address_text'] ?? '');
$notes = trim($_POST['notes'] ?? '');

$stmt = db()->prepare("SELECT s.id,s.price_chf,s.duration_min,s.hairdresser_id FROM services s WHERE s.id=? AND s.active=1");
$stmt->bind_param("i",$service_id);
$stmt->execute();
$svc = $stmt->get_result()->fetch_assoc();
if(!$svc){ http_response_code(404); exit('SERVICE_NOT_FOUND'); }

$duration = (int)$svc['duration_min'];
$end_at = date('Y-m-d H:i', strtotime($start_at . " +$duration minutes"));

$amount = (float)$svc['price_chf'];
$fee = round($amount * PLATFORM_FEE_RATE, 2);

$ins = db()->prepare("INSERT INTO bookings (client_id,hairdresser_id,service_id,start_at,end_at,address_text,notes,status,amount_total_chf,platform_fee_chf)
                      VALUES (?,?,?,?,?,?,?,?,?,?)");
$status='pending';
$ins->bind_param("iiissssssd",
  $u['id'], $svc['hairdresser_id'], $service_id, $start_at, $end_at, $address, $notes, $status, $amount, $fee
);
$ins->execute();

header('Location: /public/dashboard.php');
