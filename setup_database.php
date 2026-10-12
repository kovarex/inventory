#!/usr/bin/env php
<?php
// Creates or upgrades the database configured in src/config.php: ./setup_database.php [host]
// When config.php chooses the database by $_SERVER["HTTP_HOST"], pass the site's host name,
// e.g. ./setup_database.php test.im.kovarex.com
//
// The schema lives in migrations/*.sql, applied in the order of their file names.
// Applied migrations are recorded in im_migration, so running this again only applies the new ones.
// A database created before migrations existed (it has the tables but no im_migration) gets the initial
// migration marked as applied, and only the later ones run.
if (PHP_SAPI != "cli")
  die("Run from the command line: php setup_database.php\n");

chdir(__DIR__);
$_SERVER["HTTP_HOST"] = $argv[1] ?? "";
require "src/config.php";
if (empty($dbName))
{
  echo "src/config.php didn't set a database".(empty($argv[1]) ? ", if it depends on the host, pass it: ./setup_database.php im.kovarex.com" : " for host ".$argv[1]).".\n";
  exit(1);
}
echo "Database ".$dbName." on ".$dbServer."\n";
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli($dbServer, $dbUser, $dbPassword, $dbName);
$db->set_charset("utf8");

function tableExists($db, $table)
{
  return $db->query("SHOW TABLES LIKE '".$db->real_escape_string($table)."'")->num_rows > 0;
}

function runSqlFile($db, $file)
{
  $db->multi_query(file_get_contents($file));
  do
  {
    if ($result = $db->store_result())
      $result->free();
  } while ($db->more_results() && $db->next_result()); // throws on the first failing statement
}

$migrations = glob("migrations/*.sql");
sort($migrations);

if (!tableExists($db, "im_migration"))
{
  $existingDatabase = tableExists($db, "im_user");
  $db->query("CREATE TABLE im_migration (
                name varchar(255) NOT NULL,
                applied timestamp NOT NULL default CURRENT_TIMESTAMP,
                PRIMARY KEY (name)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
  if ($existingDatabase)
  {
    $initial = basename($migrations[0]);
    $db->query("INSERT INTO im_migration(name) VALUES('".$db->real_escape_string($initial)."')");
    echo "Existing database without migration records, marked ".$initial." as applied.\n";
  }
}

$applied = array_column($db->query("SELECT name FROM im_migration")->fetch_all(MYSQLI_ASSOC), "name");
$count = 0;
foreach ($migrations as $file)
{
  $name = basename($file);
  if (in_array($name, $applied))
    continue;
  echo "Applying ".$name."... ";
  try
  {
    runSqlFile($db, $file);
  }
  catch (mysqli_sql_exception $e)
  {
    echo "FAILED\n".$e->getMessage()."\n";
    echo "Statements of this migration before the failing one may have been applied already (MySQL can't roll back schema changes).\n";
    exit(1);
  }
  $db->query("INSERT INTO im_migration(name) VALUES('".$db->real_escape_string($name)."')");
  echo "done\n";
  $count++;
}
echo $count ? "Applied ".$count." migration(s) to ".$dbName.".\n" : "Database ".$dbName." is up to date.\n";
