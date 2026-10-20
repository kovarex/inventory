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
    $this->assertStringContainsString('<div class="title-row"><h1>Hammer</h1><button', $response->body);
    $this->assertStringContainsString('<div class="breadcrumb"><a href="location?id='.$context->location('Garage')['id'].'">Garage</a> ›</div>', $response->body);
    $this->assertStringContainsString('Tools', $response->body);
    $this->assertStringContainsString('Thor', $response->body);
  }

  public function testItemPageShowsThePathToItsLocation()
  {
    $context = new ContextPreparator([
      'locations' => [['name' => 'House', 'children' => [['name' => 'Floor 1', 'children' => ['Kitchen']]]]],
      'items' => [['name' => 'Spoon', 'location' => 'Kitchen']]]);
    $body = $context->client->get('/item', ['id' => $context->item('Spoon')['id']])->body;
    $this->assertStringContainsString('<div class="breadcrumb"><a href="location?id='.$context->location('House')['id'].'">House</a> › '.
                                      '<a href="location?id='.$context->location('Floor 1')['id'].'">Floor 1</a> › '.
                                      '<a href="location?id='.$context->location('Kitchen')['id'].'">Kitchen</a> ›</div>', $body);
    // The location is in the path only, not repeated in the table below
    $this->assertSame(1, substr_count($body, 'href="location?id='.$context->location('Kitchen')['id'].'"'));
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

  public function testAddItemWithRotatedPhonePhoto()
  {
    $context = new ContextPreparator(['locations' => ['Garage'], 'categories' => ['Tools']]);
    // Taken in portrait: stored as landscape (left half red, right half blue) with EXIF "rotate 90° clockwise"
    $imageFile = $this->createPhonePhoto(4032, 3024, 6);
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
    $this->assertSame([375, 500], array_slice(getimagesizefromstring($item['image']), 0, 2));
    $this->assertSame([75, 100], array_slice(getimagesizefromstring($item['thumbnail']), 0, 2));

    $image = imagecreatefromstring($item['image']);
    $this->assertGreaterThan(200, imagecolorat($image, 187, 100) >> 16 & 0xFF, "the top should be red");
    $this->assertGreaterThan(200, imagecolorat($image, 187, 400) & 0xFF, "the bottom should be blue");
  }

  public function testUnreadableImageIsReported()
  {
    $context = new ContextPreparator(['locations' => ['Garage'], 'categories' => ['Tools']]);
    // A valid JPEG header, so getimagesize() accepts it, but the data is cut off
    $imageFile = tempnam(sys_get_temp_dir(), "inventory-test-image").".jpg";
    file_put_contents($imageFile, substr(file_get_contents($this->createPhonePhoto(800, 600, 1)), 0, 300));
    $response = $context->client->post('/add_item', [
      'name' => 'Hammer',
      'description' => '',
      'author' => '',
      'location_id' => $context->location('Garage')['id'],
      'category_id' => $context->category('Tools')['id'],
      'comment' => '',
      'redirect' => '/items'], ['image' => $imageFile]);
    $this->assertStringContainsString("The image couldn't be read", $response->body);
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

  private function createPhonePhoto(int $width, int $height, int $exifOrientation): string
  {
    $image = imagecreatetruecolor($width, $height);
    imagefilledrectangle($image, 0, 0, $width / 2 - 1, $height - 1, imagecolorallocate($image, 255, 0, 0));
    imagefilledrectangle($image, $width / 2, 0, $width - 1, $height - 1, imagecolorallocate($image, 0, 0, 255));
    ob_start();
    imagejpeg($image, NULL, 90);
    $jpeg = ob_get_clean();
    // EXIF segment with only the Orientation tag, inserted right after the JPEG start marker
    $tiff = "II*\0".pack("V", 8).pack("v", 1).pack("vvVvv", 0x0112, 3, 1, $exifOrientation, 0).pack("V", 0);
    $exif = "Exif\0\0".$tiff;
    $jpeg = substr($jpeg, 0, 2)."\xFF\xE1".pack("n", strlen($exif) + 2).$exif.substr($jpeg, 2);
    $file = tempnam(sys_get_temp_dir(), "inventory-test-image").".jpg";
    file_put_contents($file, $jpeg);
    return $file;
  }
}
