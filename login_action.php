<?php

if (!@$_POST["username"])
  redirectWithMessageCustom("/login", "username not provided");

if (empty($_POST["username"]))
  redirectWithMessageCustom("/login", "empty username");

$user = query("SELECT * from im_user where im_user.username=".escape($_POST["username"]))->fetch_assoc();

if (!$user)
  redirectWithMessageCustom("/login", "user with username \"".$_POST["username"]."\" doesn't exist.");

if (!password_verify($_POST["password"], $user["password"]))
  redirectWithMessageCustom("/login", "Wrong password!");

$_SESSION["user"] = $user;
redirect("/");
?>
