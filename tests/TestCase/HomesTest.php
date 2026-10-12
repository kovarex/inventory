<?php
class HomesTest extends InventoryTestCase
{
  public function testAddHome()
  {
    $context = new ContextPreparator(['home' => null]);
    $this->assertPageOk($context->client->post('/homes', ['action' => 'add', 'name' => 'Cottage', 'description' => 'By the lake']));
    $home = TestDb::row("SELECT * FROM im_home");
    $this->assertSame('Cottage', $home['name']);
    $this->assertEquals($context->user['id'], TestDb::value("SELECT user_id FROM im_home_user WHERE home_id=".$home['id']));
  }

  public function testActivateHome()
  {
    $context = new ContextPreparator(['other-homes' => [['name' => 'Cottage']]]);
    $cottage = $context->otherHomes[0];
    $this->assertPageOk($context->client->post('/homes', ['action' => 'activate', 'id' => $cottage['id']]));
    $this->assertStringContainsString('in Cottage', $context->client->get('/homes')->body);
    $this->assertEquals($cottage['id'], TestDb::value("SELECT last_home_id FROM im_user WHERE id=".$context->user['id']));
  }

  public function testCantActivateForeignHome()
  {
    $context = new ContextPreparator(['other-users' => [['username' => 'honza']]]);
    $foreignHome = TestDb::insert('im_home', ['name' => 'Foreign', 'description' => '']);
    $context->client->post('/homes', ['action' => 'activate', 'id' => $foreignHome]);
    $this->assertStringNotContainsString('in Foreign', $context->client->get('/homes')->body);
  }

  public function testInviteUser()
  {
    $context = new ContextPreparator(['other-users' => [['username' => 'honza']]]);
    $this->assertPageOk($context->client->post('/homes', ['action' => 'invite', 'id' => $context->home['id'], 'username' => 'honza']));
    $this->assertEquals($context->home['id'], TestDb::value("SELECT home_id FROM im_home_user WHERE user_id=".$context->otherUser('honza')['id']));
  }
}
