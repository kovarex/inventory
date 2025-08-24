<?php
require("src/header.php");
require("src/item_helper.php");

if (!isset($_GET["id"]))
  die("ID of the object not provided");

$id = escape($_GET["id"]);

$result = query("SELECT
                   im_item.id,
                   im_item.name,
                   im_item.description,
                   parent_location.id as location_id,
                   parent_location.name as location_name,
                   im_category.id as category_id,
                   im_category.name as category_name,
                   length(im_item.image) > 0 as has_image
                 FROM im_category, im_item
                 left join im_location parent_location on im_item.location_id=parent_location.id
                 where im_item.category_id = im_category.id and im_item.home_id=".homeID().
                 " and im_item.id=$id");

if ($result->num_rows == 0)
  die("Item not found!");

$item = $result->fetch_assoc();

echo "<div id=\"edit-dialog\" style=\"position:absolute;background: white;display:none;\">";
itemForm("edit", $item, "item.php?id=".$_GET["id"]);
echo "</div>";

echo "<h1>Item: ".$item["name"]."<button type=\"button\" onclick=\"showEditDialog(event);\">Edit</button></h1>";
?>
<script type="text/javascript">
function showEditDialog(event)
{
  let element = document.getElementById('edit-dialog');
  if (element.style.display == 'none')
  {
    element.style.display = 'block';
    let button = event.target;
    let buttonPosition = button.getBoundingClientRect();
    element.style.left = buttonPosition.x + 'px';
    element.style.top = (buttonPosition.y + button.clientHeight) + 'px';
  }
  else
    element.style.display = 'none';
}
</script>
<?php
echo $item['description'];
echo '
 <table>
    <tr>
      <td>Category:</td>
      <td>'.categoryLink($item["category_id"], $item['category_name']).'</td>
    </tr>
    <tr>
      <td>Location:</td>
      <td>'.locationLink($item["location_id"], $item['location_name']).'</td>
    </tr>
  </table>';

echo itemImage($item["id"], $item["has_image"], "big");

$result = query("SELECT
                       im_transaction.*,
                       from_location.name as from_location_name,
                       to_location.name as to_location_name,
                       parent_from_location.name as parent_from_location_name,
                       parent_to_location.name as parent_to_location_name,
                       parent_location.name as parent_location_name,
                       im_user.id as user_id,
                       im_user.username as user_name
                       FROM im_transaction
                 LEFT JOIN im_location from_location ON from_location.id=im_transaction.from_location_id
                 LEFT JOIN im_location to_location ON to_location.id=im_transaction.to_location_id
                 LEFT JOIN im_location parent_from_location ON parent_from_location.id=im_transaction.parent_from_location_id
                 LEFT JOIN im_location parent_to_location ON parent_to_location.id=im_transaction.parent_to_location_id
                 LEFT JOIN im_location parent_location ON parent_location.id=im_transaction.parent_location_id
                 LEFT JOIN im_user ON im_user.id=im_transaction.user_id
                 WHERE item_id=$id
                 ORDER BY im_transaction.timestamp");

$rows = $result->fetch_all(MYSQLI_ASSOC);

if (count($rows) != 0)
{
?>
  <table class="data-table">
    <tr>
      <th>Transaction</th><th>Comment</th><th>User</th><th>Timestamp</th>
    </tr>
  <?php
    foreach($rows as $row)
    {
      echo "<tr><td>";
      if (empty($row["from_location_id"]) and !empty($row["to_location_id"]))
        echo "Created in ".locationLink($row["to_location_id"], $row["to_location_name"]);
      else if (!empty($row["from_location_id"]) and !empty($row["to_location_id"]))
        echo "Moved from ".locationLink($row["from_location_id"], $row["from_location_name"]).
             " to ".locationLink($row["to_location_id"], $row["to_location_name"]);
      else if (!empty($row["parent_from_location_id"]) and !empty($row["parent_to_location_id"]))
        echo locationLink($row["parent_location_id"], $row["parent_location_name"])." moved from ".
             locationLink($row["parent_from_location_id"], $row["parent_from_location_name"]).
             " to ".locationLink($row["parent_to_location_id"], $row["parent_to_location_name"]);
      else
        echo "Unknown operation";
      echo "</td><td>".$row["comment"]."</td>";
      echo "<td>".userLink($row["user_id"], $row["user_name"])."</td>";
      echo "<td>".$row["timestamp"]."</td>";
      echo "</tr>";
    }
  ?>
  </table>
<?php
}
require("src/footer.php"); ?>


