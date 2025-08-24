<?php
require("src/header.php");
echo "<h1>Users</h1>";

$users = query("SELECT
                 im_user.username as username,
                 count(t.transaction_id) as transaction_count
               FROM
                 im_home_user,
                 im_user left join
                 (SELECT
                   im_transaction.id as transaction_id,
                   im_transaction.user_id as user_id
                 FROM
                   im_transaction, im_item
                 WHERE
                   im_transaction.item_id = im_item.id and
                   im_item.home_id = ".homeID()."
                 ) t on im_user.id = t.user_id
               WHERE
                 im_home_user.user_id = im_user.id and
                 im_home_user.home_id = ".homeID()."
               GROUP BY
                 im_user.id")->fetch_all(MYSQLI_ASSOC);
echo "<table class=\"data-table\"><tr><th>Username</th><th>Transactions</th></tr>";
foreach($users as $user)
{
  echo "<tr><td>".$user["username"]."</td><td>".$user["transaction_count"]."</td></tr>";
}
echo "</table>";
?>