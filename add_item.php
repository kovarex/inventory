<?php
require("src/auth.php");
require("src/db.php");
require("src/item_helper.php");
require("src/image_upload_helper.php");
require("src/transaction_log.php");

if (!checkCategoryAndLocation())
  die("Attempt to use invalid location or category");

$imageData = tryToProcessImageUpload();
query("INSERT INTO im_item(name,description,home_id,location_id,image, thumbnail, category_id)
      value(".
      escape($_POST["name"]).",".
      escape($_POST["description"]).",".
      homeID().",".
      escape($_POST["location_id"]).",".
      (isset($imageData) ?  "X".escape($imageData["big"]) : "NULL").",".
      (isset($imageData) ?  "X".escape($imageData["thumbnail"]) : "NULL").",".
      escape($_POST["category_id"]).")");
itemCreated($_POST["location_id"], $_POST["comment"]);
header("Location: ".$_POST["redirect"]);

?>
