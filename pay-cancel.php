<?php
require_once __DIR__ . '/api/bootstrap.php';
if (empty($_SESSION['user_id'])) { header("Location: login.html"); exit; }
$bookingId = (int)($_GET['booking_id'] ?? 0);
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pagamento annullato</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="container" style="max-width:720px">
    <div class="card">
      <div class="section-title">Pagamento annullato</div>
      <p class="p">Hai annullato il pagamento.</p>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a class="btn primary" href="pay.php?booking_id=<?= (int)$bookingId ?>">Riprova</a>
        <a class="btn secondary" href="bookings.php">Prenotazioni</a>
      </div>
    </div>
  </div>
</body>
</html>
