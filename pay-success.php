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
  <title>Pagamento riuscito</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="container" style="max-width:720px">
    <div class="card">
      <div class="section-title">Pagamento riuscito ✅</div>
      <p class="p">Grazie! Il pagamento è stato registrato.</p>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a class="btn secondary" href="messages.php?booking_id=<?= (int)$bookingId ?>">Apri chat</a>
        <a class="btn primary" href="bookings.php">Prenotazioni</a>
      </div>
    </div>
  </div>
</body>
</html>
