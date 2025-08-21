<?php
$homeNotRequired = true;
require_once("src/db.php");
require_once("src/auth.php");

$myHomesSelect = "SELECT * from im_home_user where im_home_user.home_id='".
                    $db->real_escape_string($_POST["id"])."' and im_home_user.user_id=".userID();
$queryRightCheck = "and exists(".$myHomesSelect.")";
if (@$_POST["action"] == "activate")
{
  if (query($myHomesSelect)->num_rows != 0)
    $_SESSION["home"] = query("SELECT * from im_home where id='".$db->real_escape_string($_POST["id"])."'")->fetch_assoc();
}

require("src/header.php");
echo "<h1>Homes</h1>";

function tryToInvite()
{
  global $db;
  global $myHomesSelect;
  $result = query("SELECT * FROM im_user where username='".$db->real_escape_string($_POST["username"])."'");
  if ($result->num_rows == 0)
    return "User to invite doesn't exist!";
  if (query($myHomesSelect)->num_rows != 0)
    return "You have no access to this home!";
  query("INSERT INTO im_home_user(home_id, user_id)
        values('{$db->real_escape_string($_POST["id"])}',
               {$result->fetch_assoc()["id"]})");
}

if (@$_POST["action"] == "invite")
  tryToInvite();

if (@$_POST["action"] == "edit")
  query("UPDATE im_home SET name='".
        $db->real_escape_string($_POST["name"])."',
        description='".
        $db->real_escape_string($_POST["description"]).
        "' WHERE id='".
        $db->real_escape_string($_POST["id"])
        ."'".$queryRightCheck);

if (@$_POST["action"] == "add")
  multi_query_and_clear("INSERT INTO im_home(name,description) value('".
                        $db->real_escape_string($_POST["name"])."','".
                        $db->real_escape_string($_POST["description"])."');".
                        "INSERT INTO im_home_user(home_id, user_id) ".
                        "values(LAST_INSERT_ID(), ".userID().")");

if (@$_POST["action"] == "delete")
  query("DELETE FROM im_home where id='".
        $db->real_escape_string($_POST["id"])."'".$queryRightCheck);

$formAction = "add";
if (@$_POST["action"] == "start-edit")
{
  $result = query("SELECT * FROM im_home where id='".
                  $db->real_escape_string($_POST["id"])."'");
  $row = $result->fetch_assoc();
  $formAction = "edit";
}
?>

<form method="post">
  <input type="hidden" name="action" value="<?= $formAction ?>"/>
  <input type='hidden' name='id' value="<?= @$row['id'] ?>"/>
  <table>
    <tr>
      <td><label for="name">Name:</label></td>
      <td><input type="text" name="name" value="<?= @$row['name'] ?>"/></td>
    </tr>
    <tr>
      <td><label for="description">Description:</label></td>
      <td><input type="text" name="description" value="<?= @$row['description'] ?>"/></td>
    </tr>
  </table>
  <input type="submit" value="<?= $formAction == "add" ? "Add home" : "Edit" ?>"/>
</form>

<?php


$result = query("SELECT im_home.* FROM im_home,im_home_user where im_home.id=im_home_user.home_id and im_home_user.user_id=".userID());

function activeColumn($row)
{
  if ($row["id"] === homeID())
    return "Active";
  return <<<HTML
          <form method="post" >
          <input type="submit" value="Activate"/>
          <input type="hidden" name="id" value="{$row["id"]}"/>
          <input type="hidden" name="action" value="activate">
        </form>
HTML;
}

if ($result->num_rows != 0)
{
  echo "<table class='data-table'><tr><th>State</th><th>Name</th><th>Description</th></tr>";

  while($row = $result->fetch_assoc())
  {
    $activeColumnResult = activeColumn($row);

    echo <<<HTML
    <tr>
      <td>
        {$activeColumnResult}
      </td>
      <td>
        {$row["name"]}
      </td>
      <td>
        {$row["description"]}
      </td>
      <td>
        <form method="post">
          <input type="submit" value="Delete"/>
          <input type="hidden" name="id" value="{$row["id"]}"/>
          <input type="hidden" name="action" value="delete">
        </form>
      </td>
      <td>
        <form method="post">
          <input type="submit" value="Edit"/>
          <input type="hidden" name="id" value="{$row["id"]}"/>
          <input type="hidden" name="action" value="start-edit">
        </form>
      </td>
      <td>
        <form method="post">
          <input type="text" name="username"/>
          <input type="hidden" name="id" value="{$row["id"]}"/>
          <input type="hidden" name="action" value="invite"/>
          <input type="submit" value="Invite"/>
        </form>
      </td>
    </tr>
  HTML;
  }

  echo "</table>";
}

require("src/footer.php");
?>
