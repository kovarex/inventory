<?php
use PHPUnit\Framework\TestCase;

abstract class InventoryTestCase extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    if (defined("TEST_SERVER_LOG"))
    {
      clearstatcache();
      $this->serverLogOffset = filesize(TEST_SERVER_LOG);
    }
  }

  // Any PHP warning, notice or error the application logged during the test fails it.
  protected function assertPostConditions(): void
  {
    parent::assertPostConditions();
    if (!defined("TEST_SERVER_LOG"))
      return;
    $log = file_get_contents(TEST_SERVER_LOG, false, null, $this->serverLogOffset);
    preg_match_all("/PHP (Warning|Notice|Deprecated|Fatal error|Parse error).*/", $log, $errors);
    $this->assertSame([], $errors[0], "The application produced PHP errors.");
  }

  protected function assertPageOk(HttpResponse $response): void
  {
    $this->assertSame(200, $response->status, $response->body);
    $this->assertStringNotContainsString("Sql error", $response->body);
  }

  private int $serverLogOffset = 0;
}
