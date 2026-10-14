<?php
require_once("src/image_upload_helper.php");

$imageData = tryToProcessImageUpload();
query("INSERT INTO im_location(home_id, name, code, description,parent_location_id, image, thumbnail) value(".
      homeID().",".
      escape($_POST["name"]).",".
      (trim($_POST["code"] ?? "") == "" ? "NULL" : escape(trim($_POST["code"]))).",".
      escape($_POST["description"]).",".
      escape($_POST["parent_id"]).",".
      (isset($imageData) ?  "X".escape($imageData["big"]) : "NULL").",".
      (isset($imageData) ?  "X".escape($imageData["thumbnail"]) : "NULL").")");

header("Location: ".$_POST["redirect"]);
?>
