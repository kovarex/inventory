<?php
require("src/header.php");

if (!isset($_GET["id"]))
  die("ID of the object not provided");

$id = $db->real_escape_string($_GET["id"]);

$result = query("SELECT
                   im_item.id,
                   im_item.name,
                   im_item.description,
                   parent_location.name as location_name,
                   im_category.name as category_name,
                   length(im_item.image) as image_size
                 FROM im_category, im_item
                 left join im_location parent_location on im_item.location_id=parent_location.id
                 where im_item.category_id = im_category.id and im_item.home_id=".homeID().
                 " and im_item.id='".$id."'");

if ($result->num_rows == 0)
  die("Item not found!");

$item = $result->fetch_assoc();

echo "<h1>Item: ".$item["name"]."</h1>";
?>
 <table>
    <tr>
      <td>Name:</td>
      <td><?= @$item['name'] ?></td>
    </tr>
    <tr>
      <td>Description:</td>
      <td><?= @$item['description'] ?></td>
    </tr>
    <tr>
      <td>Category:</td>
      <td><?= @$item['category_name'] ?></td>
    </tr>
    <tr>
      <td>Location:</td>
      <td><?= @$item['location_name'] ?></td>
    </tr>
  </table>
<?php
if ($item['image_size'] > 0)
  echo "<img src=\"image.php?source=item&id={$item['id']}\"/>";

require("src/footer.php"); ?>


