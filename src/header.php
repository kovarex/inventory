<?php
require_once("link_helper.php");
require_once("header_internal.php");

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
echo "<div class=\"centered-div\">\n";
echo "<div class=\"centered-div\">\n";
echo "<a href=\"/\">Home</a>\n";
echo "&nbsp;&nbsp;:&nbsp;&nbsp;<a href=\"/items\">Items</a>\n";
echo "&nbsp;&nbsp;:&nbsp;&nbsp;<a href=\"/homes\">Homes</a>\n";
echo "&nbsp;&nbsp;:&nbsp;&nbsp;<a href=\"/categories\">Categories</a>\n";
echo "&nbsp;&nbsp;:&nbsp;&nbsp;<a href=\"/locations\">Locations</a>\n";
echo "&nbsp;&nbsp;:&nbsp;&nbsp;<a href=\"/users\">Users</a>\n";
echo "&nbsp;&nbsp;:&nbsp;&nbsp;<a href=\"/transactions\">Transactions</a>\n";
echo "</div>";
echo "<br/><br/>\n";
?>
