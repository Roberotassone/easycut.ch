<?php
require_once __DIR__ . '/db.php';

$m = db();

$canton = strtoupper(trim($_GET['canton'] ?? ''));
$km = (int)($_GET['km'] ?? 10);

$lat = $_GET['lat'] ?? '';
$lng = $_GET['lng'] ?? '';

if ($km <= 0) $km = 10;

// se l'utente ha dato posizione valida -> calcolo distanza
$useGeo = ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng));
$lat = (float)$lat;
$lng = (float)$lng;

if ($useGeo) {
  // Haversine (km)
  $sql = "
    SELECT
      u.id, u.name, u.city, u.phone,
      p.canton, p.residence, p.languages, p.bio, p.radius_km,
      p.profile_image, p.lat, p.lng,
      (6371 * acos(
        cos(radians(?)) * cos(radians(p.lat)) *
        cos(radians(p.lng) - radians(?)) +
        sin(radians(?)) * sin(radians(p.lat))
      )) AS distance_km
    FROM users u
    JOIN profiles p ON p.user_id = u.id
    WHERE u.role = 'hairdresser'
      AND u.email_verified_at IS NOT NULL
      AND p.lat IS NOT NULL AND p.lng IS NOT NULL
  ";

  $params = [$lat, $lng, $lat];
  $types = "ddd";

  if ($canton !== '') {
    $sql .= " AND p.canton = ? ";
    $params[] = $canton;
    $types .= "s";
  }

  $sql .= " HAVING distance_km <= ? ORDER BY distance_km ASC LIMIT 200";
  $params[] = $km;
  $types .= "i";

  $stmt = $m->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} else {
  // senza posizione: filtro per cantone e basta
  $sql = "
    SELECT
      u.id, u.name, u.city, u.phone,
      p.canton, p.residence, p.languages, p.bio, p.radius_km,
      p.profile_image
    FROM users u
    JOIN profiles p ON p.user_id = u.id
    WHERE u.role = 'hairdresser'
      AND u.email_verified_at IS NOT NULL
  ";

  $params = [];
  $types = "";

  if ($canton !== '') {
    $sql .= " AND p.canton = ? ";
    $params[] = $canton;
    $types .= "s";
  }

  $sql .= " ORDER BY u.id DESC LIMIT 200";

  $stmt = $m->prepare($sql);
  if ($types !== "") $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Risultati ricerca</title>
  <link rel="stylesheet" href="../styles.css">
</head>
<body>
  <div class="container" style="max-width:1000px">
    <div class="card">
      <div class="section-title">Risultati ricerca</div>

      <?php if (!$rows): ?>
        <p class="p">Nessun parrucchiere trovato.</p>
        <a class="btn secondary" href="../search.html">Torna alla ricerca</a>

      <?php else: ?>
        <div class="list">
          <?php foreach ($rows as $r): ?>
            <div class="card" style="margin:10px 0">
              <div style="display:flex;gap:12px;align-items:center">

                <?php if (!empty($r['profile_image'])): ?>
                  <img src="../<?= htmlspecialchars($r['profile_image']) ?>"
                       style="width:60px;height:60px;border-radius:16px;object-fit:cover">
                <?php else: ?>
                  <div style="width:60px;height:60px;border-radius:16px;background:#eee"></div>
                <?php endif; ?>

                <div style="flex:1">
                  <div style="font-size:18px"><b><?= htmlspecialchars($r['name']) ?></b></div>

                  <div class="small">
                    <?= htmlspecialchars($r['city'] ?? '') ?>
                    <?php if (!empty($r['canton'])): ?> • <?= htmlspecialchars($r['canton']) ?><?php endif; ?>
                    <?php if (isset($r['distance_km'])): ?>
                      • <b><?= number_format((float)$r['distance_km'], 1) ?> km</b>
                    <?php endif; ?>
                  </div>

                  <?php if (!empty($r['languages'])): ?>
                    <div class="small">Lingue: <?= htmlspecialchars($r['languages']) ?></div>
                  <?php endif; ?>

                  <?php if (!empty($r['bio'])): ?>
                    <div class="small" style="margin-top:6px"><?= nl2br(htmlspecialchars($r['bio'])) ?></div>
                  <?php endif; ?>
                </div>

                <div style="display:flex;flex-direction:column;gap:8px">
                  <a class="btn primary" href="../profile-view.php?id=<?= (int)$r['id'] ?>">Vedi profilo</a>
                  <a class="btn secondary" href="../messages.php?to_user_id=<?= (int)$r['id'] ?>">Scrivi</a>
                </div>

              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <a class="btn secondary" href="../search.html">Nuova ricerca</a>
      <?php endif; ?>

    </div>
  </div>
</body>
</html>
