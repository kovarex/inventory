<?php
require("src/auth.php");
require("src/db.php");

if (!isset($_GET["source"]))
  die("Source of the image not provided");

if ($_GET["source"] == "item")
  $sourceTableName = "im_item";
else
  die("Unknown source type: ".$_GET["source"]);

if (!isset($_GET["id"]))
  die("ID of the object not provided");

if (@$_GET["type"] == "thumbnail")
  $sourceTableColumn = "thumbnail";
else
  $sourceTableColumn = "image";

$id = $db->real_escape_string($_GET["id"]);
$result = query("SELECT $sourceTableColumn FROM $sourceTableName where id='$id' and home_id=".homeID());
if ($result->num_rows == 0)
  die("Image not found!");
$image = $result->fetch_assoc()[$sourceTableColumn];
header("Content-type: image/jpeg");
echo $image;
?>
