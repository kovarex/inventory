<?php
require("src/auth.php");
require("src/db.php");
require("src/item_helper.php");
require("src/image_upload_helper.php");
require("src/transaction_log.php");

if (!checkCategoryAndLocation())
  die("Attempt to use invalid location or category");

$imageData = tryToProcessImageUpload();

$beforeChange = query("SELECT * FROM im_item where im_item.id=".escape($_POST["id"]))->fetch_assoc();

query("UPDATE im_item SET ".
      "  name=".escape($_POST["name"]).",".
      "  description=".escape($_POST["description"]).",".
      "  category_id=".escape($_POST["category_id"]).",".
      "  location_id=".escape($_POST["location_id"]).
      (isset($imageData) ? ",image=X".escape($imageData["big"]) : "").
      (isset($imageData) ? ",thumbnail=X".escape($imageData["thumbnail"]) : "").
      "WHERE id=".escape($_POST["id"]).$queryRightCheck);
if (!empty($beforeChange["location_id"]) and !empty($_POST["location_id"]) &&
    $beforeChange["location_id"] != $_POST["location_id"])
  itemMoved($_POST["id"], $beforeChange["location_id"], $_POST["location_id"], $_POST["comment"]);
header("Location: ".$_POST["redirect"]);
?>
