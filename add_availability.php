<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_role('hairdresser');

$u = current_user();
$start = trim($_POST['start_at'] ?? '');
$end = trim($_POST['end_at'] ?? '');

if(!$start || !$end){ http_response_code(400); exit('BAD_INPUT'); }

$stmt = db()->prepare("INSERT INTO availability (hairdresser_id,start_at,end_at) VALUES (?,?,?)");
$stmt->bind_param("iss", $u['id'], $start, $end);
$stmt->execute();

header('Location: /public/dashboard.php');
