<?php
class PlanTest extends InventoryTestCase
{
  private const KITCHEN_AREA = '0.1000,0.1000 0.5000,0.1000 0.5000,0.5000';

  public function testPlanShowsAreasOfLocationsBelow()
  {
    $context = new ContextPreparator(['locations' => [[
      'name' => 'Floor 1',
      'plan' => ['width' => 1000, 'height' => 500],
      'children' => [
        ['name' => 'Kitchen', 'area' => self::KITCHEN_AREA, 'children' => [['name' => 'Kitchen drawer', 'area' => '0.2,0.2 0.3,0.2 0.3,0.3']]],
        ['name' => 'Bedroom', 'plan' => true, 'area' => '0.6,0.1 0.9,0.1 0.9,0.9', 'children' => [['name' => 'Wardrobe', 'area' => '0,0 1,0 1,1']]],
        ['name' => 'Hall']]]]]);
    $response = $context->client->get('/location', ['id' => $context->location('Floor 1')['id']]);
    $this->assertPageOk($response);
    $this->assertStringContainsString('viewBox="0 0 1000 500"', $response->body);
    $this->assertStringContainsString('image?source=location&amp;id='.$context->location('Floor 1')['id'].'&amp;type=plan', $response->body);
    $this->assertStringContainsString('<polygon class="plan-area" points="100,50 500,50 500,250"/>', $response->body);
    $this->assertStringContainsString('location?id='.$context->location('Kitchen')['id'], $response->body);
    $this->assertStringContainsString('location?id='.$context->location('Kitchen drawer')['id'].'" class="plan-link', $response->body);
    $this->assertStringContainsString('location?id='.$context->location('Bedroom')['id'].'" class="plan-link', $response->body);
    // Belongs to the plan of the bedroom
    $this->assertStringNotContainsString('location?id='.$context->location('Wardrobe')['id'].'" class="plan-link', $response->body);
    // No area
    $this->assertStringNotContainsString('location?id='.$context->location('Hall')['id'].'" class="plan-link', $response->body);
  }

  public function testAreaNamesAreWrittenInTheAreas()
  {
    $context = new ContextPreparator(['locations' => [[
      'name' => 'Floor 1',
      'plan' => ['width' => 1000, 'height' => 500],
      'children' => [
        ['name' => 'Big kitchen', 'area' => '0,0 0.5,0 0.5,1 0,1'],
        ['name' => 'Tiny storage room', 'area' => '0.9,0 0.95,0 0.95,0.2 0.9,0.2']]]]]);
    $body = $context->client->get('/location', ['id' => $context->location('Floor 1')['id']])->body;
    // Enough space, one line of the default size
    $this->assertStringContainsString('<text class="plan-label" x="250" y="250" font-size="20"><tspan x="250" dy="0em">Big kitchen</tspan></text>', $body);
    // Narrow area, a word per line, smaller
    $this->assertMatchesRegularExpression('#<text class="plan-label"[^>]* font-size="([0-9.]+)"><tspan[^>]*>Tiny</tspan><tspan[^>]*>storage</tspan><tspan[^>]*>room</tspan></text>#', $body);
    preg_match('#font-size="([0-9.]+)"><tspan[^>]*>Tiny#', $body, $size);
    $this->assertLessThan(20, (float)$size[1]);
  }

  public function testLocationIsHighlightedOnThePlanAbove()
  {
    $context = new ContextPreparator(['locations' => [[
      'name' => 'Floor 1',
      'plan' => true,
      'children' => [['name' => 'Kitchen', 'children' => [['name' => 'Kitchen drawer', 'area' => self::KITCHEN_AREA]]]]]]]);
    $response = $context->client->get('/location', ['id' => $context->location('Kitchen drawer')['id']]);
    $this->assertPageOk($response);
    $this->assertStringContainsString('On the plan of', $response->body);
    $this->assertStringContainsString('location?id='.$context->location('Kitchen drawer')['id'].'" class="plan-link plan-highlight"', $response->body);
    $this->assertStringContainsString('Redraw the area', $this->planControls($response->body));
  }

  public function testLocationWithoutPlansHasNoPlanSection()
  {
    $context = new ContextPreparator(['locations' => [['name' => 'Garage', 'children' => ['Shelf']]]]);
    $response = $context->client->get('/location', ['id' => $context->location('Shelf')['id']]);
    $this->assertPageOk($response);
    $this->assertStringNotContainsString('<svg', $response->body);
    $this->assertStringNotContainsString('Draw the area', $response->body);
    $this->assertStringContainsString('Add a plan', $this->planControls($response->body));
  }

  public function testDrawAreaPageShowsEditor()
  {
    $context = new ContextPreparator(['locations' => [['name' => 'Floor 1', 'plan' => true, 'children' => [['name' => 'Kitchen', 'area' => self::KITCHEN_AREA], 'Hall']]]]);
    $response = $context->client->get('/location', ['id' => $context->location('Hall')['id'], 'draw_area' => 1]);
    $this->assertPageOk($response);
    $this->assertStringContainsString('data-editor-for="'.$context->location('Hall')['id'].'"', $response->body);
    $this->assertStringContainsString('class="plan-link plan-reference"', $response->body);
    $this->assertStringContainsString('id="plan-editor-area" value=""', $response->body);
  }

  public function testSaveArea()
  {
    $context = new ContextPreparator(['locations' => [['name' => 'Floor 1', 'plan' => true, 'children' => ['Kitchen']]]]);
    $id = $context->location('Kitchen')['id'];
    $response = $context->client->post('/edit_location_area', ['id' => $id, 'area' => self::KITCHEN_AREA]);
    $this->assertSame('location?id='.$id, $response->location());
    $this->assertSame(self::KITCHEN_AREA, TestDb::value("SELECT plan_area FROM im_location WHERE id=$id"));

    $context->client->post('/edit_location_area', ['id' => $id, 'area' => '']);
    $this->assertNull(TestDb::value("SELECT plan_area FROM im_location WHERE id=$id"));
  }

  public function testInvalidAreaIsRejected()
  {
    $context = new ContextPreparator(['locations' => [['name' => 'Kitchen', 'area' => self::KITCHEN_AREA]]]);
    $id = $context->location('Kitchen')['id'];
    foreach (['0.1,0.1 0.2,0.2', '0.1,0.1 0.2,0.2 1.5,0.3', "0.1,0.1 0.2,0.2 0.3,0.3'; DROP TABLE im_item; --"] as $area)
    {
      $response = $context->client->post('/edit_location_area', ['id' => $id, 'area' => $area]);
      $this->assertStringContainsString('Invalid+area', $response->location());
      $this->assertSame(self::KITCHEN_AREA, TestDb::value("SELECT plan_area FROM im_location WHERE id=$id"));
    }
  }

  public function testCantChangeLocationOfOtherHome()
  {
    $context = new ContextPreparator();
    $foreignHome = TestDb::insert('im_home', ['name' => 'Foreign', 'description' => '']);
    $foreignLocation = TestDb::insert('im_location', ['home_id' => $foreignHome, 'name' => 'Attic']);
    $context->client->post('/edit_location_area', ['id' => $foreignLocation, 'area' => self::KITCHEN_AREA]);
    $context->client->post('/edit_location_plan', ['id' => $foreignLocation], ['plan' => $this->svgFile(ContextPreparator::planSvg(10, 10))]);
    $row = TestDb::row("SELECT plan_area, plan FROM im_location WHERE id=$foreignLocation");
    $this->assertNull($row['plan_area']);
    $this->assertNull($row['plan']);
    $this->assertStringContainsString('Location not found!', $context->client->get('/location', ['id' => $foreignLocation])->body);
  }

  public function testUploadSvgPlan()
  {
    $context = new ContextPreparator(['locations' => ['Floor 1']]);
    $id = $context->location('Floor 1')['id'];
    $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg" width="80mm" height="40mm" viewBox="0 0 800 400"><rect width="800" height="400"/></svg>';
    $response = $context->client->post('/edit_location_plan', ['id' => $id], ['plan' => $this->svgFile($svg)]);
    $this->assertSame('location?id='.$id, $response->location());
    $row = TestDb::row("SELECT * FROM im_location WHERE id=$id");
    $this->assertSame($svg, $row['plan']);
    $this->assertSame('image/svg+xml', $row['plan_type']);
    $this->assertEquals([800, 400], [$row['plan_width'], $row['plan_height']]);

    $image = $context->client->get('/image', ['source' => 'location', 'id' => $id, 'type' => 'plan']);
    $this->assertSame('image/svg+xml', $image->headers['content-type']);
    $this->assertStringContainsString("default-src 'none'", $image->headers['content-security-policy']);
    $this->assertSame($svg, $image->body);
  }

  public function testUploadPngPlan()
  {
    $context = new ContextPreparator(['locations' => ['Floor 1']]);
    $id = $context->location('Floor 1')['id'];
    $image = imagecreatetruecolor(300, 200);
    $file = tempnam(sys_get_temp_dir(), "inventory-test-plan").".png";
    imagepng($image, $file);
    $context->client->post('/edit_location_plan', ['id' => $id], ['plan' => $file]);
    $row = TestDb::row("SELECT * FROM im_location WHERE id=$id");
    $this->assertSame(file_get_contents($file), $row['plan']);
    $this->assertSame('image/png', $row['plan_type']);
    $this->assertEquals([300, 200], [$row['plan_width'], $row['plan_height']]);
  }

  public function testUnknownPlanFormatIsRejected()
  {
    $context = new ContextPreparator(['locations' => ['Floor 1']]);
    $id = $context->location('Floor 1')['id'];
    $response = $context->client->post('/edit_location_plan', ['id' => $id], ['plan' => $this->svgFile('just some text')]);
    $this->assertStringContainsString('Unrecognized+plan+format', $response->location());
    $this->assertNull(TestDb::value("SELECT plan FROM im_location WHERE id=$id"));
  }

  public function testRemovePlan()
  {
    $context = new ContextPreparator(['locations' => [['name' => 'Floor 1', 'plan' => true]]]);
    $id = $context->location('Floor 1')['id'];
    $this->assertStringContainsString('Remove the plan', $context->client->get('/location', ['id' => $id])->body);
    $context->client->post('/edit_location_plan', ['id' => $id, 'remove' => 1]);
    $this->assertNull(TestDb::value("SELECT plan FROM im_location WHERE id=$id"));
    $this->assertStringNotContainsString('<svg', $context->client->get('/location', ['id' => $id])->body);
  }

  private function svgFile(string $content): string
  {
    $file = tempnam(sys_get_temp_dir(), "inventory-test-plan").".svg";
    file_put_contents($file, $content);
    return $file;
  }

  // The plan setup controls, which have to be inside the edit dialog so they don't clutter the page.
  private function planControls(string $body): string
  {
    $dialog = strpos($body, 'id="edit-dialog"');
    $controls = strpos($body, '<div class="plan-controls">');
    $this->assertNotFalse($dialog);
    $this->assertNotFalse($controls);
    $this->assertGreaterThan($dialog, $controls);
    $this->assertStringNotContainsString('</div>', substr($body, $dialog, $controls - $dialog), 'The plan controls are outside of the edit dialog.');
    $this->assertSame(1, substr_count($body, '<div class="plan-controls">'));
    return substr($body, $controls);
  }
}
