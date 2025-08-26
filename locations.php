<?php
require("src/header.php");
require_once("src/image_upload_helper.php");
require_once("src/location_helper.php");
require_once("src/transaction_log.php");

echo "<h1>Locations</h1>";
$queryRightCheck = " and home_id=".homeID();

$imageData = tryToProcessImageUpload();

if (@$_POST["action"] == "edit")
{
  $oldParentLocation=query("SELECT parent_location_id FROM im_location WHERE id=".escape($_POST["id"]))->fetch_assoc()["parent_location_id"];
  $locationChildren = locationChildrenFlat(escape($_POST["id"]));
  $validMove = true;
  if ($_POST["parent_id"] == $_POST["id"])
  {
    $validMove = false;
    echo "<div>Can't move location to itself!</div>";
  }
  if ($validMove)
  {
    foreach ($locationChildren as $child)
      if ($child == $_POST["parent_id"])
      {
        $validMove = false;
        echo "<div>Can't move location to its own child!</div>";
      }
  }
  if ($validMove)
  {
    $updated = query("UPDATE im_location SET
                        name=".escape($_POST["name"]).",
                        description=".escape($_POST["description"]).",
                        parent_location_id=".escape($_POST["parent_id"]).
                        (isset($imageData) ? ",image=X".escape($imageData["big"]) : "").
                        (isset($imageData) ? ",thumbnail=X".escape($imageData["thumbnail"]) : "").
                     " WHERE id=".escape($_POST["id"]).$queryRightCheck);
    if ($updated && $oldParentLocation != $_POST["parent_id"])
      locationMoved($_POST["id"], $oldParentLocation, $_POST["parent_id"], $_POST["comment"], $locationChildren);
  }
}

if (@$_POST["action"] == "add")
  query("INSERT INTO im_location(home_id, name,description,parent_location_id, image, thumbnail) value(".
        homeID().",".
        escape($_POST["name"]).",".
        escape($_POST["description"]).",".
        escape($_POST["parent_id"]).",".
        (isset($imageData) ?  "X".escape($imageData["big"]) : "NULL").",".
        (isset($imageData) ?  "X".escape($imageData["thumbnail"]) : "NULL").")");

if (@$_POST["action"] == "delete")
  query("DELETE FROM im_location where id='".
        $db->real_escape_string($_POST["id"])."'".$queryRightCheck);

$formAction = "add";
if (@$_POST["action"] == "start-edit")
{
  $locationToEdit = query("SELECT * FROM im_location where id=".escape($_POST["id"]).$queryRightCheck)->fetch_assoc();
  $formAction = "edit";
}
?>

<?php

$result = $db->query("SELECT
                        im_location.id,
                        im_location.name,
                        im_location.description,
                        parent_location.name as parent_name,
                        parent_location.id as parent_id,
                        length(im_location.image) > 0 as has_image
                      FROM im_location
                        LEFT JOIN im_location parent_location on im_location.parent_location_id=parent_location.id
                      WHERE im_location.home_id=".homeID());
$rows = $result->fetch_all(MYSQLI_ASSOC);

?>

<form method="post" enctype="multipart/form-data">
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
      <td><label for="image">Image:</label></td>
      <td><input type="file" name="image" accept="image/*" capture="camera"></td>
    </tr>
    <tr>
      <td><label for="parent_id">Parent:<label</td>
      <td><?php locationSelector("parent_id", @$locationToEdit["parent_location_id"]); ?></td>
    </tr>
<?php
    if ($formAction == "edit")
      echo '
        <tr>
          <td>Move comment</td>
          <td><input type="text" name="comment"/></td>
        </tr>';
?>
  </table>
  <input type="submit" value="<?= $formAction == "add" ? "Add location" : "Edit" ?>"/>
</form>

<?php

if (count($rows) != 0)
{
  echo "<table class='data-table'><tr><th>Image</th><th>Name</th><th>Description</th><th>Parent</th</tr>";
  foreach($rows as $row)
  {
    echo '
    <tr>
      <td>'.locationLink($row["id"], locationImage($row["id"], $row["has_image"])).'
      <td>'.locationLink($row["id"], $row["name"]).'</a></td>
      <td>'.$row["description"].'
      </td>
      <td>'.locationLink($row["parent_id"], $row["parent_name"]).'</td>
      <td>
        <form method="post" >
          <input type="submit" value="Delete"/>
          <input type="hidden" name="id" value="'.$row["id"].'"/>
          <input type="hidden" name="action" value="delete">
        </form>
      </td>
      <td>
        <form method="post" >
          <input type="submit" value="Edit"/>
          <input type="hidden" name="id" value="'.$row["id"].'"/>
          <input type="hidden" name="action" value="start-edit">
        </form>
      </td>
    </tr>';
  }
}

echo "</table>";

require("src/footer.php");
?>
