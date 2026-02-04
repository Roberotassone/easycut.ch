<?php
require_once __DIR__ . '/api/bootstrap.php';
require_once __DIR__ . '/api/db.php';

$m = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { die("Profilo non valido."); }

// prendo solo parrucchieri + verificati
$stmt = $m->prepare("
  SELECT
    u.id, u.role, u.name, u.email, u.phone, u.city, u.email_verified_at,
    p.age, p.residence, p.canton, p.languages, p.bio, p.radius_km, p.profile_image, p.lat, p.lng
  FROM users u
  LEFT JOIN profiles p ON p.user_id = u.id
  WHERE u.id = ?
    AND u.role = 'hairdresser'
    AND u.email_verified_at IS NOT NULL
  LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if (!$u) { die("Parrucchiere non trovato oppure non verificato."); }

// rating (se tabella reviews esiste)
$avg = null; $cnt = 0;
$hasReviews = false;
try {
  $r = $m->query("SHOW TABLES LIKE 'reviews'");
  $hasReviews = ($r && $r->num_rows > 0);
} catch(Throwable $e){ $hasReviews = false; }

if ($hasReviews) {
  $stmt = $m->prepare("SELECT AVG(rating) AS a, COUNT(*) AS c FROM reviews WHERE stylist_id=?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $rr = $stmt->get_result()->fetch_assoc();
  if ($rr) {
    $avg = $rr['a'] !== null ? round((float)$rr['a'], 1) : null;
    $cnt = (int)($rr['c'] ?? 0);
  }
}

// ultimi 10 commenti (se reviews esiste)
$comments = [];
if ($hasReviews) {
  $stmt = $m->prepare("
    SELECT r.rating, r.comment, r.created_at, u.name AS client_name
    FROM reviews r
    JOIN users u ON u.id = r.client_id
    WHERE r.stylist_id=?
    ORDER BY r.id DESC
    LIMIT 10
  ");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// utente loggato?
$meId = (int)($_SESSION['user_id'] ?? 0);
$meRole = $_SESSION['role'] ?? '';
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($u['name']) ?> — Profilo</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="nav">
  <div class="nav-inner">
    <div class="brand">EasyCut</div>
    <div class="navlinks">
      <a class="btn ghost" href="index.html">Home</a>
      <a class="btn ghost" href="search.html">Cerca</a>
      <?php if ($meId): ?>
        <a class="btn secondary" href="dashboard.php">Dashboard</a>
        <a class="btn primary" href="api/logout.php">Esci</a>
      <?php else: ?>
        <a class="btn secondary" href="register.html">Iscriviti</a>
        <a class="btn primary" href="login.html">Accedi</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="container" style="max-width:1000px">
  <div class="card">
    <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap">

      <div>
        <?php if (!empty($u['profile_image'])): ?>
          <img src="<?= htmlspecialchars($u['profile_image']) ?>" style="width:140px;height:140px;border-radius:24px;object-fit:cover">
        <?php else: ?>
          <div style="width:140px;height:140px;border-radius:24px;background:#eee"></div>
        <?php endif; ?>
      </div>

      <div style="flex:1;min-width:260px">
        <div style="font-size:26px"><b><?= htmlspecialchars($u['name']) ?></b></div>

        <div class="small" style="margin-top:6px">
          <?= htmlspecialchars($u['city'] ?? '') ?>
          <?php if (!empty($u['canton'])): ?> • <?= htmlspecialchars($u['canton']) ?><?php endif; ?>
          <?php if (!empty($u['radius_km'])): ?> • Raggio: <b><?= (int)$u['radius_km'] ?> km</b><?php endif; ?>
        </div>

        <?php if (!empty($u['languages'])): ?>
          <div class="small" style="margin-top:6px">Lingue: <b><?= htmlspecialchars($u['languages']) ?></b></div>
        <?php endif; ?>

        <?php if ($avg !== null): ?>
          <div class="small" style="margin-top:6px">Valutazione: <b><?= htmlspecialchars((string)$avg) ?>/5</b> (<?= (int)$cnt ?>)</div>
        <?php else: ?>
          <div class="small" style="margin-top:6px">Valutazione: <b>nessuna recensione</b></div>
        <?php endif; ?>

        <?php if (!empty($u['bio'])): ?>
          <div class="p" style="margin-top:12px"><?= nl2br(htmlspecialchars($u['bio'])) ?></div>
        <?php endif; ?>

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
          <?php if ($meId && $meId !== (int)$u['id']): ?>
            <a class="btn primary" href="messages.php?to_user_id=<?= (int)$u['id'] ?>">Scrivi</a>
            <a class="btn secondary" href="book.php?stylist_id=<?= (int)$u['id'] ?>">Prenota</a>
          <?php elseif (!$meId): ?>
            <a class="btn primary" href="login.html">Accedi per scrivere</a>
            <a class="btn secondary" href="register.html">Registrati</a>
          <?php else: ?>
            <a class="btn secondary" href="profile.php">Modifica il mio profilo</a>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>

  <div class="card" style="margin-top:14px">
    <div class="section-title">Recensioni</div>

    <?php if (!$hasReviews): ?>
      <p class="p">Sistema recensioni non attivo (tabella reviews mancante).</p>

    <?php else: ?>
      <?php if (!$comments): ?>
        <p class="p">Nessuna recensione al momento.</p>
      <?php else: ?>
        <div class="list">
          <?php foreach($comments as $c): ?>
            <div class="card" style="margin:10px 0">
              <div class="small">
                <b><?= htmlspecialchars($c['client_name'] ?? 'Cliente') ?></b>
                • voto <b><?= (int)$c['rating'] ?>/5</b>
                <?php if (!empty($c['created_at'])): ?> • <?= htmlspecialchars($c['created_at']) ?><?php endif; ?>
              </div>
              <?php if (!empty($c['comment'])): ?>
                <div class="small" style="margin-top:6px"><?= nl2br(htmlspecialchars($c['comment'])) ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

</div>
</body>
</html>
