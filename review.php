<?php
require_once __DIR__ . '/api/bootstrap.php';
require_once __DIR__ . '/api/db.php';

if (empty($_SESSION['user_id'])) { header("Location: login.html"); exit; }

$userId = (int)$_SESSION['user_id'];
$bookingId = (int)($_GET['booking_id'] ?? 0);
if ($bookingId <= 0) { header("Location: bookings.php"); exit; }

$m = db();

// booking deve essere del client e completato
$stmt = $m->prepare("SELECT b.*, u.name AS stylist_name
                     FROM bookings b
                     JOIN users u ON u.id=b.stylist_id
                     WHERE b.id=? AND b.client_id=? LIMIT 1");
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();

if (!$b) { echo "Prenotazione non trovata"; exit; }
if ($b['status'] !== 'completed') { echo "Puoi recensire solo dopo completamento."; exit; }

// già recensito?
$stmt = $m->prepare("SELECT id FROM reviews WHERE booking_id=? LIMIT 1");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
if ($exists) { header("Location: bookings.php"); exit; }
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Recensione</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="container" style="max-width:700px">
    <div class="card">
      <div class="section-title">Lascia una recensione</div>

      <div class="small" style="margin-bottom:10px">
        Parrucchiere: <b><?= htmlspecialchars($b['stylist_name']) ?></b> •
        Servizio: <b><?= htmlspecialchars($b['service_name']) ?></b>
      </div>

      <form class="list" action="api/review-create.php" method="POST">
        <input type="hidden" name="booking_id" value="<?= (int)$bookingId ?>">

        <div>
          <div class="small">Valutazione (1-5)</div>
          <select name="rating" required>
            <option value="5">5 - Eccellente</option>
            <option value="4">4 - Ottimo</option>
            <option value="3">3 - Buono</option>
            <option value="2">2 - Così così</option>
            <option value="1">1 - Scarso</option>
          </select>
        </div>

        <div>
          <div class="small">Commento (opzionale)</div>
          <textarea name="comment" rows="4" placeholder="Com’è andata?"></textarea>
        </div>

        <button class="btn primary" type="submit">Invia recensione</button>
        <a class="btn secondary" href="bookings.php">Indietro</a>
      </form>
    </div>
  </div>
</body>
</html>
