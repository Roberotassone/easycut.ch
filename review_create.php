<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_role('client');

$u = current_user();
$booking_id = (int)($_POST['booking_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if($rating<1 || $rating>5){ http_response_code(400); exit('BAD_RATING'); }

$stmt = db()->prepare("SELECT id,hairdresser_id,client_id,status FROM bookings WHERE id=? AND client_id=? AND status IN ('done','paid','confirmed')");
$stmt->bind_param("ii",$booking_id,$u['id']);
$stmt->execute();
$b = $stmt->get_result()->fetch_assoc();
if(!$b){ http_response_code(404); exit('BOOKING_NOT_ALLOWED'); }

$ins = db()->prepare("INSERT INTO reviews (booking_id,hairdresser_id,client_id,rating,comment) VALUES (?,?,?,?,?)");
$ins->bind_param("iiiis",$booking_id,$b['hairdresser_id'],$u['id'],$rating,$comment);
$ins->execute();

header('Location: /public/profile.php?id='.$b['hairdresser_id']);
