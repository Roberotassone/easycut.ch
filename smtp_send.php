<?php
function smtp_read_all($fp){
  $data = "";
  while ($line = fgets($fp, 515)) {
    $data .= $line;
    if (preg_match('/^\d{3} /', $line)) break;
  }
  return $data;
}
function smtp_expect($fp, $code){
  $r = smtp_read_all($fp);
  if (substr($r,0,3) != (string)$code) return $r;
  return true;
}

function smtp_send_tls($host, $port, $user, $pass, $fromEmail, $fromName, $toEmail, $subject, $bodyText){
  $fp = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 20);
  if (!$fp) return "CONNECT_FAIL: $errstr";

  $r = smtp_expect($fp, 220); if ($r !== true) return "GREET_FAIL: $r";

  fwrite($fp, "EHLO localhost\r\n");
  $r = smtp_expect($fp, 250); if ($r !== true) return "EHLO_FAIL: $r";

  fwrite($fp, "STARTTLS\r\n");
  $r = smtp_expect($fp, 220); if ($r !== true) return "STARTTLS_FAIL: $r";

  if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
    return "TLS_ENABLE_FAIL";
  }

  fwrite($fp, "EHLO localhost\r\n");
  $r = smtp_expect($fp, 250); if ($r !== true) return "EHLO2_FAIL: $r";

  fwrite($fp, "AUTH LOGIN\r\n");
  $r = smtp_expect($fp, 334); if ($r !== true) return "AUTH_FAIL: $r";

  fwrite($fp, base64_encode($user)."\r\n");
  $r = smtp_expect($fp, 334); if ($r !== true) return "USER_FAIL: $r";

  fwrite($fp, base64_encode($pass)."\r\n");
  $r = smtp_expect($fp, 235); if ($r !== true) return "PASS_FAIL: $r";

  fwrite($fp, "MAIL FROM:<$fromEmail>\r\n");
  $r = smtp_expect($fp, 250); if ($r !== true) return "MAILFROM_FAIL: $r";

  fwrite($fp, "RCPT TO:<$toEmail>\r\n");
  $r = smtp_expect($fp, 250); if ($r !== true) return "RCPTTO_FAIL: $r";

  fwrite($fp, "DATA\r\n");
  $r = smtp_expect($fp, 354); if ($r !== true) return "DATA_FAIL: $r";

  $headers =
    "From: ".mb_encode_mimeheader($fromName,"UTF-8")." <{$fromEmail}>\r\n".
    "To: <{$toEmail}>\r\n".
    "Subject: ".mb_encode_mimeheader($subject,"UTF-8")."\r\n".
    "MIME-Version: 1.0\r\n".
    "Content-Type: text/plain; charset=UTF-8\r\n".
    "Content-Transfer-Encoding: 8bit\r\n";

  $msg = $headers . "\r\n" . $bodyText . "\r\n.\r\n";
  fwrite($fp, $msg);

  $r = smtp_expect($fp, 250); if ($r !== true) return "BODY_FAIL: $r";

  fwrite($fp, "QUIT\r\n");
  fclose($fp);
  return true;
}
