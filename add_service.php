<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_role('hairdresser');

$u = current_user();
$title = trim($_POST['title'] ?? '');
$price = (float)($_POST['price_chf'] ?? 0);
$dur = (int)($_POST['duration_min'] ?? 0);

if(!$title || $price<=0 || $dur<=0){ http_response_code(400); exit('BAD_INPUT'); }

$stmt = db()->prepare("INSERT INTO services (hairdresser_id,title,price_chf,duration_min) VALUES (?,?,?,?)");
$stmt->bind_param("isdi", $u['id'], $title, $price, $dur);
$stmt->execute();

header('Location: /public/dashboard.php');
