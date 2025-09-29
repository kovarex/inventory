<?php
if (!isset($_GET["id"]))
  die("ID of the object not provided");

$id = escape($_GET["id"]);

$result = query("SELECT
                   im_user.username
                 FROM
                   im_user
                 WHERE
                   im_user.id=".$id);

if ($result->num_rows == 0)
  die("User not found!");

$item = $result->fetch_assoc();

echo "<h1>User: ".$item["username"]."</h1>";

$rows = query("SELECT
                   im_transaction.*,
                   im_item.id as item_id,
                   im_item.name as item_name,
                   from_location.name as from_location_name,
                   to_location.name as to_location_name,
                   parent_from_location.name as parent_from_location_name,
                   parent_to_location.name as parent_to_location_name,
                   parent_location.name as parent_location_name,
                   im_user.username as user_name
                 FROM im_item, im_transaction
                 LEFT JOIN im_location from_location ON from_location.id=im_transaction.from_location_id
                 LEFT JOIN im_location to_location ON to_location.id=im_transaction.to_location_id
                 LEFT JOIN im_location parent_from_location ON parent_from_location.id=im_transaction.parent_from_location_id
                 LEFT JOIN im_location parent_to_location ON parent_to_location.id=im_transaction.parent_to_location_id
                 LEFT JOIN im_location parent_location ON parent_location.id=im_transaction.parent_location_id
                 LEFT JOIN im_user ON im_user.id=im_transaction.user_id
                 WHERE user_id=$id and im_transaction.item_id = im_item.id and im_item.home_id=".homeID()."
                 ORDER BY im_transaction.timestamp")->fetch_all(MYSQLI_ASSOC);

if (count($rows) != 0)
{
?>
  <table class="data-table">
    <tr>
      <th>Transaction</th><th>Comment</th><th>Item</th><th>Timestamp</th>
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
      echo "<td>".itemLink($row["item_id"], $row["item_name"])."</td>";
      echo "<td>".$row["timestamp"]."</td>";
      echo "</tr>";
    }
  echo "</table>";
}?>
