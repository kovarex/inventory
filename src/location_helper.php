<?php
function locationSelector($inputName, $preselectedID)
{
  buildLocationStructureSorted(locationChildren('NULL'), $structuredData, $locationPointers);
  echo "<select name=\"".$inputName."\">";

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
      foreach($structuredData["locations"] as $row)
        showSelect($row["id"], $row, empty($parentID) ? $indent : $indent."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $preselectedID);
  }
  showSelect(NULL, $structuredData, "", $preselectedID);
  echo "</select>";
}

function sortLocationStructureRecursive(&$structuredData, &$locationPointers)
{
  if (!empty($structuredData["locations"]))
  {
    usort($structuredData["locations"], function ($item1, $item2) { return $item1['name'] <=> $item2['name']; });
    foreach($structuredData["locations"] as &$innerLocation)
    {
      $locationPointers[$innerLocation["id"]] = &$innerLocation;
      sortLocationStructureRecursive($innerLocation, $locationPointers);
    }
  }
}

function buildLocationStructureSorted($flatLocationData, &$structuredData, &$locationPointers)
{
  buildLocationStructure($flatLocationData, $structuredData, $locationPointers);
  sortLocationStructureRecursive($structuredData, $locationPointers);
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
        $parent["locations"][$locationID]["id"] = $locationID;
        $parent = &$parent["locations"][$locationID];
        $locationPointers[$locationID] = &$parent;
      }
    }
  }
}
?>