<?php
require_once("src/image_upload_helper.php");
require_once("src/location_helper.php");
require_once("src/transaction_log.php");

echo "<h1>Locations</h1>";
$queryRightCheck = " and home_id=".homeID();

if (@$_POST["action"] == "add")
  query("INSERT INTO im_location(home_id, name,description,parent_location_id, image, thumbnail) value(".
        homeID().",".
        escape($_POST["name"]).",".
        escape($_POST["description"]).",".
        escape($_POST["parent_id"]).",".
        (isset($imageData) ?  "X".escape($imageData["big"]) : "NULL").",".
        (isset($imageData) ?  "X".escape($imageData["thumbnail"]) : "NULL").")");

if (@$_POST["action"] == "delete")
  query("DELETE FROM im_location where id='".
        $db->real_escape_string($_POST["id"])."'".$queryRightCheck);
locationForm("add", NULL, "locations");
buildLocationStructure(locationChildren('NULL'), $structuredData, $locationPointers);

if (count($structuredData) != 0)
{
  echo "<table class='data-table'><tr><th>Image</th><th>Name</th><th>Description</th><th>Parent</th</tr>";
  function showInTable($parentID, $structuredData, $indent)
  {
    if (!empty($parentID))
    {
      echo "<tr>";
      echo "<td>".locationLink($structuredData["id"], locationImage($structuredData["id"], $structuredData["has_image"]));
      echo "<td>".$indent.locationLink($structuredData["id"], $structuredData["name"])."</a></td>";
      echo "<td>".$structuredData["description"]."</td>";
      echo "<td>".locationLink($structuredData["parent_location_id"], $structuredData["parent_name"])."</td>";
      echo "<td>
              <form method=\"post\">
                <input type=\"submit\" value=\"Delete\"/>
                <input type=\"hidden\" name=\"id\" value=\"".$structuredData["id"]."\"/>
                <input type=\"hidden\" name=\"action\" value=\"delete\">
              </form>
            </td>";
     echo "</tr>";
    }
    if (!empty($structuredData["locations"]))
      foreach($structuredData["locations"] as $row)
        showInTable($row["id"], $row, empty($parentID) ? $indent : $indent."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
  }
  showInTable(NULL, $structuredData, "");
  echo "</table>";
}
?>
