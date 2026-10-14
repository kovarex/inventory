<?php
require_once("src/plan_helper.php");

$id = escape(@$_POST["id"]);
$redirect = "location?id=".urlencode(@$_POST["id"]);
if (query("SELECT id FROM im_location WHERE id=".$id." and home_id=".homeID())->num_rows == 0)
  redirectWithMessageCustom($redirect, "Location not found!");

$area = trim(@$_POST["area"]);
if ($area != "" and !parsePlanArea($area))
  redirectWithMessageCustom($redirect, "Invalid area, it needs at least 3 points.");

query("UPDATE im_location SET plan_area=".($area == "" ? "NULL" : escape($area))." WHERE id=".$id);
redirect($redirect);
?>
