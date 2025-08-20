<?php
require("config.php");

$db = new mysqli($dbServer, $dbUser, $dbPassword, $dbName);
if ($db->connect_error)
  die("Connection failed: " . $db->connect_error);

function multi_query_and_clear($query)
{
  global $db;
  $db->multi_query($query);
  while ($db->next_result())
  {
    // flushing results
    if (!empty($db->error))
      die($db->error);
  }
}

function query($query)
{
  global $db;
  $result = $db->query($query);
  if (!empty($db->error))
  {
    echo "<div>Sql error:".$db->error."</div>";
    echo "<pre>Query:".$query."</pre>";
    die();
  }
  return $result;
}
?>
