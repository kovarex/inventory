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

function userLink($id, $name)
{
  return "<a href=\"user.php?id=$id\">$name</a>";
}

function itemImage($id, $generate, $type = "thumbnail")
{
  if (!$generate)
    return "";
  return "<img src=\"image.php?source=item&id=".$id."&type=".$type."\"/>";
}

function locationImage($id, $generate, $type = "thumbnail")
{
  if (!$generate)
    return "";
  return "<img src=\"image.php?source=location&id=".$id."&type=".$type."\"/>";
}

?>
