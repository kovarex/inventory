<?php
require("src/header.php");
echo "<h1>Users</h1>";

$users = query("SELECT
                 im_user.username as username,
                 count(im_transaction.id) as transaction_count
               FROM
                 im_user left join
                 im_transaction on im_transaction.user_id = im_user.id,
                 im_home_user
               WHERE
                 im_home_user.user_id = im_user.id and
                 im_home_user.home_id=".homeID()."
               GROUP BY
                 im_user.id")->fetch_all(MYSQLI_ASSOC);
echo "<table class=\"data-table\"><tr><th>Username</th><th>Transactions</th></tr>";
foreach($users as $user)
{
  echo "<tr><td>".$user["username"]."</td><td>".$user["transaction_count"]."</td></tr>";
}
echo "</table>";
?>