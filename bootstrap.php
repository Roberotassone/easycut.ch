<?php
// session folder locale
$sessionPath = __DIR__ . '/../sessions';
if (!is_dir($sessionPath)) {
  @mkdir($sessionPath, 0755, true);
}
ini_set('session.save_path', $sessionPath);

session_start();
