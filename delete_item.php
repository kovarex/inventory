<?php
require("src/auth.php");
require("src/db.php");
require("src/transaction_log.php");

query("UPDATE im_item SET deleted=true where id=".escape($_POST["id"])." and home_id=".homeID());
itemDeleted($_POST["id"], $_POST["comment"]);
header("Location: ".$_POST["redirect"]);
?>
