<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) { header("Location: ../login.html"); exit; }

$m = db();
$userId = (int)$_SESSION['user_id'];

// prendo ruolo dal DB per evitare trucchi
$stmt = $m->prepare("SELECT role FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$me = $stmt->get_result()->fetch_assoc();
$role = $me['role'] ?? 'client';

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$city = trim($_POST['city'] ?? '');
$canton = strtoupper(trim($_POST['canton'] ?? ''));

$age = (int)($_POST['age'] ?? 0);
$languages = trim($_POST['languages'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$radius_km = (int)($_POST['radius_km'] ?? 10);

$lat = trim($_POST['lat'] ?? '');
$lng = trim($_POST['lng'] ?? '');

if ($name==='' || $phone==='' || $city==='' || $canton==='') {
  die("Campi obbligatori mancanti.");
}

if ($role === 'hairdresser') {
  if ($age < 18) die("Età minima 18.");
  if ($languages==='') die("Lingue obbligatorie.");
  if ($radius_km <= 0) $radius_km = 10;
  if ($lat==='' || $lng==='' || !is_numeric($lat) || !is_numeric($lng)) {
    die("Posizione non valida. Premi 'Usa la mia posizione'.");
  }
} else {
  // client: pulisco
  $age = 0; $languages = ''; $bio=''; $radius_km = 10; $lat=''; $lng='';
}

// upload immagine
$imgPath = null;

if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
  $tmp = $_FILES['profile_image']['tmp_name'];
  $size = (int)$_FILES['profile_image']['size'];

  if ($size > 3 * 1024 * 1024) die("Immagine troppo grande (max 3MB).");

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = finfo_file($finfo, $tmp);
  finfo_close($finfo);

  $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  if (!isset($allowed[$mime])) die("Formato immagine non valido.");

  $ext = $allowed[$mime];
  $dir = __DIR__ . '/../uploads';
  if (!is_dir($dir)) @mkdir($dir, 0755, true);

  $filename = 'u'.$userId.'_'.time().'.'.$ext;
  $dest = $dir . '/' . $filename;

  if (!move_uploaded_file($tmp, $dest)) die("Errore upload file.");

  $imgPath = 'uploads/' . $filename;
}

// aggiorna users
$stmt = $m->prepare("UPDATE users SET name=?, phone=?, city=? WHERE id=?");
$stmt->bind_param("sssi", $name, $phone, $city, $userId);
$stmt->execute();

// profile: se esiste update, altrimenti insert
$stmt = $m->prepare("SELECT user_id FROM profiles WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();

if ($exists) {
  if ($imgPath) {
    $stmt = $m->prepare("
      UPDATE profiles
      SET age=?, residence=?, canton=?, languages=?, bio=?, radius_km=?, lat=?, lng=?, profile_image=?
      WHERE user_id=?
    ");
    $residence = $city;
    $latF = ($lat==='') ? null : (float)$lat;
    $lngF = ($lng==='') ? null : (float)$lng;
    $stmt->bind_param("issssiddsi", $age, $residence, $canton, $languages, $bio, $radius_km, $latF, $lngF, $imgPath, $userId);
  } else {
    $stmt = $m->prepare("
      UPDATE profiles
      SET age=?, residence=?, canton=?, languages=?, bio=?, radius_km=?, lat=?, lng=?
      WHERE user_id=?
    ");
    $residence = $city;
    $latF = ($lat==='') ? null : (float)$lat;
    $lngF = ($lng==='') ? null : (float)$lng;
    $stmt->bind_param("issssiddi", $age, $residence, $canton, $languages, $bio, $radius_km, $latF, $lngF, $userId);
  }
  $stmt->execute();

} else {
  $residence = $city;
  $latF = ($lat==='') ? null : (float)$lat;
  $lngF = ($lng==='') ? null : (float)$lng;
  $img = $imgPath ?? '';

  $stmt = $m->prepare("
    INSERT INTO profiles (user_id, age, residence, canton, languages, bio, radius_km, lat, lng, profile_image)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  $stmt->bind_param("iissssiddds", $userId, $age, $residence, $canton, $languages, $bio, $radius_km, $latF, $lngF, $img);
  $stmt->execute();
}

header("Location: ../profile.php?saved=1");
exit;
