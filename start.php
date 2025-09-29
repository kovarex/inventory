<?php
$pageStart = microtime(true);
error_reporting(E_ALL);
$pagePath = substr(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), 1);
$query = parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY);

if ($query)
  foreach (explode('&', $query) as $chunk)
  {
    $param = explode("=", $chunk);
    if ($param and @$param[0] and @$param[1])
      $_GET[urldecode($param[0])] = urldecode($param[1]);
  }

require_once("src/constants.php");
require_once("src/link_helper.php");
require_once("src/db.php");
require_once("src/auth.php");

define("PAGE_WITHOUT_HEADER", 1);
define("NORMAL_PAGE", 2);

foreach (array("login_action",
               "logoff_action",
               "add_item",
               "add_location",
               "annihilate_item",
               "delete_item",
               "edit_item",
               "edit_location",
               "image") as $target)
  $pages[$target] = PAGE_WITHOUT_HEADER;

foreach (array("login",
               "home",
               "homes",
               "item",
               "items",
               "category",
               "categories",
               "location",
               "locations",
               "register",
               "restore_item",
               "transactions",
               "user",
               "users") as $target)
  $pages[$target] = NORMAL_PAGE;

if ($pagePath == "")
  $pageType = NORMAL_PAGE;
else
  $pageType = @$pages[$pagePath];

if (!$pageType)
  $user = query("SELECT im_user.id as id from im_user WHERE im_user.username=".escape($pagePath))->fetch_assoc();

if ($pageType == NORMAL_PAGE or isset($user))
{
  require("src/header.php");
  if (!empty($_GET["message"]))
    echo "<div class=\"message-div\"><h3><b>Message:</b></h3></br>".$_GET["message"]."</div>";
}

if ($pagePath == "")
  $result = require("home.php");
else if ($pageType)
  $result = require($pagePath.".php");
else if ($user)
{
  $_GET["id"] = $user["id"];
  $result = require("user.php");
}
else
  echo "Unknown page:".$pagePath;

if (!empty($result) and is_string($result))
  echo "<div class=\"message-div\"><h3><b>Message:</b></h3></br>".$result."</div>";

if ($pageType == NORMAL_PAGE or isset($user))
  require("src/footer.php");
?>
