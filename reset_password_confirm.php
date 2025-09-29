<?php
if (!@$_GET["id"])
  return "Player id not provided";

$user = query("SELECT * FROM im_user WHERE id=".escape($_GET["id"]))->fetch_assoc();
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

if ($user["reset_password_secret"] != $_GET["secret"])
  return "Wrong secret.";

echo "Now you can set a new password:<br/>\n";
echo "<form method=\"post\" action=\"reset_password_confirm_action\">\n";
echo "<table>\n";
echo "<tr><td><label for=\"password\"/>New password:</label></td><td><input type=\"password\" id=\"password\" name=\"password\"/></td></tr><br/>\n";
echo "</table>\n";
echo "<input type=\"submit\" value=\"Reset\"/>";
echo "<input type=\"hidden\" name=\"id\" value=\"".$user["id"]."\"/>";
echo "<input type=\"hidden\" name=\"secret\" value=\"".$user["reset_password_secret"]."\"/>";
echo "</form>\n";
?>
