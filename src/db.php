<?php
require("config.php");
require_once("constants.php");

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
  if (empty($input))
    return "NULL";
  global $db;
  return "'".$db->real_escape_string($input)."'";
}

function locationChildren($id)
{
  $columns=[];
  $joins=["im_location as level1_location"];
  for ($f = 1;$f <= LOCATION_CHILDREN_DEPTH; $f++)
  {
    $columns[] = "level{$f}_location.id as level{$f}_location_id,
                  level{$f}_location.name as level{$f}_location_name";
    if ($f > 1) $joins[] = "im_location as level{$f}_location on level{$f}_location.parent_location_id = level".($f - 1)."_location.id";
  }
  return query("SELECT 
               ".implode(",",$columns)." 
               FROM 
               ".implode(" left join ",$joins). " 
               WHERE level1_location.parent_location_id=".$id)->fetch_all(MYSQLI_ASSOC);
}

function locationChildrenFlat($id)
{
  $children = locationChildren($id);
  $flatLocations=[$id];
  foreach($children as $row)
  {
    for ($i = 1; $i <= LOCATION_CHILDREN_DEPTH; $i++)
    {
      $locationID = $row["level{$i}_location_id"];
      if (!empty($locationID))
        $flatLocations[] = $locationID;
    }
  }
  return $flatLocations;
}
?>
