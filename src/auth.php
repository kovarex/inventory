<?php
session_start();
if (empty($_SESSION["user"]))
{
  header("Location: login.php");
  die();
}

if (@!$homeNotRequired and empty($_SESSION["home"]))
{
  $lastHomeID = query("SELECT last_home_id FROM im_user WHERE im_user.id=".userID())->fetch_assoc()["last_home_id"];
  if (!empty($lastHomeID))
  {
    $myHomesSelect = query("SELECT * from im_home_user WHERE im_home_user.home_id=".$lastHomeID." and im_home_user.user_id=".userID());
    if ($myHomesSelect->num_rows != 0)
    {
      $_SESSION["home"] = query("Select * from im_home WHERE im_home.id=".$lastHomeID)->fetch_assoc();
      $_SESSION["home"]["is_admin"] = $myHomesSelect->fetch_assoc()["is_admin"];
    }
  }
  else
  {
    header("Location: home.php");
    die();
  }
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
