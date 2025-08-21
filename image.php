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

$id = $db->real_escape_string($_GET["id"]);
$result = query("SELECT image FROM $sourceTableName where id='$id'");
if ($result->num_rows == 0)
  die("Image not found!");
$image = $result->fetch_assoc()["image"];
header("Content-type: image/jpeg");
echo $image;
?>
