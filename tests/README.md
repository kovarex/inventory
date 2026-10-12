# Tests

The tests talk to the application over HTTP, the same way a browser does, and check the pages and the database.
`tests/bootstrap.php` starts a PHP built-in web server for the test run, so nothing else needs to be running.

## Running locally

1. Create an empty MySQL/MariaDB database and load the schema: `mysql inventory_test < setup.sql`
2. Point `src/config.php` to it and allow the tests to wipe it (see `tests/ci/database-config.php`):
   ```php
   $allowTestDataWipe = true;
   ```
   **Never** set this in the production config, every test deletes all the data in the database first.
3. `composer install`
4. `vendor/bin/phpunit` (or `composer test`)

CI runs the same thing on every push to master and on every pull request (`.github/workflows/ci.yml`).

## Writing tests

Each test starts by describing the state it needs with `ContextPreparator`, which wipes the database and creates it:

```php
$context = new ContextPreparator([
  'categories' => ['Tools'],
  'locations' => [['name' => 'Garage', 'children' => ['Top shelf']]],
  'items' => [['name' => 'Hammer', 'location' => 'Top shelf', 'category' => 'Tools']]]);

$response = $context->client->get('/item', ['id' => $context->item('Hammer')['id']]);
$this->assertPageOk($response);
```

- By default there is a user `kovarex` (password `test`) with an active home, and `$context->client` is logged in as them.
  Use `'user' => null` for nobody logged in, `'home' => null` for a user without a home.
- Locations and categories are referenced by name; when an item names one that wasn't listed, it's created.
- An unknown option throws, so a typo can't silently prepare a different context.
- `$context->client` doesn't follow redirects, so `$response->location()` can be checked; `follow($response)` follows them.
- `TestDb` gives direct SQL access for checking the result.
- Any PHP warning, notice or error the application logs during a test fails that test.
