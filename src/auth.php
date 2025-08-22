<?php
session_start();
if (empty($_SESSION["user"]))
{
  header("Location: login.php");
  die();
}

if (@!$homeNotRequired and empty($_SESSION["home"]))
{
  header("Location: home.php");
  die();
}

function userID()
{
  return $_SESSION["user"]["id"];
}

function homeID()
{
  if (empty($_SESSION["home"]))
    return 0;
  return $_SESSION["home"]["id"];
}
?>
