<?php
require_once __DIR__ . '/api/bootstrap.php';
require_once __DIR__ . '/api/db.php';

if (empty($_SESSION['user_id'])) { header("Location: login.html"); exit; }

$userId = (int)$_SESSION['user_id'];
$bookingId = (int)($_GET['booking_id'] ?? 0);
if ($bookingId <= 0) { header("Location: bookings.php"); exit; }

$m = db();

// verifica che l’utente faccia parte della prenotazione
$stmt = $m->prepare("SELECT b.*, u1.name AS client_name, u2.name AS stylist_name
                     FROM bookings b
                     JOIN users u1 ON u1.id=b.client_id
                     JOIN users u2 ON u2.id=b.stylist_id
                     WHERE b.id=? LIMIT 1");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();

if (!$b) { echo "Booking non trovato"; exit; }

if ((int)$b['client_id'] !== $userId && (int)$b['stylist_id'] !== $userId) {
  http_response_code(403);
  echo "Non autorizzato";
  exit;
}

$otherName = ((int)$b['client_id'] === $userId) ? $b['stylist_name'] : $b['client_name'];

// messaggi
$stmt = $m->prepare("SELECT m.*, u.name AS sender_name
                     FROM messages m
                     JOIN users u ON u.id=m.sender_id
                     WHERE m.booking_id=?
                     ORDER BY m.id ASC");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$msgs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Chat</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="container" style="max-width:900px">
    <div class="card">
      <div class="section-title">Chat con <?= htmlspecialchars($otherName) ?></div>

      <div class="small" style="margin-bottom:8px">
        Booking #<?= (int)$b['id'] ?> • <?= htmlspecialchars($b['service_name']) ?> • CHF <?= htmlspecialchars($b['service_price']) ?>
      </div>

      <div id="chatBox" class="card" style="height:420px;overflow:auto;padding:14px;background:#fff">
        <?php if (!$msgs): ?>
          <div class="small">Nessun messaggio. Scrivi il primo 👇</div>
        <?php else: ?>
          <?php foreach ($msgs as $x): ?>
            <?php $mine = ((int)$x['sender_id'] === $userId); ?>
            <div style="display:flex;justify-content:<?= $mine ? 'flex-end' : 'flex-start' ?>;margin:8px 0">
              <div style="max-width:70%;padding:10px 12px;border-radius:14px;
                          background:<?= $mine ? '#eaf3ff' : '#f5f5f7' ?>;">
                <div class="small" style="opacity:.8">
                  <b><?= htmlspecialchars($mine ? 'Tu' : $x['sender_name']) ?></b>
                  • <?= htmlspecialchars($x['created_at']) ?>
                </div>
                <div style="margin-top:4px;white-space:pre-wrap"><?= htmlspecialchars($x['body']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <form class="list" action="api/message-send.php" method="POST" style="margin-top:10px">
        <input type="hidden" name="booking_id" value="<?= (int)$bookingId ?>">
        <div>
          <div class="small">Messaggio</div>
          <textarea name="body" rows="3" required placeholder="Scrivi qui..."></textarea>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <button class="btn primary" type="submit">Invia</button>
          <a class="btn secondary" href="bookings.php">Prenotazioni</a>
        </div>
      </form>
    </div>
  </div>

  <script>
    const box = document.getElementById('chatBox');
    if (box) box.scrollTop = box.scrollHeight;
  </script>
</body>
</html>
