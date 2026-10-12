<?php
class ItemTest extends InventoryTestCase
{
  public function testItemsPage()
  {
    $context = new ContextPreparator(['items' => ['Hammer', ['name' => 'Saw', 'deleted' => true]]]);
    $response = $context->client->get('/items');
    $this->assertPageOk($response);
    $this->assertStringContainsString('Hammer', $response->body);
    $this->assertStringNotContainsString('Saw', $response->body);
  }

  public function testItemPage()
  {
    $context = new ContextPreparator(['items' => [['name' => 'Hammer', 'location' => 'Garage', 'category' => 'Tools', 'author' => 'Thor']]]);
    $response = $context->client->get('/item', ['id' => $context->item('Hammer')['id']]);
    $this->assertPageOk($response);
    $this->assertStringContainsString('Item: Hammer', $response->body);
    $this->assertStringContainsString('Garage', $response->body);
    $this->assertStringContainsString('Tools', $response->body);
    $this->assertStringContainsString('Thor', $response->body);
  }

  public function testItemOfOtherHomeIsNotShown()
  {
    $context = new ContextPreparator(['items' => ['Hammer'], 'other-homes' => [['name' => 'Cottage']]]);
    $context->client->post('/homes', ['action' => 'activate', 'id' => $context->otherHomes[0]['id']]);
    $this->assertStringContainsString('Item not found!', $context->client->get('/item', ['id' => $context->item('Hammer')['id']])->body);
  }

  public function testAddItem()
  {
    $context = new ContextPreparator(['locations' => ['Garage'], 'categories' => ['Tools']]);
    $response = $context->client->post('/add_item', [
      'name' => 'Hammer',
      'description' => 'Big one',
      'author' => '',
      'location_id' => $context->location('Garage')['id'],
      'category_id' => $context->category('Tools')['id'],
      'comment' => 'bought',
      'redirect' => '/items']);
    $this->assertSame('/items', $response->location());
    $item = TestDb::row("SELECT * FROM im_item WHERE name='Hammer'");
    $this->assertSame('Big one', $item['description']);
    $this->assertNull($item['image']);
    $transaction = TestDb::row("SELECT * FROM im_transaction WHERE item_id=".$item['id']);
    $this->assertEquals($context->location('Garage')['id'], $transaction['to_location_id']);
    $this->assertSame('bought', $transaction['comment']);
  }

  public function testAddItemWithImage()
  {
    $context = new ContextPreparator(['locations' => ['Garage'], 'categories' => ['Tools']]);
    $imageFile = $this->createPng(800, 600);
    $response = $context->client->post('/add_item', [
      'name' => 'Hammer',
      'description' => '',
      'author' => '',
      'location_id' => $context->location('Garage')['id'],
      'category_id' => $context->category('Tools')['id'],
      'comment' => '',
      'redirect' => '/items'], ['image' => $imageFile]);
    $this->assertSame('/items', $response->location(), $response->body);
    $item = TestDb::row("SELECT * FROM im_item WHERE name='Hammer'");
    $this->assertSame([500, 375], array_slice(getimagesizefromstring($item['image']), 0, 2));
    $this->assertSame([100, 75], array_slice(getimagesizefromstring($item['thumbnail']), 0, 2));

    $image = $context->client->get('/image', ['source' => 'item', 'id' => $item['id'], 'type' => 'thumbnail']);
    $this->assertSame('image/jpeg', $image->headers['content-type']);
    $this->assertSame($item['thumbnail'], $image->body);
  }

  public function testMoveItem()
  {
    $context = new ContextPreparator(['locations' => ['Cellar'], 'items' => [['name' => 'Hammer', 'location' => 'Garage', 'category' => 'Tools']]]);
    $response = $context->client->post('/edit_item', [
      'id' => $context->item('Hammer')['id'],
      'name' => 'Hammer',
      'description' => '',
      'author' => '',
      'location_id' => $context->location('Cellar')['id'],
      'category_id' => $context->category('Tools')['id'],
      'comment' => 'moved',
      'redirect' => '/items']);
    $this->assertSame('/items', $response->location(), $response->body);
    $this->assertEquals($context->location('Cellar')['id'], $context->reloadItem('Hammer')['location_id']);
    $transaction = TestDb::row("SELECT * FROM im_transaction WHERE item_id=".$context->item('Hammer')['id']);
    $this->assertEquals($context->location('Garage')['id'], $transaction['from_location_id']);
    $this->assertEquals($context->location('Cellar')['id'], $transaction['to_location_id']);
  }

  public function testCantEditItemOfOtherHome()
  {
    $context = new ContextPreparator(['locations' => ['Garage'], 'categories' => ['Tools'], 'other-users' => [['username' => 'honza']]]);
    $foreignHome = TestDb::insert('im_home', ['name' => 'Foreign', 'description' => '']);
    $foreignLocation = TestDb::insert('im_location', ['home_id' => $foreignHome, 'name' => 'Attic']);
    $foreignCategory = TestDb::insert('im_category', ['home_id' => $foreignHome, 'name' => 'Toys']);
    $foreignItem = TestDb::insert('im_item', ['home_id' => $foreignHome, 'name' => 'Doll', 'location_id' => $foreignLocation, 'category_id' => $foreignCategory]);
    $context->client->post('/edit_item', [
      'id' => $foreignItem,
      'name' => 'Stolen doll',
      'description' => '',
      'author' => '',
      'location_id' => $context->location('Garage')['id'],
      'category_id' => $context->category('Tools')['id'],
      'comment' => '',
      'redirect' => '/items']);
    $this->assertSame('Doll', TestDb::value("SELECT name FROM im_item WHERE id=".$foreignItem));
    $this->assertSame(0, (int)TestDb::value("SELECT COUNT(*) FROM im_transaction"));
  }

  public function testDeleteAndRestoreItem()
  {
    $context = new ContextPreparator(['items' => ['Hammer']]);
    $id = $context->item('Hammer')['id'];
    $this->assertSame('/items', $context->client->post('/delete_item', ['id' => $id, 'comment' => 'broken', 'redirect' => '/items'])->location());
    $this->assertEquals(1, $context->reloadItem('Hammer')['deleted']);
    $this->assertSame('/items', $context->client->post('/restore_item', ['id' => $id, 'comment' => 'fixed', 'redirect' => '/items'])->location());
    $this->assertEquals(0, $context->reloadItem('Hammer')['deleted']);
    $this->assertSame(['broken', 'fixed'], array_column(TestDb::rows("SELECT comment FROM im_transaction WHERE item_id=$id ORDER BY id"), 'comment'));
  }

  public function testTransactionsPage()
  {
    $context = new ContextPreparator(['items' => ['Hammer']]);
    $context->client->post('/delete_item', ['id' => $context->item('Hammer')['id'], 'comment' => 'broken', 'redirect' => '/items']);
    $response = $context->client->get('/transactions');
    $this->assertPageOk($response);
    $this->assertStringContainsString('broken', $response->body);
  }

  private function createPng(int $width, int $height): string
  {
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 200, 100, 50));
    $file = tempnam(sys_get_temp_dir(), "inventory-test-image").".png";
    imagepng($image, $file);
    return $file;
  }
}
