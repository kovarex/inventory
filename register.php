<?php
session_start();

function tryToRegister($username, $password, $email)
{
  global $db;
  if (empty($username))
    return "Username must be specified.";

  $result = query("SELECT * from im_user where im_user.username LIKE '".
                  $db->real_escape_string($username)."'");

  if ($result->num_rows != 0)
    return "User with that name already exists!";

  $result = query("SELECT * from im_user where im_user.email LIKE '".
                  $db->real_escape_string($email)."'");

  if ($result->num_rows != 0)
    return "User with that email address already exists!";

  query("INSERT INTO im_user(username, password, email)
        values('{$db->real_escape_string($username)}',
               '{$db->real_escape_string(password_hash($password, PASSWORD_DEFAULT))}',
               '{$db->real_escape_string($email)}')", true);

  $_SESSION["user"] = query("SELECT * FROM im_user where username='{$db->real_escape_string($username)}'")->fetch_assoc();
  $_SESSION["home"] = NULL;
  header("Location: /");
  return true;
}

if (!empty($_POST["username"]))
{
  $error = tryToRegister($_POST["username"], $_POST["passwowrd"], $_POST["email"]);
  if (!empty($error))
    echo "Register error: ".$error;
}

require("src/header_internal.php");
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
    <tr>
      <td><label for="email">Email:</label></td>
      <td><input name="email" type="email"/></td>
    </tr>
  </table>
  <input type="submit" value="Register"/>
</form>
