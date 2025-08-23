<?php
require("src/header.php");
require_once("src/transaction_log.php");

echo "<h1>Locations</h1>";
$queryRightCheck = " and home_id=".homeID();

if (@$_POST["action"] == "edit") {
  $locationID = $db->real_escape_string($_POST["id"]);
  $oldParentLocation=query("SELECT parent_location_id FROM im_location WHERE id=".escape($_POST["id"]))->fetch_assoc()["parent_location_id"];
  $locationChildren = locationChildrenFlat($locationID);
  $validMove = true;
  if ($_POST["parent_id"] == $locationID) {
    $validMove = false;
    echo "<div>Can't move location to itself!</div>";
  }
  if ($validMove) {
    foreach ($locationChildren as $child) {
      if ($child == $_POST["parent_id"]) {
        $validMove = false;
        echo "<div>Can't move location to its own child!</div>";
      }
    }
  }
  if ($validMove) {
    $updated = query("UPDATE im_location SET
                        name=".escape($_POST["name"]).",
                        description=".escape($_POST["description"]).",
                        parent_location_id=".escape($_POST["parent_id"])."
                      WHERE id=".escape($_POST["id"]).$queryRightCheck);
    if ($updated && $oldParentLocation != $_POST["parent_id"])
      locationMoved($locationID, $oldParentLocation, $_POST["parent_id"], $_POST["comment"], $locationChildren);
  }
}

if (@$_POST["action"] == "add")
  query("INSERT INTO im_location(home_id, name,description,parent_location_id) value(".
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
<?php
    if ($formAction == "edit")
      echo '
        <tr>
          <td>Move comment</td>
          <td><input type="text" name="comment"/></td>
        </tr>
      ';
?>
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
        <a href="location.php?id={$row["id"]}">{$row["name"]}</a>
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
