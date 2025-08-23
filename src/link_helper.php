<?php
function locationLink($locationID, $locationName)
{
  return "<a href=\"location.php?id=$locationID\">$locationName</a>";
}

function itemLink($itemID, $itemName)
{
  return "<a href=\"item.php?id=$itemID\">$itemName</a>";
}

function categoryLink($categoryID, $categoryName)
{
  return "<a href=\"category.php?id=$categoryID\">$categoryName</a>";
}

function itemImage($id, $generate)
{
  if (!$generate)
    return "";
  return "<img src=\"image.php?source=item&id=".$id."&type=thumbnail\"/>";
}

?>
