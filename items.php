<?php
require_once("src/transaction_log.php");
require_once("src/item_helper.php");
require_once("src/location_helper.php");
require_once("src/table_viewer.php");

echo "<h1>Items</h1>";
$queryRightCheck = " and home_id=".homeID();
$queryDeleted=" and deleted=false";

if (@$_GET["deleted"] == 'true')
  $queryDeleted=" and deleted=true";

$formAction = "add";

itemForm($formAction, @$itemToEdit, "items");
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
  $searchSQL=" AND (im_item.name LIKE '%{$searchQuery}%' OR im_item.description LIKE '%{$searchQuery}%' OR im_item.author LIKE '%{$searchQuery}%')";
}
else
  $searchSQL="";

if (@$_GET["deleted"] == 'true')
  echo '<div><a href="items">Show existing items</a></div>';
else
  echo '<div><a href="items?deleted=true">Show deleted items</a></div>';

$table = new TableViewer("im_category, im_item
                            LEFT JOIN im_location ON im_item.location_id=im_location.id
                          WHERE
                            im_item.category_id = im_category.id and
                            im_item.home_id=".homeID().$searchSQL.$queryDeleted,
                         $_GET);

if (@$_GET["deleted"] and $_SESSION["home"]["is_admin"])
  $table->addColumn("anihilate",
                    "Anihilate",
                    array(),
                    function($row)
                    {
                     echo "<form method=\"post\" action=\"annihilate_item\">
                             <input type=\"submit\" value=\"Annihilate\"/>
                             <input type=\"hidden\" name=\"id\" value=\"".$row["id"]."\"/>
                             <input type=\"hidden\" name=\"action\" value=\"annihilate\">
                             <input type=\"hidden\" name=\"redirect\" value=\"items?deleted=true\"/>
                           </form>";
                    });

$table->setPrimarySort(new SortDefinition("id", false));

$table->addColumn("has_image",
                  "Image",
                  array(array("length(im_item.image) > 0", "has_image")),
                  function($row) { echo itemLink($row["id"], itemImage($row["id"], $row["has_image"])); });

$table->addColumn("name",
                  "Name",
                  array(array("im_item.name", "name"),
                        array("im_item.id", "id")),
                  function($row) { echo itemLink($row["id"], $row["name"]); });

$table->addColumn("description",
                  "Description",
                  array(array("im_item.description", "description")),
                  function($row) { echo $row["description"]; });

$table->addColumn("author",
                  "Author",
                  array(array("im_item.author", "author")),
                  function($row) { echo $row["author"]; });

$table->addColumn("category",
                  "Category",
                  array(array("im_category.name", "category_name"),
                        array("im_item.category_id", "category_id")),
                  function($row) { echo categoryLink($row["category_id"], $row["category_name"]); });

$table->addColumn("location",
                  "Location",
                  array(array("im_location.name", "location_name"),
                        array("im_item.location_id", "location_id")),
                  function($row) { echo locationLink($row["location_id"], $row["location_name"]); });

$table->addColumn("delete",
                  "Delete",
                  array(),
                  function($row) { echo  "<form method=\"post\" action=\"".(@$_GET["deleted"] == "true" ? "restore_item" : "delete_item")."\">
                                            <input type=\"text\" name=\"comment\"/>
                                            <input type=\"submit\" value=\"".(@$_GET["deleted"] == "true" ? "Restore" : "Delete")."\"/>
                                            <input type=\"hidden\" name=\"id\" value=\"".$row["id"]."\"/>
                                            <input type=\"hidden\" name=\"redirect\" value=\"items".(@$_GET["deleted"] == "true" ? "?deleted=true" : "")."\"/>
                                          </form>"; });

$table->render();
?>
