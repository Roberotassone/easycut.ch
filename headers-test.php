<?php
ob_start();
session_start();

header('Content-Type: text/plain; charset=utf-8');

echo "headers_sent? ";
var_export(headers_sent($file, $line));
echo "\n";
if (headers_sent()) {
  echo "SENT IN: $file:$line\n";
} else {
  echo "OK: headers not sent yet\n";
}

header("Location: https://www.easycut.ch/PING.html", true, 302);
exit;
