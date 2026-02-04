<?php
require_once __DIR__ . '/config.php';

function send_mail_smtp(string $to, string $subject, string $text): bool {
  if (!SMTP_HOST || !SMTP_USER || !SMTP_PASS) return false;

  $host = SMTP_HOST;
  $port = SMTP_PORT;

  $from = SMTP_FROM;
  $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'EasyCut';

  $socket = fsockopen($host, $port, $errno, $errstr, 20);
  if (!$socket) return false;

  $read = function() use ($socket) { return fgets($socket, 515); };
  $write = function($cmd) use ($socket) { fwrite($socket, $cmd . "\r\n"); };

  $read();
  $write("EHLO " . $host); $read(); $read(); $read(); $read();

  $write("STARTTLS"); $read();
  if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) return false;

  $write("EHLO " . $host); $read(); $read(); $read(); $read();

  $write("AUTH LOGIN"); $read();
  $write(base64_encode(SMTP_USER)); $read();
  $write(base64_encode(SMTP_PASS)); $read();

  $write("MAIL FROM:<$from>"); $read();
  $write("RCPT TO:<$to>"); $read();
  $write("DATA"); $read();

  $headers = [];
  $headers[] = "From: {$fromName} <{$from}>";
  $headers[] = "To: <{$to}>";
  $headers[] = "Subject: " . $subject;
  $headers[] = "MIME-Version: 1.0";
  $headers[] = "Content-Type: text/plain; charset=utf-8";

  $data = implode("\r\n", $headers) . "\r\n\r\n" . $text . "\r\n.";
  $write($data); $read();

  $write("QUIT"); $read();
  fclose($socket);
  return true;
}
