<?php
session_start();

function current_user() {
  return $_SESSION['user'] ?? null;
}

function require_login() {
  if (!current_user()) { http_response_code(401); exit('NOT_AUTH'); }
}

function require_role($role) {
  require_login();
  if (current_user()['role'] !== $role) { http_response_code(403); exit('FORBIDDEN'); }
}

// Best-practice headers (minimi)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
