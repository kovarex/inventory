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

  public function testLocationPageLayout()
  {
    $context = new ContextPreparator([
      'locations' => [['name' => 'House', 'children' => [['name' => 'Floor 1', 'children' => [['name' => 'Kitchen', 'children' => ['Drawer']]]], 'Attic']]],
      'items' => [['name' => 'Spoon', 'location' => 'Drawer'], ['name' => 'Doormat', 'location' => 'House']]]);
    $response = $context->client->get('/location', ['id' => $context->location('House')['id']]);
    $this->assertPageOk($response);
    // Just the name, with the buttons next to it, and the forms in dialogs
    $this->assertStringContainsString('<div class="title-row"><h1>House</h1><button', $response->body);
    $this->assertStringNotContainsString('Location:', $response->body);
    $this->assertMatchesRegularExpression('#<div id="add-item-dialog" class="dialog" style="display:none;">\s*<form[^>]*action="add_item"#', $response->body);
    // The items right here are listed, the sub-locations are collapsed with the count of the items inside
    $this->assertStringContainsString('Doormat', $response->body);
    $this->assertStringContainsString('<li><details><summary><a href="location?id='.$context->location('Floor 1')['id'].'">Floor 1</a> <span class="item-count">(1 item)</span></summary>', $response->body);
    $this->assertStringNotContainsString('<details open', $response->body);
    $this->assertStringContainsString('<li class="location-tree-leaf"><a href="location?id='.$context->location('Attic')['id'].'">Attic</a> <span class="item-count">(0 items)</span></li>', $response->body);
    $this->assertStringContainsString('Spoon', $response->body);
  }

  public function testHeaderSearchesItems()
  {
    $context = new ContextPreparator(['items' => ['Hammer', 'Saw']]);
    $body = $context->client->get('/locations')->body;
    $this->assertStringContainsString('<form method="get" action="/items" class="header-search">', $body);
    $response = $context->client->get('/items', ['search' => 'Ham', 'action' => 'search']);
    $this->assertPageOk($response);
    $this->assertStringContainsString('Hammer', $response->body);
    $this->assertStringNotContainsString('>Saw<', $response->body);
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

  public function testEditLocationCode()
  {
    $context = new ContextPreparator(['locations' => ['Skříň 1A']]);
    $id = $context->location('Skříň 1A')['id'];
    $edit = fn($code) => $context->client->post('/edit_location', [
      'id' => $id, 'name' => 'Skříň 1A', 'code' => $code, 'description' => '', 'parent_id' => '', 'redirect' => '/locations']);
    $edit(' 1A ');
    $this->assertSame('1A', TestDb::value("SELECT code FROM im_location WHERE id=".$id));
    $this->assertStringContainsString('name="code" value="1A"', $context->client->get('/location', ['id' => $id])->body);
    $edit('');
    $this->assertNull(TestDb::value("SELECT code FROM im_location WHERE id=".$id));
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

  private function assertNamesInOrder(array $expected, string $body)
  {
    preg_match_all('/'.implode('|', array_map(fn($name) => preg_quote($name, '/'), $expected)).'/u', $body, $matches);
    $this->assertSame($expected, array_values(array_unique($matches[0])));
  }

  private function unsortedLocations()
  {
    // Created out of order, so the database returns them out of order too.
    return ['locations' => [['name' => 'Room', 'children' => [
      ['name' => 'Skříň 3L', 'children' => ['Shelf']],
      'Skříň 3A',
      'skříň 3K',
      'Box 10',
      'Box 2']]]];
  }

  public function testLocationsPageSortsLocationsAlphabetically()
  {
    $context = new ContextPreparator($this->unsortedLocations());
    $response = $context->client->get('/locations');
    $this->assertPageOk($response);
    // The parent selector of the add form comes first, then the table.
    [$selector, $table] = explode('</select>', $response->body, 2);
    $expected = ['Box 2', 'Box 10', 'Skříň 3A', 'skříň 3K', 'Skříň 3L'];
    $this->assertNamesInOrder($expected, $selector);
    $this->assertNamesInOrder($expected, $table);
  }

  public function testLocationPageSortsLocationsAlphabetically()
  {
    $context = new ContextPreparator($this->unsortedLocations());
    $response = $context->client->get('/location', ['id' => $context->location('Room')['id']]);
    $this->assertPageOk($response);
    $this->assertNamesInOrder(['Box 2', 'Box 10', 'Skříň 3A', 'skříň 3K', 'Skříň 3L'], $response->body);
  }
}
