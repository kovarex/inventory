<?php
require("src/header.php");
echo "<h1>Users</h1>";

$users = query("SELECT *
               FROM
                 im_user,
                 im_home_user,
                 im_transaction
               WHERE
                 im_home_user.user_id = im_user.id and
                 im
                 im_home_user.home_id=".homeID())->fetch_all(MYSQLI_ASSOC);
echo "<table class=\"data-table\"><tr><th>Name</th><th>Transactions</th></tr>;
foreach($users as $user)
{
  echo "<tr><td>".$user["name"]."</td></tr>";
}
echo "</table>";
?>