<?php
// Bootstrap for PHPUnit.
// Unless TEST_APP_URL is given, a PHP built-in web server is started for the duration of the test run,
// serving this checkout with the same src/config.php the tests use.
require_once __DIR__."/TestDb.php";
require_once __DIR__."/HttpResponse.php";
require_once __DIR__."/HttpClient.php";
require_once __DIR__."/ContextPreparator.php";
require_once __DIR__."/TestCase/InventoryTestCase.php";

TestDb::connection(); // fail early on a missing or non-test config

if (getenv("TEST_APP_URL"))
  define("TEST_APP_URL", rtrim(getenv("TEST_APP_URL"), "/"));
else
{
  $socket = stream_socket_server("tcp://127.0.0.1:0");
  $port = (int)substr(strrchr(stream_socket_get_name($socket, false), ":"), 1);
  fclose($socket);
  define("TEST_APP_URL", "http://127.0.0.1:".$port);

  $serverLog = sys_get_temp_dir()."/inventory-test-server.log";
  define("TEST_SERVER_LOG", $serverLog); // PHP errors of the application end up here, the tests check it
  $server = proc_open([PHP_BINARY,
                       "-d", "display_errors=0", "-d", "log_errors=1", "-d", "error_reporting=".E_ALL,
                       "-S", "127.0.0.1:".$port, "-t", dirname(__DIR__), __DIR__."/router.php"],
                      [0 => ["pipe", "r"], 1 => ["file", $serverLog, "w"], 2 => ["file", $serverLog, "w"]],
                      $pipes, dirname(__DIR__));
  register_shutdown_function(function () use ($server)
  {
    proc_terminate($server);
    proc_close($server);
  });

  for ($i = 0; $i < 100; $i++)
  {
    $connection = @fsockopen("127.0.0.1", $port);
    if ($connection)
    {
      fclose($connection);
      break;
    }
    usleep(50000);
  }
  if (!$connection)
    throw new Exception("Test server didn't start, see ".$serverLog);
}
