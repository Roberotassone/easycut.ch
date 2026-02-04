<?php
// Minimal front controller to avoid 403 and keep pretty URLs.
// If a real file exists, let the server handle it.
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = rtrim($path, '/');
if ($path === '') $path = '/index.html';
$file = __DIR__ . $path;

if (is_file($file)) {
  $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
  if ($ext === 'php') { require $file; exit; }
  if ($ext === 'html') { header('Content-Type: text/html; charset=utf-8'); readfile($file); exit; }
  readfile($file); exit;
}
http_response_code(404);
echo "404";
