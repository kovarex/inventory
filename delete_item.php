<?php
require_once("src/transaction_log.php");

query("UPDATE im_item SET deleted=true where id=".escape($_POST["id"])." and home_id=".homeID());
itemDeleted($_POST["id"], $_POST["comment"]);
header("Location: ".$_POST["redirect"]);
?>
