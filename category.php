<?php
if (!isset($_GET["id"]))
  die("ID of the object not provided");

$id = escape($_GET["id"]);

$category = query("SELECT
                    im_category.id,
                    im_category.name,
                    im_category.description
                  FROM im_category
                    where im_category.id=$id")->fetch_assoc();

if (empty($category))
  die("Category not found!");

$rows = query("SELECT
                   im_item.id as item_id,
                   im_item.name as item_name,
                   im_item.description as item_description,
                   parent_location.id as location_id,
                   parent_location.name as location_name,
                   im_category.id as category_id,
                   im_category.name as category_name,
                   length(im_item.image) as image_size
                 FROM im_category, im_item
                 left join im_location parent_location on im_item.location_id=parent_location.id
                 WHERE
                   im_item.category_id = im_category.id and
                   im_item.home_id=".homeID()." and
                   im_item.deleted = false and
                   im_category.id=$id")->fetch_all(MYSQLI_ASSOC);

echo "<h1>Category: ".$category["name"]."</h1>";
echo $category['description'];

if (count($rows) != 0)
{
  echo "<table class=\"data-table\">";
  echo "<tr><th>Name</th><th>Description</th><th>image</th></tr>";
  foreach($rows as $row)
  {
    echo "<tr>";
    echo "<td>".itemLink($row["item_id"], $row["item_name"])."</td>";
    echo "<td>".locationLink($row["location_id"], $row["location_name"])."</td>";
    echo "<td>".itemLink($row["item_id"], itemImage($row['item_id'], $row['image_size'] > 0))."</td>";
    echo "</tr>";
  }
  echo "</table>";
}?>
