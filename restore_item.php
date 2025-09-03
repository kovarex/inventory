<?php
require("src/auth.php");
require("src/db.php");
query("UPDATE im_item SET deleted=false where id=".escape($_POST["id"])." and home_id=".homeID());
header("Location: ".$_POST["redirect"]);
?>
