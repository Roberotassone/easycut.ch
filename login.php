<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
  header("Location: ../login.html?bad=1");
  exit;
}

$m = db();

$stmt = $m->prepare("SELECT id, role, password_hash, email_verified_at FROM users WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !password_verify($password, $user['password_hash'])) {
  header("Location: ../login.html?bad=1");
  exit;
}

if (empty($user['email_verified_at'])) {
  header("Location: ../login.html?needverify=1");
  exit;
}

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['role'] = $user['role'];

header("Location: ../dashboard.php");
exit;
