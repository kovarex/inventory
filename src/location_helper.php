<?php
function locationSelector($inputName, $preselectedID)
{
  echo "<select name=\"".$inputName."\">";
  buildLocationStructure(locationChildren('NULL'), $structuredData, $locationPointers);
  function showSelect($parentID, $structuredData, $indent, $preselectedID)
  {
    if (!empty($parentID))
    {
      echo "<option value=".$parentID;
      if ($parentID == @$preselectedID)
        echo " selected";
      echo ">".$indent.$structuredData["name"]."</option>";
    }
    if (!empty($structuredData["locations"]))
      foreach($structuredData["locations"] as $key=>$row)
        showSelect($key, $row, empty($parentID) ? $indent : $indent."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $preselectedID);
  }
  showSelect(NULL, $structuredData, "", $preselectedID);
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