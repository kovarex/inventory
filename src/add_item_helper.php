<?php
function itemForm($formAction, $itemToEdit, $predefinedLocation = NULL)
{
?>
  <form method="post" enctype="multipart/form-data">
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
      <td><label for="category_id">Category:<label</td>
      <td>
        <select name="category_id">
        <?php

          $result = query("SELECT
                             im_category.id,
                             im_category.name
                           FROM im_category
                           where im_category.home_id=".homeID());
          $rows = $result->fetch_all(MYSQLI_ASSOC);

          foreach($rows as $row)
          {
            $selected = $row["id"] == $itemToEdit["category_id"] ? " selected" : "";
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
      <td><label for="location_id">Location:<label</td>
      <td>
        <select name="location_id">
        <?php

          $result = query("SELECT
                             im_location.id,
                             im_location.name
                           FROM im_location
                           where im_location.home_id=".homeID());
          $rows = $result->fetch_all(MYSQLI_ASSOC);

          foreach($rows as $row)
          {
            $selected = $row["id"] == $itemToEdit["location_id"] ? " selected" : "";
            echo "<option value='{$row["id"]}'{$selected}>{$row["name"]}</option>";
          }
        ?>
        </select>
    </tr> <?php
    }
    else
      echo "<input type=\"hidden\" name=\"location_id\" value=\"".$predefinedLocation."\"/>";
    ?>
    <tr>
      <td><label for="item_image">Image:</label></td>
      <td>
        <input type="file" name="item_image"/>
      </td>
    </tr>
    <tr>
      <td><?= $formAction == "add" ? "Creation comment" : "Move comment" ?></td>
      <td><input type="text" name="comment"/></td>
    </tr>
  </table>
  <input type="submit" value="<?= $formAction == "add" ? "Add Item" : "Edit" ?>"/>
</form>
<?php
}
?>