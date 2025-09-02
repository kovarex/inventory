<?php
require("src/auth.php");
require("src/db.php");

$check = query("SELECT * from im_home_user WHERE im_home_user.home_id=".homeID()." and im_home_user.user_id=".userID())->fetch_assoc();
if (!$check["is_admin"])
  die("You are not admin in this home, so you can't delete stuff.");

query("DELETE from im_item WHERE im_item.id=".escape($_POST["id"]));
header("Location: ".$_POST["redirect"]);
?>
