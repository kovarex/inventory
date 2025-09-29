<?php
function generateAddress($url, $get)
{
  if (empty($get))
    return $url;
  $parameters = "";
  foreach($get as $key =>$value)
  {
    if (empty($key))
      continue; // php are you drunk?
    if (empty($parameters))
      $parameters .= "?";
    else
      $parameters .= "&amp;";
    $parameters .= urlencode($key)."=".urlencode($value);
  }
  return $url.$parameters;
}

function redirectWithMessageCustom($redirect, $message)
{
  if (!isset($redirect))
    die("Redirect address not provided.");
  $parameterDelimiter = "?";
  if (str_contains($redirect, "?"))
    $parameterDelimiter = "&";
  header("Location: ".$redirect.$parameterDelimiter."message=".urlencode($message));
  die();
}

function redirectWithMessage($message)
{
  $redirect = @$_POST["redirect"];
  if (!$redirect)
    $redirect = @$_GET["redirect"];
  redirectWithMessageCustom($redirect, $message);
}

function redirect($target)
{
  header("Location: ".$target);
  die();
}

function locationLink($locationID, $locationName)
{
  return "<a href=\"location?id=$locationID\">$locationName</a>";
}

function itemLink($itemID, $itemName)
{
  return "<a href=\"item?id=$itemID\">$itemName</a>";
}

function categoryLink($categoryID, $categoryName)
{
  return "<a href=\"category?id=$categoryID\">$categoryName</a>";
}

function userLink($id, $name)
{
  return "<a href=\"user?id=$id\">$name</a>";
}

function itemImage($id, $generate, $type = "thumbnail")
{
  if (!$generate)
    return "";
  return "<img src=\"image?source=item&id=".$id."&type=".$type."\"/>";
}

function locationImage($id, $generate, $type = "thumbnail")
{
  if (!$generate)
    return "";
  return "<img src=\"image?source=location&id=".$id."&type=".$type."\"/>";
}

?>
