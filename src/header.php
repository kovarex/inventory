<?php
require("src/db.php");
require("src/auth.php");
require("src/header_internal.php");

assert(!empty($_SESSION["user"]));
?>

<div style="position: absolute;right: 0px;">
  Currently logged in as <?=$_SESSION["user"]["username"]?>
  <form method="post" action="login.php" style="display:inline;">
    <input type="submit" value="Logoff"/>
    <input type="hidden" name="action" value="logoff"/>
  </form>
</div>
