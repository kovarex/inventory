<?php
// Every page renders without PHP warnings or SQL errors.
class PagesTest extends InventoryTestCase
{
  public static function pages(): array
  {
    return array_map(fn($page) => [$page], ['/', '/items', '/items?deleted=true', '/homes', '/categories', '/locations', '/users', '/transactions']);
  }

  #[PHPUnit\Framework\Attributes\DataProvider('pages')]
  public function testPageRenders(string $page)
  {
    $context = new ContextPreparator(['items' => [['name' => 'Hammer', 'location' => 'Garage', 'category' => 'Tools']]]);
    $this->assertPageOk($context->client->get($page));
  }

  public function testCategoryPage()
  {
    $context = new ContextPreparator(['items' => [['name' => 'Hammer', 'category' => 'Tools']]]);
    $response = $context->client->get('/category', ['id' => $context->category('Tools')['id']]);
    $this->assertPageOk($response);
    $this->assertStringContainsString('Hammer', $response->body);
  }

  public function testUserPage()
  {
    $context = new ContextPreparator();
    $this->assertPageOk($context->client->get('/user', ['id' => $context->user['id']]));
  }

  public function testStylesheetAndScriptLoad()
  {
    $context = new ContextPreparator();
    $body = $context->client->get('/')->body;
    // The version parameter makes browsers load the new file after each change
    $this->assertMatchesRegularExpression('#<link rel="stylesheet" href="([^"]*/style.css)\?v=\d+"#', $body);
    $this->assertMatchesRegularExpression('#<script src="([^"]*/common.js)\?v=\d+"#', $body);
    preg_match('#href="([^"]*/style.css)\?#', $body, $style);
    preg_match('#<script src="([^"]*/common.js)\?#', $body, $script);
    foreach ([$style[1], $script[1]] as $url)
      $this->assertSame(200, $context->client->get('/'.ltrim(preg_replace('#^\./#', '', $url), '/'))->status, $url);
  }
}
