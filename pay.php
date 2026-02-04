<?php
require_once __DIR__ . '/api/bootstrap.php';
require_once __DIR__ . '/api/db.php';

if (empty($_SESSION['user_id'])) { header("Location: login.html"); exit; }
$userId = (int)$_SESSION['user_id'];

$bookingId = (int)($_GET['booking_id'] ?? 0);
if ($bookingId <= 0) { header("Location: bookings.php"); exit; }

$m = db();

$stmt = $m->prepare("
  SELECT b.*, u.name AS stylist_name, p.stripe_account_id
  FROM bookings b
  JOIN users u ON u.id=b.stylist_id
  LEFT JOIN profiles p ON p.user_id=b.stylist_id
  WHERE b.id=? AND b.client_id=? LIMIT 1
");
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();

if (!$b) { echo "Prenotazione non trovata"; exit; }

if (($b['status'] ?? '') !== 'confirmed') { echo "Puoi pagare solo dopo conferma del parrucchiere."; exit; }
if (($b['payment_status'] ?? 'unpaid') === 'paid') { header("Location: pay-success.php?booking_id=".$bookingId); exit; }

?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Paga</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="container" style="max-width:720px">
    <div class="card">
      <div class="section-title">Pagamento</div>

      <div class="small">
        Booking #<?= (int)$b['id'] ?> • Stato: <b><?= htmlspecialchars($b['status']) ?></b>
      </div>

      <div style="margin-top:10px">
        <b><?= htmlspecialchars($b['service_name']) ?></b><br>
        Parrucchiere: <b><?= htmlspecialchars($b['stylist_name']) ?></b><br>
        Prezzo: <b>CHF <?= htmlspecialchars($b['service_price']) ?></b>
      </div>

      <div class="small" style="margin-top:12px">
        Commissione piattaforma: <b>2%</b> (trattenuta automatica).
      </div>

      <?php if (empty($b['stripe_account_id'])): ?>
        <div class="card" style="margin-top:12px">
          <b>Impossibile pagare</b><br>
          <div class="small">
            Questo parrucchiere non ha ancora collegato Stripe (manca <code>acct_...</code> nel profilo).
          </div>
          <div style="margin-top:10px">
            <a class="btn secondary" href="messages.php?booking_id=<?= (int)$b['id'] ?>">Apri chat</a>
            <a class="btn ghost" href="bookings.php">Indietro</a>
          </div>
        </div>
      <?php else: ?>
        <form action="api/stripe-create-checkout.php" method="POST" style="margin-top:14px">
          <input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>">
          <button class="btn primary" type="submit">Paga con Stripe</button>
          <a class="btn secondary" href="bookings.php">Indietro</a>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
