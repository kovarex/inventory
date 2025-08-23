<?php
require("src/header.php");

if (!isset($_GET["id"]))
  die("ID of the object not provided");

$id = escape($_GET["id"]);

$result = query("SELECT
                   im_location.id,
                   im_location.name,
                   im_location.description
                 FROM im_location
                 WHERE
                   im_location.id=$id");

if ($result->num_rows == 0)
  die("Location not found!");

$item = $result->fetch_assoc();

echo "<h1>Location: ".$item["name"]."</h1>";
?>
<?= @$item['description'] ?>
<?php

$structuredData["name"] = $item["name"];
$locationPointers[$item["id"]] = &$structuredData;

function addToStructuredData($flatData)
{
  global $structuredData;
  global $locationPointers;

  foreach($flatData as $row)
  {
    $root = &$locationPointers[$row["item_location_id"]];
    assert(!empty($root));
    $root["items"][$row["item_id"]]["name"] = $row["item_name"];
    $root["items"][$row["item_id"]]["description"] = $row["item_description"];
  }
}

function buildLocationStructure($flatLocationData)
{
  global $structuredData;
  global $locationPointers;

  foreach($flatLocationData as $row)
  {
    $parent = &$structuredData;
    for ($i = 1; $i <= 3; $i++)
    {
      $locationID = $row["level{$i}_location_id"];
      if (!empty($locationID))
      {
        $parent["locations"][$locationID]["name"] = $row["level{$i}_location_name"];
        $parent = &$parent["locations"][$locationID];
        $locationPointers[$locationID] = &$parent;
      }
    }
  }
}

$flatLocationData = query("SELECT
                            level1_location.id as level1_location_id,
                            level1_location.name as level1_location_name,
                            level2_location.id as level2_location_id,
                            level2_location.name as level2_location_name,
                            level3_location.id as level3_location_id,
                            level3_location.name as level3_location_name
                           FROM
                             im_location as level1_location left join
                             im_location as level2_location on level2_location.parent_location_id = level1_location.id left join
                             im_location as level3_location on level3_location.parent_location_id = level2_location.id
                           WHERE
                             level1_location.parent_location_id=$id")->fetch_all(MYSQLI_ASSOC);

buildLocationStructure($flatLocationData);
foreach($locationPointers as $key=>$row)
  if (empty($locationList))
    $locationList = $key;
  else
    $locationList .= ",".$key;

$flatData = query("SELECT
                  im_item.id as item_id,
                  im_item.name as item_name,
                  im_item.description as item_description,
                  im_item.location_id as item_location_id
                FROM
                  im_item, im_location
                WHERE
                  im_item.location_id in ($locationList)")->fetch_all(MYSQLI_ASSOC);
addToStructuredData($flatData);

if (count($structuredData) != 0)
{
  function show($parentID, $structuredData)
  {
    if (empty($structuredData))
      return;

    echo "<li>".locationLink($parentID, $structuredData["name"]);
    if (!empty($structuredData["items"]))
    {
      echo "<ul>";
      foreach($structuredData["items"] as $key=>$row)
        echo "<li>".itemLink($key, $row["name"])."</li>";
      echo "</ul>";
    }
    if (!empty($structuredData["locations"]))
    {
      echo "<ul>";
      foreach($structuredData["locations"] as $key=>$row)
        show($key, $row);
      echo "</ul>";
    }
    echo "</li>";
  }

  echo "<ul>";
  show($item["id"], $structuredData);
  echo "</ul>";
}
?>

<?php require("src/footer.php"); ?>



