<?php
// Prepares the database state for a test, in the spirit of tsumego-hero's ContextPreparator, but with plain SQL.
// All the data is wiped first, then the described context is created.
//
// new ContextPreparator([
//   'user' => ['username' => 'kovarex'],                 // default, logged in through $context->client; null = nobody logged in
//   'other-users' => [['username' => 'honza']],
//   'home' => ['name' => 'Vostrovská', 'users' => ['honza']], // default home of the user, set as their last (active) home
//   'other-homes' => [['name' => 'Cottage']],
//   'categories' => ['Books', ['name' => 'Tools', 'description' => 'Hand tools']],
//   'locations' => ['Garage', ['name' => '1A', 'children' => ['Top shelf', 'Bottom shelf']]],
//   'locations' => [['name' => 'Floor 1', 'plan' => true, 'children' => [['name' => 'Kitchen', 'area' => '0,0 0.5,0 0.5,0.5']]]],
//   'items' => [['name' => 'Hammer', 'location' => 'Garage', 'category' => 'Tools', 'deleted' => true]],
// ]);
//
// Every option is checked, an unknown one throws, so typos don't silently produce a different context.
class ContextPreparator
{
  public function __construct(array $options = [])
  {
    $this->wipeData();

    if (array_key_exists("user", $options))
      $this->prepareThisUser(self::extract("user", $options));
    else
      $this->prepareThisUser([]);
    $this->prepareOtherUsers(self::extract("other-users", $options));

    if (array_key_exists("home", $options))
      $this->prepareThisHome(self::extract("home", $options));
    else if ($this->user)
      $this->prepareThisHome([]);
    $this->prepareOtherHomes(self::extract("other-homes", $options));

    $this->prepareCategories(self::extract("categories", $options));
    $this->prepareLocations(self::extract("locations", $options), null);
    $this->prepareItems(self::extract("items", $options));
    self::checkOptionsConsumed($options);

    $this->client = new HttpClient();
    if ($this->user)
      $this->client->login($this->user["username"], $this->user["password"]);
  }

  public function category(string $name): array
  {
    return $this->categories[$name] ?? throw new Exception("Category ".$name." wasn't prepared.");
  }

  public function location(string $name): array
  {
    return $this->locations[$name] ?? throw new Exception("Location ".$name." wasn't prepared.");
  }

  public function item(string $name): array
  {
    return $this->items[$name] ?? throw new Exception("Item ".$name." wasn't prepared.");
  }

  public function otherUser(string $username): array
  {
    return $this->otherUsers[$username] ?? throw new Exception("User ".$username." wasn't prepared.");
  }

  // Reads the current state of the item from the database.
  public function reloadItem(string $name): array
  {
    return TestDb::row("SELECT * FROM im_item WHERE id=".$this->item($name)["id"]);
  }

  private function wipeData(): void
  {
    TestDb::query("SET FOREIGN_KEY_CHECKS = 0");
    foreach (["im_transaction", "im_item", "im_location", "im_category", "im_home_user", "im_home", "im_user"] as $table)
      TestDb::query("DELETE FROM `".$table."`");
    TestDb::query("SET FOREIGN_KEY_CHECKS = 1");
  }

  private function prepareThisUser(?array $input): void
  {
    if ($input === null)
      return;
    $this->user = $this->prepareUser($input);
  }

  private function prepareOtherUsers(?array $input): void
  {
    foreach ($input ?? [] as $userInput)
    {
      $user = $this->prepareUser($userInput);
      $this->otherUsers[$user["username"]] = $user;
    }
  }

  private function prepareUser(array $input): array
  {
    $user = [];
    $user["username"] = self::extract("username", $input) ?? "kovarex";
    $user["email"] = self::extract("email", $input) ?? $user["username"]."@example.com";
    $password = self::extract("password", $input) ?? "test";
    self::checkOptionsConsumed($input);

    $user["id"] = TestDb::insert("im_user", [
      "username" => $user["username"],
      "email" => $user["email"],
      "password" => password_hash($password, PASSWORD_DEFAULT)]);
    $user["password"] = $password; // plain, so the test can log in
    return $user;
  }

  private function prepareThisHome(?array $input): void
  {
    if ($input === null)
      return;
    $this->home = $this->prepareHome($input);
    if ($this->user)
      TestDb::query("UPDATE im_user SET last_home_id=".$this->home["id"]." WHERE id=".$this->user["id"]);
  }

  private function prepareOtherHomes(?array $input): void
  {
    foreach ($input ?? [] as $homeInput)
      $this->otherHomes[] = $this->prepareHome($homeInput);
  }

  // The home is shared by this user (when there is one) and the listed other users.
  private function prepareHome(array $input): array
  {
    $home = [];
    $home["name"] = self::extract("name", $input) ?? "Test home";
    $home["description"] = self::extract("description", $input) ?? "";
    $usernames = self::extract("users", $input) ?? [];
    $admin = self::extract("admin", $input) ?? true;
    self::checkOptionsConsumed($input);

    $home["id"] = TestDb::insert("im_home", ["name" => $home["name"], "description" => $home["description"]]);
    $userIDs = array_map(fn($username) => $this->otherUser($username)["id"], $usernames);
    if ($this->user)
      array_unshift($userIDs, $this->user["id"]);
    foreach ($userIDs as $userID)
      TestDb::insert("im_home_user", ["home_id" => $home["id"], "user_id" => $userID, "is_admin" => $admin]);
    return $home;
  }

  private function prepareCategories(?array $input): void
  {
    foreach ($input ?? [] as $categoryInput)
      $this->prepareCategory($categoryInput);
  }

  private function prepareCategory(string|array $input): array
  {
    if (is_string($input))
      $input = ["name" => $input];
    $category = [];
    $category["name"] = self::extract("name", $input);
    $category["description"] = self::extract("description", $input);
    self::checkOptionsConsumed($input);

    $category["id"] = TestDb::insert("im_category", [
      "home_id" => $this->homeID(),
      "name" => $category["name"],
      "description" => $category["description"]]);
    return $this->categories[$category["name"]] = $category;
  }

  private function prepareLocations(?array $input, ?array $parent): void
  {
    foreach ($input ?? [] as $locationInput)
      $this->prepareLocation($locationInput, $parent);
  }

  private function prepareLocation(string|array $input, ?array $parent): array
  {
    if (is_string($input))
      $input = ["name" => $input];
    $location = [];
    $location["name"] = self::extract("name", $input);
    $location["description"] = self::extract("description", $input);
    $location["parent_location_id"] = $parent ? $parent["id"] : null;
    $location["plan_area"] = self::extract("area", $input);
    $plan = self::extract("plan", $input);
    $children = self::extract("children", $input);

    $location["id"] = TestDb::insert("im_location", [
      "home_id" => $this->homeID(),
      "name" => $location["name"],
      "description" => $location["description"],
      "parent_location_id" => $location["parent_location_id"],
      "plan_area" => $location["plan_area"]]);
    if ($plan)
      $this->preparePlan($location, $plan === true ? [] : $plan);
    $this->locations[$location["name"]] = $location;
    $this->prepareLocations($children, $location);
    self::checkOptionsConsumed($input);
    return $location;
  }

  // The plan is a simple SVG of the given size unless 'svg' is given.
  private function preparePlan(array &$location, array $input): void
  {
    $width = self::extract("width", $input) ?? 1000;
    $height = self::extract("height", $input) ?? 500;
    $svg = self::extract("svg", $input) ?? self::planSvg($width, $height);
    self::checkOptionsConsumed($input);
    TestDb::query("UPDATE im_location SET
                     plan=".TestDb::escape($svg).",
                     plan_type='image/svg+xml',
                     plan_width=".$width.",
                     plan_height=".$height."
                   WHERE id=".$location["id"]);
    $location["plan_width"] = $width;
    $location["plan_height"] = $height;
  }

  public static function planSvg(int $width, int $height): string
  {
    return "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 ".$width." ".$height."\">".
           "<rect x=\"0\" y=\"0\" width=\"".$width."\" height=\"".$height."\" fill=\"none\" stroke=\"black\"/></svg>";
  }

  private function prepareItems(?array $input): void
  {
    foreach ($input ?? [] as $itemInput)
      $this->prepareItem($itemInput);
  }

  // Location and category are referenced by name, they are created when they don't exist yet.
  private function prepareItem(string|array $input): array
  {
    if (is_string($input))
      $input = ["name" => $input];
    $item = [];
    $item["name"] = self::extract("name", $input);
    $item["description"] = self::extract("description", $input);
    $item["author"] = self::extract("author", $input);
    $item["deleted"] = self::extract("deleted", $input) ?? false;
    $locationName = self::extract("location", $input) ?? "Test location";
    $categoryName = self::extract("category", $input) ?? "Test category";
    self::checkOptionsConsumed($input);

    $item["location_id"] = ($this->locations[$locationName] ?? $this->prepareLocation($locationName, null))["id"];
    $item["category_id"] = ($this->categories[$categoryName] ?? $this->prepareCategory($categoryName))["id"];
    $item["id"] = TestDb::insert("im_item", [
      "home_id" => $this->homeID(),
      "name" => $item["name"],
      "description" => $item["description"],
      "author" => $item["author"],
      "deleted" => $item["deleted"],
      "location_id" => $item["location_id"],
      "category_id" => $item["category_id"]]);
    return $this->items[$item["name"]] = $item;
  }

  private function homeID(): int
  {
    if (!$this->home)
      throw new Exception("Home data can't be prepared without a home.");
    return $this->home["id"];
  }

  // Removes the option from the array and returns it, so we can check that all options were used.
  private static function extract(string $key, ?array &$input): mixed
  {
    if ($input === null or !array_key_exists($key, $input))
      return null;
    $result = $input[$key];
    unset($input[$key]);
    return $result;
  }

  private static function checkOptionsConsumed(?array $options): void
  {
    if (!empty($options))
      throw new Exception("Option ".implode(",", array_keys($options))." not recognized.");
  }

  public HttpClient $client;
  public ?array $user = null;
  public array $otherUsers = [];
  public ?array $home = null;
  public array $otherHomes = [];
  public array $categories = [];
  public array $locations = [];
  public array $items = [];
}
