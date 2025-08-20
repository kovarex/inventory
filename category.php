<?php
require("src/header.php");

if (!empty($_POST["name"]))
  $db->query("INSERT INTO im_category(name,description) value('".
             $db->real_escape_string($_POST["name"])."','".
             $db->real_escape_string($_POST["description"])."')");

if (!empty($_POST["delete_category"]))
  $db->query("DELETE FROM im_category where id='".
             $db->real_escape_string($_POST["delete_category"])."'");

$result = $db->query("SELECT * FROM im_category");
?>

<form method="post">
  <table>
    <tr>
      <td><label for="name">Name:</label></td>
      <td><input type="text" name="name"/></td>
    </tr>
    <tr>
      <td><label for="description">Description:</label></td>
      <td><input type="text" name="description"/></td>
    </tr>
  </table>
  <input type="submit" value="Add category"/>
</form>

<?php


echo "<table class='data-table'>";

while($row = $result->fetch_assoc())
{
  echo <<<HTML
  <tr>
    <td>
      {$row["name"]}
    </td>
    <td>
      {$row["description"]}
    </td>
    <td>
      <form method="post" >
        <input type="submit" value="Delete"/>
        <input type="hidden" name="delete_category" value="{$row["id"]}"/>
      </form>
    </td>
  </tr>
HTML;
}

echo "</table>";

require("src/footer.php");
?>