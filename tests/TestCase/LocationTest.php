<?php
class LocationTest extends InventoryTestCase
{
  public function testLocationsPage()
  {
    $context = new ContextPreparator(['locations' => [['name' => 'Garage', 'children' => ['Top shelf']]]]);
    $response = $context->client->get('/locations');
    $this->assertPageOk($response);
    $this->assertStringContainsString('Garage', $response->body);
    $this->assertStringContainsString('Top shelf', $response->body);
  }

  public function testLocationPage()
  {
    $context = new ContextPreparator(['items' => [['name' => 'Hammer', 'location' => 'Garage']]]);
    $response = $context->client->get('/location', ['id' => $context->location('Garage')['id']]);
    $this->assertPageOk($response);
    $this->assertStringContainsString('Hammer', $response->body);
  }

  public function testAddLocation()
  {
    $context = new ContextPreparator(['locations' => ['Garage']]);
    $response = $context->client->post('/add_location', [
      'name' => 'Top shelf',
      'description' => 'Above the car',
      'parent_id' => $context->location('Garage')['id'],
      'redirect' => '/locations']);
    $this->assertSame('/locations', $response->location());
    $location = TestDb::row("SELECT * FROM im_location WHERE name='Top shelf'");
    $this->assertEquals($context->location('Garage')['id'], $location['parent_location_id']);
    $this->assertEquals($context->home['id'], $location['home_id']);
  }

  public function testMoveLocationLogsTransactionForItsItems()
  {
    $context = new ContextPreparator([
      'locations' => ['Garage', 'Cellar', ['name' => 'Toolbox', 'children' => ['Drawer']]],
      'items' => [['name' => 'Hammer', 'location' => 'Drawer']]]);
    $response = $context->client->post('/edit_location', [
      'id' => $context->location('Toolbox')['id'],
      'name' => 'Toolbox',
      'description' => '',
      'parent_id' => $context->location('Cellar')['id'],
      'comment' => 'tidying',
      'redirect' => '/locations']);
    $this->assertSame('/locations', $response->location());
    $this->assertEquals($context->location('Cellar')['id'], TestDb::value("SELECT parent_location_id FROM im_location WHERE id=".$context->location('Toolbox')['id']));
    $transaction = TestDb::row("SELECT * FROM im_transaction WHERE item_id=".$context->item('Hammer')['id']);
    $this->assertEquals($context->location('Cellar')['id'], $transaction['parent_to_location_id']);
    $this->assertSame('tidying', $transaction['comment']);
  }

  public function testCantMoveLocationIntoItsChild()
  {
    $context = new ContextPreparator(['locations' => [['name' => 'Toolbox', 'children' => ['Drawer']]]]);
    $context->client->post('/edit_location', [
      'id' => $context->location('Toolbox')['id'],
      'name' => 'Toolbox',
      'description' => '',
      'parent_id' => $context->location('Drawer')['id'],
      'redirect' => '/locations']);
    $this->assertNull(TestDb::value("SELECT parent_location_id FROM im_location WHERE id=".$context->location('Toolbox')['id']));
  }
}
