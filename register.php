<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$city = trim($_POST['city'] ?? '');
$canton = strtoupper(trim($_POST['canton'] ?? ''));
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'client';
$accept = $_POST['accept'] ?? null;

// hairdresser extra
$age = (int)($_POST['age'] ?? 0);
$languages = trim($_POST['languages'] ?? '');
$bio = trim($_POST['bio'] ?? '');

if (!$accept || $name==='' || $email==='' || $phone==='' || $city==='' || $canton==='' || $password==='') {
  header("Location: ../register.html");
  exit;
}

if (!in_array($role, ['client','hairdresser'], true)) $role = 'client';
if ($role === 'hairdresser') {
  if ($age < 18) { die("Devi avere almeno 18 anni."); }
  if ($languages === '') { die("Inserisci le lingue parlate."); }
}

$m = db();

// email unica
$stmt = $m->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
  header("Location: ../login.html?exists=1");
  exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

// crea utente (NON verificato)
$stmt = $m->prepare("
  INSERT INTO users (role, name, email, password_hash, phone, city, email_verified_at)
  VALUES (?, ?, ?, ?, ?, ?, NULL)
");
$stmt->bind_param("ssssss", $role, $name, $email, $hash, $phone, $city);
$stmt->execute();
$userId = (int)$stmt->insert_id;

// profilo (sempre)
$residence = $city;
$stmt = $m->prepare("
  INSERT INTO profiles (user_id, age, residence, canton, languages, bio, radius_km)
  VALUES (?, ?, ?, ?, ?, ?, 10)
");
$stmt->bind_param("iissss", $userId, $age, $residence, $canton, $languages, $bio);
$stmt->execute();

// token conferma email
$token = bin2hex(random_bytes(32));
$stmt = $m->prepare("INSERT INTO email_verifications (user_id, token) VALUES (?, ?)");
$stmt->bind_param("is", $userId, $token);
$stmt->execute();

$verifyLink = SITE_URL . "/api/verify-email.php?token=" . $token;

// EMAIL conferma
$subject = "Conferma la tua email — EasyCut";
$message =
"Ciao $name,\n\n".
"Grazie per esserti registrato su EasyCut.\n".
"Per attivare il tuo account devi confermare l'indirizzo email cliccando qui:\n\n".
"$verifyLink\n\n".
"Senza conferma NON potrai accedere.\n\n".
"EasyCut\n";

send_mail_smtp($email, $subject, $message);

// vai alla login con messaggio "controlla la mail"
header("Location: ../login.html?verify=1");
exit;
