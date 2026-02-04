<?php
// Webhook Stripe: aggiorna stato booking in base a PaymentIntent.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

$payload = file_get_contents('php://input');
$sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

function fail($msg){ http_response_code(400); exit($msg); }

// Verifica firma (minima). In produzione usa libreria stripe-php.
$secret = STRIPE_WEBHOOK_SECRET;
if(!$secret || strpos($sig,'t=')===false){ fail('NO_SIG'); }

$event = json_decode($payload, true);
if(!$event || empty($event['type'])) fail('BAD_JSON');

if($event['type'] === 'payment_intent.succeeded'){
  $pi = $event['data']['object'];
  $booking_id = (int)($pi['metadata']['booking_id'] ?? 0);
  if($booking_id){
    $upd = db()->prepare("UPDATE bookings SET status='paid' WHERE id=?");
    $upd->bind_param("i",$booking_id);
    $upd->execute();
  }
}

http_response_code(200);
echo "OK";
