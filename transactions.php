<?php
require("src/header.php");
require("src/transaction_log.php");
echo "<h1>Transactions</h1>";

$rows = query("SELECT
                 im_transaction.*,
                 im_item.id as item_id,
                 im_item.name as item_name,
                 from_location.name as from_location_name,
                 to_location.name as to_location_name,
                 parent_from_location.name as parent_from_location_name,
                 parent_to_location.name as parent_to_location_name,
                 parent_location.name as parent_location_name,
                 im_user.id as user_id,
                 im_user.username as user_name,
                 im_action.name as action_name
               FROM im_item, im_transaction
               LEFT JOIN im_location from_location ON from_location.id=im_transaction.from_location_id
               LEFT JOIN im_location to_location ON to_location.id=im_transaction.to_location_id
               LEFT JOIN im_location parent_from_location ON parent_from_location.id=im_transaction.parent_from_location_id
               LEFT JOIN im_location parent_to_location ON parent_to_location.id=im_transaction.parent_to_location_id
               LEFT JOIN im_location parent_location ON parent_location.id=im_transaction.parent_location_id
               LEFT JOIN im_user ON im_user.id=im_transaction.user_id
               LEFT JOIN im_action ON im_action.id=im_transaction.action_id
               WHERE im_transaction.item_id = im_item.id and im_item.home_id=".homeID()."
               ORDER BY im_transaction.timestamp DESC")->fetch_all(MYSQLI_ASSOC);

if (count($rows) != 0)
{
  echo "<table class=\"data-table\">";
  echo "<tr><th>Transaction</th><th>Comment</th><th>User</th><th>Timestamp</th></tr>";
  foreach($rows as $row)
  {
    echo "<tr>";
    echo "<td>".generateTransactionDescription($row, "none")."</td>";
    echo "<td>".$row["comment"]."</td>";
    echo "<td>".userLink($row["user_id"], $row["user_name"])."</td>";
    echo "<td>".$row["timestamp"]."</td>";
    echo "</tr>";
  }
  echo "</table>";
}
require("src/footer.php");
?>
