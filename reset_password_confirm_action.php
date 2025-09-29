<?php
if (!@$_POST["id"])
  return "Player id not provided";

$user = query("SELECT * FROM im_user WHERE id=".escape($_POST["id"]))->fetch_assoc();
if (!$user)
  return  "User doesn't exist";

if (!@$user["reset_password_secret"])
  return "User doesn't have pending password reset.";

if (!@$user["reset_password_timestamp"])
  return "This shouldn't happen.";

if (!@$user["reset_password_timestamp"])
{
  $difference_in_seconds = time() - strtotime($user["reset_password_timestamp"]);
  if ($difference_in_seconds > 60 * 60)
    return "The password reset request timed out, it only lasts for 1 hour.";
}

if ($user["reset_password_secret"] != $_POST["secret"])
  return "Wrong secret.";

query("UPDATE
         im_user
     SET
       password=".escape(password_hash($_POST["password"], PASSWORD_DEFAULT)).",
       reset_password_secret = NULL,
       reset_password_timestamp = NULL
     WHERE id=".escape($user["id"]));
redirectWithMessageCustom("/", "Password successfully changed.");
?>
