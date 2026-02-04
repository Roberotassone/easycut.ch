<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/stripe-lib.php';

if (empty($_SESSION['user_id'])) { header("Location: ../login.html"); exit; }

$userId = (int)$_SESSION['user_id'];
$bookingId = (int)($_POST['booking_id'] ?? 0);
if ($bookingId <= 0) { header("Location: ../bookings.php"); exit; }

if (empty(STRIPE_SECRET)) { echo "Stripe non configurato (manca STRIPE_SECRET)."; exit; }

$m = db();

$stmt = $m->prepare("
  SELECT b.*, p.stripe_account_id, u.name AS stylist_name
  FROM bookings b
  LEFT JOIN profiles p ON p.user_id=b.stylist_id
  JOIN users u ON u.id=b.stylist_id
  WHERE b.id=? AND b.client_id=? LIMIT 1
");
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();

if (!$b) { echo "Booking non trovato"; exit; }
if (($b['status'] ?? '') !== 'confirmed') { echo "Pagamento disponibile solo dopo conferma."; exit; }
if (($b['payment_status'] ?? 'unpaid') === 'paid') { header("Location: ../pay-success.php?booking_id=".$bookingId); exit; }

$stripeAccountId = $b['stripe_account_id'] ?? '';
if (!$stripeAccountId) { echo "Parrucchiere senza Stripe Account ID."; exit; }

$price = (float)$b['service_price'];
$amount = money_to_cents($price);
if ($amount < 50) { echo "Importo troppo basso."; exit; }

$fee = (int) round($amount * 0.02); // 2% fee
// Stripe richiede interi >= 0
if ($fee < 0) $fee = 0;

$successUrl = SITE_URL . "/pay-success.php?booking_id=" . $bookingId;
$cancelUrl  = SITE_URL . "/pay-cancel.php?booking_id=" . $bookingId;

// Crea Checkout Session con destination charge:
// - application_fee_amount = 2% al sito
// - transfer_data[destination] = parrucchiere (acct_...)
$data = [
  "mode" => "payment",
  "success_url" => $successUrl,
  "cancel_url" => $cancelUrl,
  "client_reference_id" => (string)$bookingId,

  "line_items[0][price_data][currency]" => "chf",
  "line_items[0][price_data][product_data][name]" => "HairShake - " . $b['service_name'],
  "line_items[0][price_data][unit_amount]" => $amount,
  "line_items[0][quantity]" => 1,

  "payment_intent_data[application_fee_amount]" => $fee,
  "payment_intent_data[transfer_data][destination]" => $stripeAccountId,

  "metadata[booking_id]" => (string)$bookingId,
];

$session = stripe_request("POST", "/v1/checkout/sessions", $data);

// salva session id
$stmt = $m->prepare("UPDATE bookings SET stripe_session_id=?, payment_status='pending' WHERE id=? AND client_id=?");
$sid = $session['id'];
$stmt->bind_param("sii", $sid, $bookingId, $userId);
$stmt->execute();

// redirect a stripe checkout
header("Location: " . $session['url']);
exit;
