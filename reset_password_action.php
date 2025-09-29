<?php
if (!@$_POST["email"])
  redirectWithMessageCustom("/reset_password", "Email not provided");

if (empty($_POST["email"]))
  redirectWithMessageCustom("/reset_password", "Email not provided");

$user = query("SELECT * from im_user where email=".escape($_POST["email"]))->fetch_assoc();
if (!$user)
  redirectWithMessageCustom("/reset_password", "Player with email ".$_POST["email"]." doesn't exist.");

$resetTimestamp = $user["reset_password_timestamp"];
if ($resetTimestamp)
{
  $difference_in_seconds = time() - strtotime($resetTimestamp);
  if ($difference_in_seconds < 60 * 10)
    redirectWithMessageCustom("/reset_password", "Reset password to the email ".$_POST["email"]." was ordered recentry (".$difference_in_seconds." seconds).<br/>\n The minimum time between resets is 10 minutes.");
}

$secret = rand();
query("UPDATE im_user SET reset_password_secret=".escape($secret).", reset_password_timestamp=now() WHERE id=".escape($user["id"]));

$message = "You have requested to reset your password<br/>\n";
$message .= "Follow this <a href=\"https://".$_SERVER['HTTP_HOST']."/reset_password_confirm?id=".$user["id"]."&secret=".$secret."\">link</a> to proceed.<br/>\n";
$message .= "If you didn't order the reset, you can ignore the email.";

$from = 'webmaster@gorating.com';
$headers = '';
$headers .= 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
$headers .= 'From: ' . $from . ' ' . "\r\n";
$headers .= 'Reply-To: webmaster@gorating.com\r\n';
$headers .= 'X-Mailer: PHP/' . phpversion();

mail($_POST["email"], "im.kovarex password reset", $message, $headers);

redirectWithMessageCustom("/", "Password reset link sent.");
?>
