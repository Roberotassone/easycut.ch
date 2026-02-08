<?php
$bootstrapPath = __DIR__ . '/bootstrap.php';
if (!file_exists($bootstrapPath)) {
  $bootstrapPath = __DIR__ . '/api/bootstrap.php';
}
require_once $bootstrapPath;

$dbPath = __DIR__ . '/db.php';
if (!file_exists($dbPath)) {
  $dbPath = __DIR__ . '/api/db.php';
}
require_once $dbPath;

if (empty($_SESSION['user_id'])) { header("Location: login.html"); exit; }

$m = db();
$userId = (int)$_SESSION['user_id'];

$stmt = $m->prepare("
  SELECT u.id,u.role,u.name,u.email,u.phone,u.city,
         p.age,p.residence,p.canton,p.languages,p.bio,p.radius_km,p.profile_image,p.lat,p.lng
  FROM users u
  LEFT JOIN profiles p ON p.user_id=u.id
  WHERE u.id=?
  LIMIT 1
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();

$role = $me['role'] ?? 'client';
?>
<!doctype html>
<html lang="it">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Profilo</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container" style="max-width:900px">
  <div class="card">
    <div class="section-title">Il mio profilo</div>

    <?php if (!empty($_GET['saved'])) { ?>
      <div class="card" style="margin:10px 0;background:#f0fff3">
        <b>✅ Salvato!</b>
      </div>
    <?php } ?>

    <div style="display:flex;gap:14px;align-items:center;margin-bottom:14px">
      <?php if (!empty($me['profile_image'])) { ?>
        <img src="<?php echo htmlspecialchars($me['profile_image']); ?>" style="width:90px;height:90px;border-radius:22px;object-fit:cover">
      <?php } else { ?>
        <div style="width:90px;height:90px;border-radius:22px;background:#eee"></div>
      <?php } ?>

      <div style="flex:1">
        <div style="font-size:20px"><b><?php echo htmlspecialchars($me['name'] ?? ''); ?></b></div>
        <div class="small"><?php echo htmlspecialchars($me['email'] ?? ''); ?></div>
        <div class="small">Ruolo: <b><?php echo htmlspecialchars($role); ?></b></div>
      </div>

      <div>
        <a class="btn secondary" href="dashboard.php">Dashboard</a>
        <a class="btn ghost" href="logout.php">Esci</a>
      </div>
    </div>

    <form class="list" action="profile-save.php" method="POST" enctype="multipart/form-data">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <div class="small">Nome</div>
          <input name="name" value="<?php echo htmlspecialchars($me['name'] ?? ''); ?>" required>
        </div>

        <div>
          <div class="small">Telefono</div>
          <input name="phone" value="<?php echo htmlspecialchars($me['phone'] ?? ''); ?>" required>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <div class="small">Città</div>
          <input name="city" value="<?php echo htmlspecialchars($me['city'] ?? ''); ?>" required>
        </div>

        <div>
          <div class="small">Cantone</div>
          <select name="canton" required>
            <?php
              $cantons = ["","AG","AI","AR","BE","BL","BS","FR","GE","GL","GR","JU","LU","NE","NW","OW","SG","SH","SO","SZ","TG","TI","UR","VD","VS","ZG","ZH"];
              foreach($cantons as $c){
                $sel = (($me['canton'] ?? '') === $c) ? 'selected' : '';
                $label = $c==='' ? 'Seleziona cantone' : $c;
                echo "<option value=\"".htmlspecialchars($c)."\" $sel>$label</option>";
              }
            ?>
          </select>
        </div>
      </div>

      <div>
        <div class="small">Immagine profilo (JPG/PNG/WebP)</div>
        <input type="file" name="profile_image" accept="image/*">
      </div>

      <?php if ($role === 'hairdresser') { ?>
        <div class="card" style="margin:12px 0">
          <b>Parrucchiere freelance</b>
          <div class="small">Questi dati servono per farti trovare in ricerca (cantone + km).</div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <div class="small">Età (min 18)</div>
            <input type="number" min="18" name="age" value="<?php echo (int)($me['age'] ?? 18); ?>" required>
          </div>

          <div>
            <div class="small">Lingue parlate</div>
            <input name="languages" value="<?php echo htmlspecialchars($me['languages'] ?? ''); ?>" required>
          </div>
        </div>

        <div>
          <div class="small">Descrizione</div>
          <textarea name="bio" rows="3"><?php echo htmlspecialchars($me['bio'] ?? ''); ?></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <div class="small">Raggio (km)</div>
            <select name="radius_km" required>
              <?php
                $rads=[5,10,25,50,100];
                $cur=(int)($me['radius_km'] ?? 10);
                foreach($rads as $r){
                  $sel = ($cur===$r) ? 'selected' : '';
                  echo "<option value=\"$r\" $sel>$r</option>";
                }
              ?>
            </select>
          </div>

          <div>
            <div class="small">Posizione (lat / lng)</div>
            <input id="lat" name="lat" value="<?php echo htmlspecialchars($me['lat'] ?? ''); ?>" placeholder="lat" required>
            <input id="lng" name="lng" value="<?php echo htmlspecialchars($me['lng'] ?? ''); ?>" placeholder="lng" required style="margin-top:8px">
            <button class="btn secondary" type="button" id="geoBtn" style="margin-top:10px">Usa la mia posizione</button>
          </div>
        </div>
      <?php } else { ?>
        <!-- client -->
        <input type="hidden" name="age" value="0">
        <input type="hidden" name="languages" value="">
        <input type="hidden" name="bio" value="">
        <input type="hidden" name="radius_km" value="10">
        <input type="hidden" name="lat" value="">
        <input type="hidden" name="lng" value="">
      <?php } ?>

      <button class="btn primary" type="submit">Salva profilo</button>
    </form>

  </div>
</div>

<?php if ($role === 'hairdresser') { ?>
<script>
document.getElementById('geoBtn').addEventListener('click', () => {
  if (!navigator.geolocation) return alert("Geolocalizzazione non supportata.");
  navigator.geolocation.getCurrentPosition((pos) => {
    document.getElementById('lat').value = pos.coords.latitude.toFixed(7);
    document.getElementById('lng').value = pos.coords.longitude.toFixed(7);
  }, () => alert("Permesso posizione negato."));
});
</script>
<?php } ?>

</body>
</html>
