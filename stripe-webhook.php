<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

$payload = @file_get_contents('php://input');
$sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

require_once __DIR__ . '/stripe/init.php';

try {
  $event = \Stripe\Webhook::constructEvent(
    $payload,
    $sig,
    STRIPE_WEBHOOK_SECRET
  );
} catch (Exception $e) {
  http_response_code(400);
  exit;
}

if ($event->type === 'checkout.session.completed') {
  $session = $event->data->object;
  $sessionId = $session->id;

  $m = db();
  $stmt = $m->prepare("
    UPDATE bookings
    SET payment_status='paid', status='confirmed'
    WHERE stripe_session_id=?
  ");
  $stmt->bind_param("s", $sessionId);
  $stmt->execute();
}

http_response_code(200);
