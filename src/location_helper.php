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

function buildLocationStructure($flatLocationData, &$structuredData, &$locationPointers)
{
  foreach($flatLocationData as $row)
  {
    $parent = &$structuredData;
    for ($i = 1; $i <= LOCATION_CHILDREN_DEPTH; $i++)
    {
      $locationID = $row["level{$i}_location_id"];
      if (!empty($locationID))
      {
        $parent["locations"][$locationID]["name"] = $row["level{$i}_location_name"];
        $parent = &$parent["locations"][$locationID];
        $locationPointers[$locationID] = &$parent;
      }
    }
  }
}
?>