<?php
if (!empty($_SESSION["user"]))
{
  echo "Currently logged in as ".$_SESSION["user"]["username"];
  echo "<form method=\"post\">";
  echo "<input type=\"submit\" value=\"Logoff\"/>";
  echo "</form>";
  return;
}?>

<form method="post" action="/login_action">
  <table>
    <tr>
      <td><label for="username">Username:</label></td>
      <td><input name="username" type="text"/></td>
    </tr>
    <tr>
      <td><label for="password">Password:</label></td>
      <td><input name="password" type="password"/></td>
    </tr>
  </table>
  <input type="submit" value="Login"/>
</form>

<form method="get" action="register">
  <input type="submit" value="Register"/>
</form>
