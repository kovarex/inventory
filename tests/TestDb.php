<?php
// Direct database access for the tests, using the same src/config.php as the application.
class TestDb
{
  public static function connection(): mysqli
  {
    if (self::$db)
      return self::$db;
    $configFile = dirname(__DIR__)."/src/config.php";
    if (!is_file($configFile))
      throw new Exception("src/config.php is missing, see tests/README.md.");
    require $configFile;
    if (empty($allowTestDataWipe))
      throw new Exception("src/config.php doesn't set \$allowTestDataWipe = true, refusing to run tests that wipe the database ".$dbName.".");
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    self::$db = new mysqli($dbServer, $dbUser, $dbPassword, $dbName);
    self::$db->set_charset("utf8");
    return self::$db;
  }

  public static function query(string $sql): mysqli_result|bool
  {
    return self::connection()->query($sql);
  }

  public static function insert(string $table, array $values): int
  {
    $columns = [];
    $escapedValues = [];
    foreach ($values as $column => $value)
    {
      $columns[] = "`".$column."`";
      $escapedValues[] = self::escape($value);
    }
    self::query("INSERT INTO `".$table."` (".implode(",", $columns).") VALUES (".implode(",", $escapedValues).")");
    return self::connection()->insert_id;
  }

  public static function row(string $sql): ?array
  {
    return self::query($sql)->fetch_assoc();
  }

  public static function rows(string $sql): array
  {
    return self::query($sql)->fetch_all(MYSQLI_ASSOC);
  }

  public static function value(string $sql): mixed
  {
    $row = self::query($sql)->fetch_row();
    return $row ? $row[0] : null;
  }

  public static function escape(mixed $value): string
  {
    if ($value === null)
      return "NULL";
    if (is_bool($value))
      return $value ? "1" : "0";
    if (is_int($value))
      return (string)$value;
    return "'".self::connection()->real_escape_string($value)."'";
  }

  private static ?mysqli $db = null;
}
