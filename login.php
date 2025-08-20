<?php

function checkLogin($username, $password)
{
  global $db;
  if (empty($username))
    return false;

  $query = "SELECT * from im_user where im_user.username='".
                       $db->real_escape_string($username)."'";
  $result = $db->query($query);

  if ($result->num_rows == 0)
    return "User not found!";

  $userFromDB = $result->fetch_assoc();
  if (!password_verify($password, $userFromDB["password"]))
    return "Wrong password!";

  $_SESSION["user"] = $userFromDB;
  return true;
}

function checkLogout($action)
{
  if ($action === "logoff")
    $_SESSION["user"] = NULL;
}

require("src/db.php");
session_start();
checkLogout($_POST['action']);
$loginResult = checkLogin($_POST['username'], $_POST['password']);

if ($loginResult === true)
{
  header("Location: index.php");
  die();
}

require("src/header_internal.php");

if (is_string($loginResult))
  echo $loginResult;

if (!empty($_SESSION["user"]))
{
  echo "Currently logged in as ".$_SESSION["user"]["username"];
  ?>
  <form method="post">
    <input type="submit" value="Logoff"/>
    <input type="hidden" name="action" value="logoff"/>
  <?php
}
else
{
 ?>
 <form method="post">
    <table>
      <tr>
        <td><label for="username">Username:</label></td>
        <td><input name="username" type="text"/></td>
      </tr>
      <tr>
        <td><label for="password">Password:</label></td>
        <td><input name="password" type="password"/></td>
      </tr>
    </table>
    <input type="submit" value="Login"/>
  </form>
  <?php
}

require("src/footer.php")
?>