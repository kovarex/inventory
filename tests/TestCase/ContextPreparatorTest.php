<?php
class ContextPreparatorTest extends InventoryTestCase
{
  public function testPreparesNestedLocationsAndItems()
  {
    $context = new ContextPreparator([
      'categories' => ['Tools'],
      'locations' => [['name' => 'Garage', 'children' => ['Top shelf']]],
      'items' => [['name' => 'Hammer', 'location' => 'Top shelf', 'category' => 'Tools']]]);

    $this->assertSame($context->location('Garage')['id'], $context->location('Top shelf')['parent_location_id']);
    $item = $context->reloadItem('Hammer');
    $this->assertEquals($context->location('Top shelf')['id'], $item['location_id']);
    $this->assertEquals($context->category('Tools')['id'], $item['category_id']);
    $this->assertEquals($context->home['id'], $item['home_id']);
  }

  public function testOldDataIsWiped()
  {
    new ContextPreparator(['items' => ['Hammer']]);
    new ContextPreparator(['items' => ['Saw']]);
    $this->assertSame(['Saw'], array_column(TestDb::rows("SELECT name FROM im_item"), 'name'));
  }

  public function testUnknownOptionThrows()
  {
    $this->expectExceptionMessage('Option colour not recognized.');
    new ContextPreparator(['items' => [['name' => 'Hammer', 'colour' => 'red']]]);
  }
}
