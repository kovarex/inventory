<?php
require_once("src/item_helper.php");
require_once("src/location_helper.php");

if (!isset($_GET["id"]))
  die("ID of the object not provided");

$id = escape($_GET["id"]);

$result = query("SELECT
                   im_location.id,
                   im_location.name,
                   im_location.description,
                   im_location.parent_location_id,
                   (SELECT parent_location.name FROM im_location AS parent_location WHERE im_location.parent_location_id=parent_location.id) AS parent_location_name,
                   length(im_location.image) > 0 as has_image
                 FROM im_location
                 WHERE
                   im_location.id=$id");

if ($result->num_rows == 0)
  die("Location not found!");

$location = $result->fetch_assoc();

echo "<h1>Location: ".$location["name"]."</h1>";
echo "<div>".$location["description"]."</div>";
echo "<div><button type=\"button\" onclick=\"showEditDialog(event);\">Edit</button></div>";

echo "<div id=\"edit-dialog\" style=\"position:absolute;background: white;display:none;\">";
locationForm("edit", $location, "location?id=".$_GET["id"]);
echo "</div>";

echo "<div>".locationImage($location["id"], $location["has_image"], "big")."</div>";

echo "<br/><br/>";
itemForm("add", NULL, "location?id=".$_GET["id"], $_GET["id"]);
echo "<br/><br/>";

$structuredData["name"] = $location["name"];
$structuredData["id"] = $location["id"];
$locationPointers[$location["id"]] = &$structuredData;

function addToStructuredData($flatData)
{
  global $structuredData;
  global $locationPointers;

  foreach($flatData as $row)
  {
    $root = &$locationPointers[$row["item_location_id"]];
    assert(!empty($root));
    $itemRef = &$root["items"][$row["item_id"]];
    $itemRef["id"] = $row["item_id"];
    $itemRef["name"] = $row["item_name"];
    $itemRef["description"] = $row["item_description"];
    $itemRef["has_image"] = $row["has_image"];
  }
}

buildLocationStructure(locationChildren(escape($_GET["id"])), $structuredData, $locationPointers);
foreach($locationPointers as $key=>$row)
  if (empty($locationList))
    $locationList = $key;
  else
    $locationList .= ",".$key;

$flatData = query("SELECT
                  im_item.id as item_id,
                  im_item.name as item_name,
                  im_item.description as item_description,
                  im_item.location_id as item_location_id,
                  length(im_item.image) > 0 as has_image
                FROM
                  im_item, im_location
                WHERE
                  im_item.location_id in ($locationList) AND im_item.deleted=false")->fetch_all(MYSQLI_ASSOC);
addToStructuredData($flatData);
sortLocationStructureRecursive($structuredData, $locationPointers);

if (count($structuredData) != 0)
{
  function show($structuredData)
  {
    if (empty($structuredData))
      return;
    echo "<li>".locationLink($structuredData["id"], $structuredData["name"]);
    if (!empty($structuredData["items"]))
    {
      echo "<ul>";
      foreach($structuredData["items"] as $row)
        echo "<li style=\" display: flex;flex-direction: row;align-items: center;\">".($row["has_image"] ? itemLink($row["id"], itemImage($row["id"], $row["name"])) : "").itemLink($row["id"], $row["name"])."</li>";
      echo "</ul>";
    }
    if (!empty($structuredData["locations"]))
    {
      echo "<ul>";
      foreach($structuredData["locations"] as $row)
        show($row);
      echo "</ul>";
    }
    echo "</li>";
  }

  echo "<div>".locationLink($location["parent_location_id"],$location["parent_location_name"])."</div>";

  echo "<ul>";
  show($structuredData);
  echo "</ul>";
}
?>
