<?php
require_once("src/image_upload_helper.php");
require_once("src/plan_helper.php");

$id = escape(@$_POST["id"]);
$redirect = "location?id=".urlencode(@$_POST["id"]);
if (query("SELECT id FROM im_location WHERE id=".$id." and home_id=".homeID())->num_rows == 0)
  redirectWithMessageCustom($redirect, "Location not found!");

if (!empty($_POST["remove"]))
{
  query("UPDATE im_location SET plan=NULL, plan_type=NULL, plan_width=NULL, plan_height=NULL WHERE id=".$id);
  redirect($redirect);
}

$plan = tryToProcessPlanUpload();
if ($plan === NULL)
  redirectWithMessageCustom($redirect, "No plan file was uploaded.");
if (is_string($plan))
  redirectWithMessageCustom($redirect, $plan);

query("UPDATE im_location SET
         plan=X".escape(bin2hex($plan["data"])).",
         plan_type=".escape($plan["type"]).",
         plan_width=".max(1, (int)round($plan["width"])).",
         plan_height=".max(1, (int)round($plan["height"]))."
       WHERE id=".$id);
redirect($redirect);
?>
