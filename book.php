<?php
require_once __DIR__ . '/api/bootstrap.php';
require_once __DIR__ . '/api/db.php';

if (empty($_SESSION['user_id'])) {
  header("Location: login.html");
  exit;
}

$clientId = (int)$_SESSION['user_id'];
$stylistId = (int)($_GET['stylist_id'] ?? 0);

if ($stylistId <= 0) {
  die("Parrucchiere non valido.");
}

$m = db();

// prendo info parrucchiere
$stmt = $m->prepare("
  SELECT u.id, u.name, p.canton, p.city
  FROM users u
  LEFT JOIN profiles p ON p.user_id = u.id
  WHERE u.id = ? AND u.role='hairdresser'
  LIMIT 1
");
$stmt->bind_param("i", $stylistId);
$stmt->execute();
$stylist = $stmt->get_result()->fetch_assoc();

if (!$stylist) {
  die("Parrucchiere non trovato.");
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <title>Prenota — EasyCut</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="nav">
  <div class="nav-inner">
    <div class="brand">
      <a href="index.html" style="text-decoration:none;color:inherit">EasyCut</a>
    </div>
    <div class="navlinks">
      <a class="btn ghost" href="dashboard.php">Dashboard</a>
      <a class="btn primary" href="api/logout.php">Esci</a>
    </div>
  </div>
</div>

<div class="container" style="max-width:700px">
  <div class="card">
    <div class="section-title">Prenota appuntamento</div>

    <p class="p">
      Parrucchiere: <b><?= htmlspecialchars($stylist['name']) ?></b><br>
      Zona: <?= htmlspecialchars($stylist['city'] ?? '') ?> <?= htmlspecialchars($stylist['canton'] ?? '') ?>
    </p>

    <form class="list" method="POST" action="api/book.php">

      <input type="hidden" name="stylist_id" value="<?= (int)$stylistId ?>">

      <div>
        <div class="small">Servizio</div>
        <input name="service_name" required placeholder="Es. Taglio uomo">
      </div>

      <div>
        <div class="small">Prezzo (CHF)</div>
        <input name="service_price" type="number" step="0.01" required>
      </div>

      <div>
        <div class="small">Data e ora</div>
        <input name="date_time" type="datetime-local" required>
      </div>

      <div>
        <div class="small">Note (opzionale)</div>
        <textarea name="notes" rows="3"></textarea>
      </div>

      <button class="btn primary" type="submit">
        Invia richiesta
      </button>
    </form>
  </div>
</div>

</body>
</html>
