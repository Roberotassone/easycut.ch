<?php
require_once __DIR__ . '/api/bootstrap.php';
require_once __DIR__ . '/api/db.php';

if (empty($_SESSION['user_id'])) {
  header("Location: login.html");
  exit;
}

$userId = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'client';

$m = db();

if ($role === 'stylist') {
  $stmt = $m->prepare("
    SELECT b.*, u.name AS client_name
    FROM bookings b
    JOIN users u ON u.id = b.client_id
    WHERE b.stylist_id = ?
    ORDER BY b.id DESC
  ");
  $stmt->bind_param("i", $userId);
} else {
  $stmt = $m->prepare("
    SELECT b.*, u.name AS stylist_name
    FROM bookings b
    JOIN users u ON u.id = b.stylist_id
    WHERE b.client_id = ?
    ORDER BY b.id DESC
  ");
  $stmt->bind_param("i", $userId);
}

$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Prenotazioni</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="container" style="max-width:1000px">
    <div class="card">
      <div class="section-title">Prenotazioni</div>

      <?php if (!$rows): ?>
        <p class="p">Nessuna prenotazione.</p>
      <?php else: ?>
        <div class="list">
          <?php foreach ($rows as $b): ?>
            <div class="card" style="margin:10px 0">

              <div class="small">
                <b>#<?= (int)$b['id'] ?></b> •
                Stato: <b><?= htmlspecialchars($b['status']) ?></b> •
                Data: <b><?= htmlspecialchars($b['date_time']) ?></b>
              </div>

              <div style="margin-top:6px">
                <b><?= htmlspecialchars($b['service_name']) ?></b>
                — CHF <?= htmlspecialchars($b['service_price']) ?>
              </div>

              <div class="small" style="margin-top:6px">
                <?php if ($role === 'stylist'): ?>
                  Cliente: <b><?= htmlspecialchars($b['client_name']) ?></b>
                <?php else: ?>
                  Parrucchiere: <b><?= htmlspecialchars($b['stylist_name']) ?></b>
                <?php endif; ?>
              </div>

              <?php if (!empty($b['notes'])): ?>
                <div class="small" style="margin-top:6px">
                  <?= nl2br(htmlspecialchars($b['notes'])) ?>
                </div>
              <?php endif; ?>

              <div class="small" style="margin-top:6px">
                Pagamento: <b><?= htmlspecialchars($b['payment_status'] ?? 'unpaid') ?></b>
              </div>

              <?php if ($role === 'stylist'): ?>
                <!-- PARRUCCHIERE -->
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px">
                  <a class="btn secondary" href="messages.php?booking_id=<?= (int)$b['id'] ?>">Apri chat</a>

                  <a class="btn secondary" href="api/book-status.php?id=<?= (int)$b['id'] ?>&s=confirmed">Conferma</a>
                  <a class="btn ghost" href="api/book-status.php?id=<?= (int)$b['id'] ?>&s=declined">Rifiuta</a>

                  <!-- Completa SOLO se pagato (evita recensioni senza pagamento) -->
                  <?php if (($b['payment_status'] ?? 'unpaid') === 'paid'): ?>
                    <a class="btn primary" href="api/book-status.php?id=<?= (int)$b['id'] ?>&s=completed">Completa</a>
                  <?php else: ?>
                    <span class="small" style="align-self:center;opacity:.8">
                      (Completa dopo pagamento)
                    </span>
                  <?php endif; ?>
                </div>

              <?php else: ?>
                <!-- CLIENTE -->
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px">
                  <a class="btn secondary" href="messages.php?booking_id=<?= (int)$b['id'] ?>">Apri chat</a>

                  <?php if (($b['status'] ?? '') === 'confirmed' && (($b['payment_status'] ?? 'unpaid') !== 'paid')): ?>
                    <a class="btn primary" href="pay.php?booking_id=<?= (int)$b['id'] ?>">Paga</a>
                  <?php endif; ?>

                  <?php if (($b['status'] ?? '') === 'completed'): ?>
                    <a class="btn primary" href="review.php?booking_id=<?= (int)$b['id'] ?>">Lascia recensione</a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
        <a class="btn secondary" href="dashboard.php">Dashboard</a>
        <a class="btn ghost" href="search.html">Cerca</a>
      </div>
    </div>
  </div>
</body>
</html>

</html>
