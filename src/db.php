<?php
require_once("config.php");

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
    echo "Debug query: ".$query."<br/>\n";
  try
  {
    $time_start = microtime(true);
    $result = $db->query($query);
    $time_end = microtime(true);
    $execution_time = ($time_end - $time_start);
    if (@$_SESSION["statistics"])
      echo "Query:".$query."<br/>\nTook ".round($execution_time, 4)." seconds<br/>\n";

    if (!empty($db->error))
    {
      echo "<div>Sql error:".$db->error."</div>\n";
      echo "<pre>Query:".$query."</pre>\n";
      die();
    }
    return $result;
  }
  catch (Exception $e)
  {
      echo "<div>Sql error:".$e->getMessage()."</div>\n";
      echo "<pre>Query:".$query."</pre>\n";
      die();
  }
}

function beginTransaction()
{
  global $db;
  $db->begin_transaction();
}

function commitTransaction()
{
  global $db;
  $db->commit();
}

function lastInsertID()
{
  global $db;
  return $db->insert_id;
}

function escape($input)
{
  if ($input == NULL)
    return "NULL";
  global $db;
  return "'".$db->real_escape_string($input)."'";
}
?>
