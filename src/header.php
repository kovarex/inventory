<?php
require_once("header_internal.php");

echo "<div class=\"top-user\">";
if (@$_SESSION["user"])
{
  if (!empty($_SESSION["home"]))
    echo "<form method=\"get\" action=\"/items\" class=\"header-search\">".
         "<input type=\"text\" name=\"search\" placeholder=\"Search items\" value=\"".htmlspecialchars(@$_GET["search"] ?? "")."\"/>".
         "<input type=\"hidden\" name=\"action\" value=\"search\"/>".
         "<input type=\"submit\" value=\"Search\"/></form>";
  echo "Currently logged in as ".$_SESSION["user"]["username"]." in ".(empty($_SESSION["home"]) ? "no home" : $_SESSION["home"]["name"]);
  echo "<form method=\"post\" action=\"logoff_action\" style=\"display:inline;\">";
  echo "<input type=\"submit\" value=\"Logoff\"/>";
  echo "</form>";
}
else
  echo "Currently not logged in";

echo "</div>";
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
