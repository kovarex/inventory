<?php

function locationChildren($id)
{
  $columns=[];
  $joins=["im_location as level1_location"];
  for ($f = 1;$f <= LOCATION_CHILDREN_DEPTH; $f++)
  {
    $columns[] = "level{$f}_location.id as level{$f}_location_id,
                  level{$f}_location.name as level{$f}_location_name,
                  level{$f}_location.description as level{$f}_location_description,
                  level{$f}_location.parent_location_id as level{$f}_parent_location_id,
                  ".(($f > 1) ? "level".($f-1)."_location.name" : "NULL")." as level{$f}_parent_name,
                  length(level{$f}_location.image) > 0 as level{$f}_has_image";
    if ($f > 1) $joins[] = "im_location as level{$f}_location on level{$f}_location.parent_location_id = level".($f - 1)."_location.id";
  }
  return query("SELECT
               ".implode(",",$columns)."
               FROM
               ".implode(" left join ",$joins). "
               WHERE level1_location.parent_location_id".($id == "NULL" ? " is " : "=").$id)->fetch_all(MYSQLI_ASSOC);
}

function locationChildrenFlat($id)
{
  $children = locationChildren($id);
  $flatLocations=[$id];
  foreach($children as $row)
  {
    for ($i = 1; $i <= LOCATION_CHILDREN_DEPTH; $i++)
    {
      $locationID = $row["level{$i}_location_id"];
      if (!empty($locationID))
        $flatLocations[] = $locationID;
    }
  }
  return $flatLocations;
}

function locationForm($formAction, $locationToEdit, $redirect, $predefinedLocation = NULL)
{
  echo "<form method=\"post\" enctype=\"multipart/form-data\" class=\"data-form\" action=\"".($formAction == "add" ? "add_location" : "edit_location")."\">";
  echo "<input type=\"hidden\" name=\"id\" value=\"".@$locationToEdit["id"]."\">";
  echo "<table>";
  echo "<tr>
          <td><label for=\"name\">Name:</label></td>
          <td><input type=\"text\" name=\"name\" value=\"".@$locationToEdit["name"]."\"/></td>
        </tr>";
  echo "<tr>
          <td><label for=\"description\">Description:</label></td>
          <td><input type=\"text\" name=\"description\" value=\"".@$locationToEdit['description']."\"></td>
        </tr>";
  echo "<tr>
          <td><label for=\"image\">Image:</label></td>
          <td><input type=\"file\" name=\"image\" accept=\"image/*\" capture=\"camera\"></td>
        </tr>";
  echo "<tr>
          <td><label for=\"parent_id\">Parent:</label></td>
          <td>";
  echo locationSelector("parent_id", @$locationToEdit["parent_location_id"]);
  echo "</td></tr>";
  if ($formAction == "edit")
    echo "<tr><td>Move comment</td><td><input type=\"text\" name=\"comment\"/></td></tr>";
  echo "</table>";
  echo "<input type=\"hidden\" name=\"redirect\" value=\"".$redirect."\"/>";
  echo "<input type=\"submit\" value=\"".($formAction == "add" ? "Add location" : "Save")."\"/>";
  echo "</form>";
}

function locationSelector($inputName, $preselectedID)
{
  buildLocationStructureSorted(locationChildren('NULL'), $structuredData, $locationPointers);
  echo "<select name=\"".$inputName."\">";

  function showLocationSelect($parentID, $structuredData, $indent, $preselectedID)
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
        showLocationSelect($row["id"], $row, empty($parentID) ? $indent : $indent."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $preselectedID);
  }
  showLocationSelect(NULL, $structuredData, "", $preselectedID);
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

  if (!empty($structuredData["items"]))
    usort($structuredData["items"], function ($item1, $item2) { return $item1['name'] <=> $item2['name']; });
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
        $parent["locations"][$locationID]["id"] = $locationID;
        $parent["locations"][$locationID]["name"] = $row["level{$i}_location_name"];
        $parent["locations"][$locationID]["description"] = $row["level{$i}_location_description"];
        $parent["locations"][$locationID]["parent_location_id"] = $row["level{$i}_parent_location_id"];
        $parent["locations"][$locationID]["parent_name"] = $row["level{$i}_parent_name"];
        $parent["locations"][$locationID]["has_image"] = empty($row["level{$i}_has_image"]) ? false : true;
        $parent = &$parent["locations"][$locationID];
        $locationPointers[$locationID] = &$parent;
      }
    }
  }
}
?>
