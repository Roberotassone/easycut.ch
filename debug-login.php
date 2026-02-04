<?php
session_start();
$_SESSION['user_id'] = 123;
$_SESSION['role'] = 'client';
header("Location: ../dashboard.php");
exit;
