<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_role('client');
require_once __DIR__ . '/config.php';

$u = current_user();
$booking_id = (int)($_POST['booking_id'] ?? 0);

$stmt = db()->prepare("SELECT b.*, s.title FROM bookings b JOIN services s ON s.id=b.service_id
                       WHERE b.id=? AND b.client_id=? AND b.status='pending'");
$stmt->bind_param("ii",$booking_id,$u['id']);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();
if(!$b){ http_response_code(404); exit('BOOKING_NOT_FOUND'); }

$amount_cents = (int)round(((float)$b['amount_total_chf']) * 100);

$payload = http_build_query([
  'amount' => $amount_cents,
  'currency' => 'chf',
  'automatic_payment_methods[enabled]' => 'true',
  'metadata[booking_id]' => (string)$booking_id,
]);

$ch = curl_init('https://api.stripe.com/v1/payment_intents');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => $payload,
  CURLOPT_HTTPHEADER => ['Authorization: Bearer '.STRIPE_SECRET_KEY],
]);
$out = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if($code<200 || $code>=300){ http_response_code(500); exit('STRIPE_ERROR'); }
$pi = json_decode($out, true);

$upd = db()->prepare("UPDATE bookings SET stripe_payment_intent_id=? WHERE id=?");
$upd->bind_param("si",$pi['id'],$booking_id);
$upd->execute();

header('Content-Type: application/json');
echo json_encode(['client_secret' => $pi['client_secret'], 'public_key' => STRIPE_PUBLIC_KEY]);
