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

$rows = query("SELECT
                  im_item.*
                FROM im_item
                WHERE im_item.location_id=$id")->fetch_all(MYSQLI_ASSOC);
if (count($rows) != 0)
{
  echo "<table class=\"data-table\"><tr><th>Name</th></tr>";
  foreach($rows as $row)
  {
    echo "<tr><td>";
    echo "<a href=\"item.php?id=".$row["id"]."\">".$row["name"]."</a>";
    echo "</td></tr>";
  }
  echo "</table>";
}
/*
log will be here
$result = query("SELECT
                       im_transaction.*,
                       from_location.name as from_location_name,
                       to_location.name as to_location_name,
                       parent_from_location.name as parent_from_location_name,
                       parent_to_location.name as parent_to_location_name,
                       parent_location.name as parent_location_name FROM im_transaction
                 LEFT JOIN im_location from_location ON from_location.id=im_transaction.from_location_id
                 LEFT JOIN im_location to_location ON to_location.id=im_transaction.to_location_id
                 LEFT JOIN im_location parent_from_location ON parent_from_location.id=im_transaction.parent_from_location_id
                 LEFT JOIN im_location parent_to_location ON parent_to_location.id=im_transaction.parent_to_location_id
                 LEFT JOIN im_location parent_location ON parent_location.id=im_transaction.parent_location_id
                 WHERE from_location_id=$id or to_location_id=$id");

$rows = $result->fetch_all(MYSQLI_ASSOC);

if (count($rows) != 0)
{
?>
  <table class="data-table">
    <tr>
      <th>Transaction</th><th>Comment</th>
    </tr>
  <?php
    foreach($rows as $row)
    {
      echo "<tr><td>";
      if (empty($row["from_location_id"]) and !empty($row["to_location_id"]))
        echo "in ".$row["to_location_name"];
      else if (!empty($row["from_location_id"]) and !empty($row["to_location_id"]))
        echo "Moved from ".$row["from_location_name"]." to ".$row["to_location_name"];
      else
        echo "Unknown operation";
      echo "</td><td>".$row["comment"]."</td>";
      echo "</tr>";
    }
  ?>
  </table>*/
?>
<?php require("src/footer.php"); ?>



