<?php
function locationLink($locationID, $locationName)
{
  return "<a href=\"location.php?id=$locationID\">$locationName</a>";
}

function itemLink($itemID, $itemName)
{
  return "<a href=\"item.php?id=$itemID\">$itemName</a>";
}
?>