<?php
include("../config.php");
$db = new mysqli("a066um.forpsi.com", "f190987", $dbPassword, "f190987");
if ($db->connect_error)
  die("Connection failed: " . $db->connect_error);
?>
