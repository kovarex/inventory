<?php
require("src/transaction_log.php");

query("UPDATE im_item SET deleted=false where id=".escape($_POST["id"])." and home_id=".homeID());
itemRestored($_POST["id"], $_POST["comment"]);

header("Location: ".$_POST["redirect"]);
?>
