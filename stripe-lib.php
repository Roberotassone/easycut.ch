<?php
require_once __DIR__ . '/config.php';

function stripe_request(string $method, string $path, array $data = []): array {
  $url = "https://api.stripe.com" . $path;

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . STRIPE_SECRET,
    "Content-Type: application/x-www-form-urlencoded"
  ]);

  if ($method !== 'GET') {
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
  } else if (!empty($data)) {
    curl_setopt($ch, CURLOPT_URL, $url . "?" . http_build_query($data));
  }

  $resp = curl_exec($ch);
  $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err = curl_error($ch);
  curl_close($ch);

  if ($resp === false) {
    throw new Exception("Stripe CURL error: " . $err);
  }

  $json = json_decode($resp, true);
  if ($http >= 400) {
    $msg = $json['error']['message'] ?? $resp;
    throw new Exception("Stripe API error ($http): " . $msg);
  }

  return $json;
}

function money_to_cents($amount): int {
  return (int) round(((float)$amount) * 100);
}
