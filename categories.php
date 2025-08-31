<?php
require("src/header.php");
echo "<h1>Categories</h1>";
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
  query("INSERT INTO im_category(name,description,home_id) value('".
        $db->real_escape_string($_POST["name"])."','".
        $db->real_escape_string($_POST["description"])."',".
        homeID().")");

if (@$_POST["action"] == "delete")
  query("DELETE FROM im_category where id=".escape($_POST["id"]).$queryRightCheck);

$formAction = "add";
if (@$_POST["action"] == "start-edit")
{
  $result = query("SELECT * FROM im_category where id='".
                  $db->real_escape_string($_POST["id"])."'".$queryRightCheck);
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
  <input type="submit" value="<?= $formAction == "add" ? "Add category" : "Edit" ?>"/>
</form>

<?php


$result = $db->query("SELECT * FROM im_category where im_category.home_id=".homeID()." ORDER BY im_category.name ASC");
if ($result->num_rows != 0)
{
  echo "<table class='data-table'><tr><th>Name</th><th>Description</th></tr>";
  while($row = $result->fetch_assoc())
  {
    echo "<tr>";
    echo "<td>".categoryLink($row["id"], $row["name"])."</td>";
    echo "<td>".$row["description"]."</td>";
    echo "<td>
            <form method=\"post\">
              <input type=\"submit\" value=\"Delete\"/>
              <input type=\"hidden\" name=\"id\" value=\"".$row["id"]."\"/>
              <input type=\"hidden\" name=\"action\" value=\"delete\">
            </form>
          </td>";
    echo "<td>
            <form method=\"post\">
              <input type=\"submit\" value=\"Edit\"/>
              <input type=\"hidden\" name=\"id\" value=\"".$row["id"]."\"/>
              <input type=\"hidden\" name=\"action\" value=\"start-edit\">
            </form>
          </td>";
    echo "</tr>";
  }
}
echo "</table>";

require("src/footer.php");
?>
