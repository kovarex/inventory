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
}
