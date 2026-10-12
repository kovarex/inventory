<?php
class AuthTest extends InventoryTestCase
{
  public function testNotLoggedInUserIsSentToLogin()
  {
    $context = new ContextPreparator(['user' => null]);
    $this->assertSame('/login', $context->client->get('/items')->location());
  }

  public function testLoginPage()
  {
    $context = new ContextPreparator(['user' => null]);
    $response = $context->client->get('/login');
    $this->assertPageOk($response);
    $this->assertStringContainsString('name="username"', $response->body);
  }

  public function testLogin()
  {
    $context = new ContextPreparator(['user' => null, 'other-users' => [['username' => 'honza', 'password' => 'secret']]]);
    $response = $context->client->post('/login_action', ['username' => 'honza', 'password' => 'secret']);
    $this->assertSame('/', $response->location());
    $this->assertStringContainsString('Currently logged in as honza', $context->client->follow($response)->body);
  }

  public function testLoginWithWrongPassword()
  {
    $context = new ContextPreparator(['user' => null, 'other-users' => [['username' => 'honza', 'password' => 'secret']]]);
    $response = $context->client->post('/login_action', ['username' => 'honza', 'password' => 'wrong']);
    $this->assertSame('/login?message='.urlencode('Wrong password!'), $response->location());
  }

  public function testLogoff()
  {
    $context = new ContextPreparator();
    $this->assertSame('/login', $context->client->post('/logoff_action')->location());
    $this->assertSame('/login', $context->client->get('/items')->location());
  }

  public function testRegister()
  {
    $context = new ContextPreparator(['user' => null]);
    $response = $context->client->post('/register', ['username' => 'honza', 'password' => 'secret', 'email' => 'honza@example.com']);
    $this->assertSame('/', $response->location());
    $user = TestDb::row("SELECT * FROM im_user WHERE username='honza'");
    $this->assertSame('honza@example.com', $user['email']);
    $this->assertTrue(password_verify('secret', $user['password']));
  }

  public function testRegisterWithoutPassword()
  {
    $context = new ContextPreparator(['user' => null]);
    $response = $context->client->post('/register', ['username' => 'honza', 'password' => '', 'email' => 'honza@example.com']);
    $this->assertStringContainsString('Password must be specified.', $response->body);
    $this->assertSame(0, (int)TestDb::value("SELECT COUNT(*) FROM im_user"));
  }

  public function testRegisterWithTakenUsername()
  {
    $context = new ContextPreparator(['user' => null, 'other-users' => [['username' => 'honza']]]);
    $response = $context->client->post('/register', ['username' => 'honza', 'password' => 'secret', 'email' => 'other@example.com']);
    $this->assertStringContainsString('User with that name already exists!', $response->body);
  }

  public function testUserWithoutHomeIsSentToHomes()
  {
    $context = new ContextPreparator(['home' => null]);
    $this->assertSame('/homes', $context->client->get('/items')->location());
    $this->assertPageOk($context->client->get('/homes'));
  }
}
