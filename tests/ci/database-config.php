<?php
// Database config used by the CI (copied to src/config.php by the workflow).
$dbServer = "127.0.0.1";
$dbUser = "root";
$dbPassword = "root";
$dbName = "inventory_test";

// The tests wipe all the data in this database before every test, they refuse to run without this flag.
$allowTestDataWipe = true;
