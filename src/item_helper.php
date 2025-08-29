<?php
require_once("src/location_helper.php");
function checkCategoryAndLocation()
{
  global $db;
  if (query("SELECT
               im_location.id
             FROM
               im_location
             WHERE
               im_location.id=".escape($_POST["location_id"])." and
               im_location.home_id=".homeID())->num_rows == 0)
    return false;

  if (query("SELECT
              im_category.id
            FROM
              im_category
            WHERE
              im_category.id=".escape($_POST["category_id"])." and
              im_category.home_id=".homeID())->num_rows == 0)
    return false;

  return true;
}

function itemForm($formAction, $itemToEdit, $redirect, $predefinedLocation = NULL)
{
?>
  <form method="post" enctype="multipart/form-data" action="<?= $formAction == "add" ? "add_item.php" : "edit_item.php" ?>" class="data-form">
  <input type="hidden" name="action" value="<?= $formAction ?>"/>
  <input type='hidden' name='id' value="<?= @$itemToEdit['id'] ?>"/>
  <table>
    <tr>
      <td><label for="name">Name:</label></td>
      <td><input type="text" name="name" value="<?= @$itemToEdit['name'] ?>"/></td>
    </tr>
    <tr>
      <td><label for="description">Description:</label></td>
      <td><input type="text" name="description" value="<?= @$itemToEdit['description'] ?>"/></td>
    </tr>
    <tr>
      <td><label for="author">Author:</label></td>
      <td><input type="text" name="author" value="<?= @$itemToEdit['author'] ?>"/></td>
    </tr>
    <tr>
      <td><label for="category_id">Category:</label></td>
      <td>
        <select name="category_id">
        <?php

          $result = query("SELECT
                             im_category.id,
                             im_category.name
                           FROM
                             im_category
                           WHERE
                             im_category.home_id=".homeID().
                         " ORDER BY
                             im_category.name ASC");
          $rows = $result->fetch_all(MYSQLI_ASSOC);

          foreach($rows as $row)
          {
            $selected = $row["id"] == @$itemToEdit["category_id"] ? " selected" : "";
            echo "<option value='{$row["id"]}'{$selected}>{$row["name"]}</option>";
          }
        ?>
        </select>
    </tr>
<?php
    if (empty($predefinedLocation))
    {
    ?>
    <tr>
      <td><label for="location_id">Location:</label></td>
      <td><?php locationSelector("location_id", @$itemToEdit["location_id"]); ?></td>
    </tr><?php
    }
    else
      echo "<input type=\"hidden\" name=\"location_id\" value=\"".$predefinedLocation."\"/>";
    ?>
    <tr>
      <td><label for="image">Image:</label></td>
      <td><input type="file" name="image" accept="image/*" capture="camera" style="width:90px;height:80px;"></td>
    </tr>
    <tr>
      <td><?= $formAction == "add" ? "Creation comment" : "Move comment" ?></td>
      <td><input type="text" name="comment"/></td>
    </tr>
  </table>
  <input type="hidden" name="redirect" value="<?= $redirect ?>"/>
  <input type="submit" value="<?= $formAction == "add" ? "Add Item" : "Save" ?>" style="width:90px;height:80px;"/>
</form>
<?php
}
?>