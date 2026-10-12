<?php
// Router for the PHP built-in web server, used when running the tests.
// Mirrors the production setup: existing files are served directly, everything else goes through start.php.
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
if ($path != "/" and is_file(dirname(__DIR__).$path))
  return false;
chdir(dirname(__DIR__));
require "start.php";
