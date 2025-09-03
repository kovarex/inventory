<?php
require("src/auth.php");
require("src/db.php");
require("src/location_helper.php");
require("src/image_upload_helper.php");

$queryRightCheck = " and home_id=".homeID();
$imageData = tryToProcessImageUpload();

$oldParentLocation = query("SELECT parent_location_id FROM im_location WHERE id=".escape($_POST["id"]))->fetch_assoc()["parent_location_id"];
$locationChildren = locationChildrenFlat(escape($_POST["id"]));
$validMove = true;
if ($_POST["parent_id"] == $_POST["id"])
{
  $validMove = false;
  echo "<div>Can't move location to itself!</div>";
}
if ($validMove)
{
  foreach ($locationChildren as $child)
    if ($child == $_POST["parent_id"])
    {
      $validMove = false;
      echo "<div>Can't move location to its own child!</div>";
    }
}
if ($validMove)
{
  $updated = query("UPDATE im_location SET
                    name=".escape($_POST["name"]).",
                    description=".escape($_POST["description"]).",
                    parent_location_id=".escape($_POST["parent_id"]).
                    (isset($imageData) ? ",image=X".escape($imageData["big"]) : "").
                    (isset($imageData) ? ",thumbnail=X".escape($imageData["thumbnail"]) : "").
                   " WHERE id=".escape($_POST["id"]).$queryRightCheck, true);
  if ($updated && $oldParentLocation != $_POST["parent_id"])
    locationMoved($_POST["id"], $oldParentLocation, $_POST["parent_id"], $_POST["comment"], $locationChildren);
}

header("Location: ".$_POST["redirect"]);
?>
