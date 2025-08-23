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

function query($query, $show = false)
{
  global $db;
  if ($show)
    echo "Debug query: ".$query;
  $result = $db->query($query);
  if (!empty($db->error))
  {
    echo "<div>Sql error:".$db->error."</div>";
    echo "<pre>Query:".$query."</pre>";
    die();
  }
  return $result;
}

function escape($input)
{
  global $db;
  return "'".$db->real_escape_string($input)."'";
}

function locationChildren($id) {
  return query("SELECT
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
}
?>
