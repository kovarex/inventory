<?php
require_once("src/db.php");
require_once("src/auth.php");
require("src/link_helper.php");
require("src/header_internal.php");

assert(!empty($_SESSION["user"]));
?>

<div style="position: absolute;right: 0px;">
  Currently logged in as <?=$_SESSION["user"]["username"]?> in <?=empty($_SESSION["home"]) ? "no home" : $_SESSION["home"]["name"] ?>
  <form method="post" action="login.php" style="display:inline;">
    <input type="submit" value="Logoff"/>
    <input type="hidden" name="action" value="logoff"/>
  </form>
</div>

<?php
if (@$hideIndexLink !== true)
  echo "<div><a href='index.php'/>Home</a></div>";
?>
