<?php
require_once __DIR__ . '/config.php';

function db(): mysqli {
  static $m;
  if ($m) return $m;

  mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
  $m = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
  $m->set_charset('utf8mb4');
  return $m;
}
