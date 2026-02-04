<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

if (empty($_SESSION['user_id'])) { http_response_code(403); exit; }

$bookingId = (int)($_GET['id'] ?? 0);
$m = db();

$stmt = $m->prepare("
  SELECT b.*, p.stripe_account_id
  FROM bookings b
  JOIN profiles p ON p.user_id = b.stylist_id
  WHERE b.id=? LIMIT 1
");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();

if (!$b || empty($b['stripe_account_id'])) {
  echo "Errore pagamento";
  exit;
}

require_once __DIR__ . '/stripe/init.php';
\Stripe\Stripe::setApiKey(STRIPE_SECRET);

$priceCents = (int)round($b['service_price'] * 100);
$feeCents   = (int)round($priceCents * 0.02);

$session = \Stripe\Checkout\Session::create([
  'mode' => 'payment',
  'line_items' => [[
    'price_data' => [
      'currency' => 'chf',
      'product_data' => [
        'name' => $b['service_name'],
      ],
      'unit_amount' => $priceCents,
    ],
    'quantity' => 1,
  ]],
  'payment_intent_data' => [
    'application_fee_amount' => $feeCents,
    'transfer_data' => [
      'destination' => $b['stripe_account_id'],
    ],
  ],
  'success_url' => SITE_URL . '/dashboard.php?paid=1',
  'cancel_url'  => SITE_URL . '/dashboard.php?paid=0',
]);

$stmt = $m->prepare("UPDATE bookings SET stripe_session_id=? WHERE id=?");
$stmt->bind_param("si", $session->id, $bookingId);
$stmt->execute();

header("Location: " . $session->url);
exit;
