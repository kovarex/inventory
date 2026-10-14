<?php
if (!isset($_GET["source"]))
  die("Source of the image not provided");

if ($_GET["source"] == "item")
  $sourceTableName = "im_item";
elseif ($_GET["source"] == "location")
  $sourceTableName = "im_location";
else
  die("Unknown source type: ".$_GET["source"]);

if (!isset($_GET["id"]))
  die("ID of the object not provided");

if (@$_GET["type"] == "thumbnail")
  $sourceTableColumn = "thumbnail";
elseif (@$_GET["type"] == "plan" and $sourceTableName == "im_location")
  $sourceTableColumn = "plan";
else
  $sourceTableColumn = "image";

$id = $db->real_escape_string($_GET["id"]);
$result = query("SELECT $sourceTableColumn".($sourceTableColumn == "plan" ? ", plan_type" : "")." FROM $sourceTableName where id='$id' and home_id=".homeID());
if ($result->num_rows == 0)
  die("Image not found!");
$row = $result->fetch_assoc();
$image = $row[$sourceTableColumn];
if ($sourceTableColumn == "plan")
{
  header("Content-type: ".$row["plan_type"]);
  // An SVG plan opened directly must not be able to run scripts.
  header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; img-src data:");
}
else
  header("Content-type: image/jpeg");
if ($sourceTableColumn == "plan")
  header("Cache-control: no-cache"); // the plan can be replaced under the same address
else
{
  header('Cache-control: max-age='.(60*60*24*365));
  header('Expires: '.gmdate(DATE_RFC1123,time() + 60*60*24*365));
}
echo $image;
?>
