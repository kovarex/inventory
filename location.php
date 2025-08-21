<?php
require("src/header.php");
echo "<h1>Locations</h1>";
$queryRightCheck = " and home_id=".homeID();

if (@$_POST["action"] == "edit")
  query("UPDATE im_location SET name='".
        $db->real_escape_string($_POST["name"])."',
        description='".
        $db->real_escape_string($_POST["description"]).
        "' WHERE id='".
        $db->real_escape_string($_POST["id"])
        ."'".$queryRightCheck);

if (@$_POST["action"] == "add")
  query("INSERT INTO im_item(home_id, name,description,parent_location_id) value(".
        homeID().",'".
        $db->real_escape_string($_POST["name"])."','".
        $db->real_escape_string($_POST["description"])."','".
        $db->real_escape_string($_POST["parent_id"])."')");

if (@$_POST["action"] == "delete")
  query("DELETE FROM im_location where id='".
        $db->real_escape_string($_POST["id"])."'".$queryRightCheck);

$formAction = "add";
if (@$_POST["action"] == "start-edit")
{
  $result = query("SELECT * FROM im_location where id='".
                  $db->real_escape_string($_POST["id"])."'".$queryRightCheck);
  $locationToEdit = $result->fetch_assoc();
  $formAction = "edit";
}
?>

<?php

$result = $db->query("SELECT im_location.id, im_location.name, im_location.description, parent_location.name as parent_name FROM im_location ".
                     "left join im_location parent_location on im_location.parent_location_id=parent_location.id ".
                     "where im_location.home_id=".homeID());
$rows = $result->fetch_all(MYSQLI_ASSOC);

?>

<form method="post">
  <input type="hidden" name="action" value="<?= $formAction ?>"/>
  <input type='hidden' name='id' value="<?= @$locationToEdit['id'] ?>"/>
  <table>
    <tr>
      <td><label for="name">Name:</label></td>
      <td><input type="text" name="name" value="<?= @$locationToEdit['name'] ?>"/></td>
    </tr>
    <tr>
      <td><label for="description">Description:</label></td>
      <td><input type="text" name="description" value="<?= @$locationToEdit['description'] ?>"/></td>
    </tr>
    <tr>
      <td><label for="parent_id">Parent:<label</td>
      <td>
        <select name="parent_id">
          <option value="">None</option>
        <?php
          foreach($rows as $row)
          {
            $selected = $row["id"] == $locationToEdit["parent_location_id"] ? " selected" : "";
            echo "<option value='{$row["id"]}'{$selected}>{$row["name"]}</option>";
          }
        ?>
        </select>
    </tr>
  </table>
  <input type="submit" value="<?= $formAction == "add" ? "Add location" : "Edit" ?>"/>
</form>

<?php

if (count($rows) != 0)
{
  echo "<table class='data-table'><tr><th>Name</th><th>Description</th><th>Parent</th></tr>";
  foreach($rows as $row)
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
        {$row["parent_name"]}
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
}

echo "</table>";

require("src/footer.php");
?>
