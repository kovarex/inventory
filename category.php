<?php
require("src/header.php");

$queryRightCheck = " and home_id=".homeID();

if (@$_POST["action"] == "edit")
  query("UPDATE im_category SET name='".
        $db->real_escape_string($_POST["name"])."',
        description='".
        $db->real_escape_string($_POST["description"]).
        "' WHERE id='".
        $db->real_escape_string($_POST["id"])
        ."'".$queryRightCheck);

if (@$_POST["action"] == "add")
  $db->query("INSERT INTO im_category(name,description,home_id) value('".
             $db->real_escape_string($_POST["name"])."','".
             $db->real_escape_string($_POST["description"])."',".
             homeID().")");

if (@$_POST["action"] == "delete")
  $db->query("DELETE FROM im_category where id='".
             $db->real_escape_string($_POST["id"])."'".$queryRightCheck);

$formAction = "add";
if (@$_POST["action"] == "start-edit")
{
  $result=$db->query("SELECT * FROM im_category where id='".
             $db->real_escape_string($_POST["id"])."'".$queryRightCheck);
  $row=$result->fetch_assoc();
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
  <input type="submit" value="<?= $formAction == "add" ? "Add category" : "Edit" ?>"/>
</form>

<?php


echo "<table class='data-table'>";

$result = $db->query("SELECT * FROM im_category where im_category.home_id=".homeID());
while($row = $result->fetch_assoc())
{
  echo <<<HTML
  <tr>
    <td>
      {$row["name"]}
    </td>
    <td>
      {$row["description"]}
    </td>
    <td>
      <form method="post" >
        <input type="submit" value="Delete"/>
        <input type="hidden" name="id" value="{$row["id"]}"/>
        <input type="hidden" name="action" value="delete">
      </form>
    </td>
    <td>
      <form method="post" >
        <input type="submit" value="Edit"/>
        <input type="hidden" name="id" value="{$row["id"]}"/>
        <input type="hidden" name="action" value="start-edit">
      </form>
    </td>
  </tr>
HTML;
}

echo "</table>";

require("src/footer.php");
?>