<?php
require_once __DIR__ . '/api/bootstrap.php';

if (empty($_SESSION['user_id'])) {
  header("Location: login.html");
  exit;
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <div class="container" style="max-width:900px">
    <div class="card">
      <div class="section-title">Dashboard</div>
      <p class="p">Login OK. User ID: <b><?= (int)$_SESSION['user_id'] ?></b></p>
      <a class="btn secondary" href="index.html">Home</a>
      <a class="btn primary" href="api/logout.php">Esci</a>
    </div>
  </div>
</body>
</html>
<a class="btn secondary" href="bookings.php">Prenotazioni</a>
<a class="btn secondary" href="messages.php">Messaggi</a>
