<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

$to = $_GET['to'] ?? '';
if (!$to) { echo "Usa: ?to=la_tua_email"; exit; }

$ok = send_mail_smtp($to, "Test EasyCut SMTP", "Se leggi questo, SMTP funziona ✅");
echo $ok ? "✅ SMTP OK" : "❌ SMTP FAIL";
