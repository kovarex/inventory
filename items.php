<?php
require("src/header.php");
require("src/transaction_log.php");
require("src/item_helper.php");
require_once("constants.php");

echo "<h1>Items</h1>";
$queryRightCheck = " and home_id=".homeID();
$queryDeleted=" and deleted=false";

if (@$_POST["action"] == "delete")
  query("UPDATE im_item SET deleted=true where id=".escape($_POST["id"]).$queryRightCheck);

if (@$_POST["action"] == "restore")
  query("UPDATE im_item SET deleted=false where id=".escape($_POST["id"]).$queryRightCheck);

if (@$_GET["deleted"] == 'true')
  $queryDeleted=" and deleted=true";

$formAction = "add";
if (@$_POST["action"] == "start-edit")
{
  $result = query("SELECT * FROM im_item where id=".escape($_POST["id"]).$queryRightCheck);
  $itemToEdit = $result->fetch_assoc();
  $formAction = "edit";
}

itemForm($formAction, @$itemToEdit, "items.php");
?>

<hr>
<form method=get class="search-form">
  <input type="text" name="search" value="<?= @htmlspecialchars(@$_GET['search']) ?>"/>
  <input type=hidden name="action" value="search"/>
  <input type=submit value="Search"/>
</form>

<?php
if (@$_GET['action']==="search")
{
  echo <<<HTML
  <form method=get class="search-form">
    <input type=submit value="X"/>
  </form>
  HTML;
  $searchQuery=$db->real_escape_string($_GET['search']);
  $searchSQL=" AND (im_item.name LIKE '%{$searchQuery}%' OR im_item.description LIKE '%{$searchQuery}%')";
}
else
  $searchSQL="";


$result = query("SELECT
                   im_item.id,
                   im_item.name,
                   im_item.description,
                   parent_location.id as parent_location_id,
                   parent_location.name as parent_location_name,
                   im_category.id as category_id,
                   im_category.name as category_name,
                   length(im_item.image) as image_size
                 FROM im_category, im_item
                 left join im_location parent_location on im_item.location_id=parent_location.id
                 where im_item.category_id = im_category.id and im_item.home_id=".homeID().$searchSQL.$queryDeleted);
$rows = $result->fetch_all(MYSQLI_ASSOC);

if (count($rows) != 0)
{
  echo "<table class='data-table'><tr><th>Image</th><th>Name</th><th>Description</th><th>Category</th><th>Location</th></tr>";
  foreach($rows as $row)
  {
    echo '
    <tr>
      <td>'.itemLink($row["id"], itemImage($row['id'], $row['image_size'] > 0)).'</td>
      <td>'.itemLink($row["id"], $row["name"]).'
      </td>
      <td>'.$row["description"].'</td>
      <td>'.categoryLink($row["category_id"], $row["category_name"]).'</td>
      <td>'.locationLink($row["parent_location_id"], $row["parent_location_name"]).'</td>
      <td>
        <form method="post">
          <input type="submit" value="'.(@$_GET["deleted"] == 'true' ? "Restore" : "Delete").'"/>
          <input type="hidden" name="id" value="'.$row["id"].'"/>
          <input type="hidden" name="action" value="'.(@$_GET["deleted"] == 'true' ? "restore" : "delete").'">
        </form>
      </td>
    </tr>';
  }
}

echo "</table>";

if (@$_GET["deleted"] == 'true')
  echo '<div><a href="items.php">Show existing items</a></div>';
else
  echo '<div><a href="items.php?deleted=true">Show deleted items</a></div>';

require("src/footer.php");
?>

