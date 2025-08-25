<?php
function locationSelector($inputName, $preselectedID)
{
  echo "<select name=\"".$inputName."\">";
  $rows = query("SELECT
                   im_location.id,
                   im_location.name
                 FROM im_location
                 WHERE im_location.home_id=".homeID())->fetch_all(MYSQLI_ASSOC);
  foreach($rows as $row)
  {
    echo "<option value=".$row["id"];
    if ($row["id"] == @$preselectedID)
      echo " selected";
    echo ">".$row["name"]."</option>";
  }
  echo "</select>";
}
?>