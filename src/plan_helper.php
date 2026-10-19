<?php
require_once(__DIR__."/location_helper.php");
// Floor plans.
// A location can have a plan image (a floor, a room, ...). Any location below it can have an area,
// a polygon on the plan of its nearest ancestor that has a plan. Both are optional.
// Areas nest: the plan of a floor shows the rooms, the cupboards are shown in the room, zoomed to its area.
// The area is stored as "x,y x,y ..." with coordinates relative to the plan size (0..1),
// so it survives replacing the plan by a version with a different resolution.

define("PLAN_MAX_SIZE", 20 * 1024 * 1024);

// All the locations of the current home, indexed by id, with the plan related columns.
function planLocations()
{
  $locations = [];
  $rows = query("SELECT
                   id,
                   name,
                   code,
                   parent_location_id,
                   plan_area,
                   plan_width,
                   plan_height,
                   plan IS NOT NULL as has_plan
                 FROM im_location
                 WHERE home_id=".homeID())->fetch_all(MYSQLI_ASSOC);
  foreach ($rows as $row)
    $locations[$row["id"]] = $row;
  return $locations;
}

// The closest ancestor (not the location itself) that has a plan, or NULL.
function nearestPlanAncestor($locations, $locationID)
{
  $visited = [];
  $parentID = @$locations[$locationID]["parent_location_id"];
  while (!empty($parentID) and isset($locations[$parentID]) and !isset($visited[$parentID]))
  {
    if ($locations[$parentID]["has_plan"])
      return $locations[$parentID];
    $visited[$parentID] = true;
    $parentID = $locations[$parentID]["parent_location_id"];
  }
  return NULL;
}

// The location that shows the area of the given location: the closest ancestor with a plan, or with an area
// that is on a plan. NULL when there is no plan above.
function areaContainer($locations, $locationID)
{
  $visited = [];
  $parentID = @$locations[$locationID]["parent_location_id"];
  while (!empty($parentID) and isset($locations[$parentID]) and !isset($visited[$parentID]))
  {
    $parent = $locations[$parentID];
    if ($parent["has_plan"])
      return $parent;
    if (!empty($parent["plan_area"]) and nearestPlanAncestor($locations, $parentID))
      return $parent;
    $visited[$parentID] = true;
    $parentID = $parent["parent_location_id"];
  }
  return NULL;
}

// Locations with an area shown in the given location (on its plan, or zoomed in its area).
function planAreas($locations, $containerID)
{
  $result = [];
  foreach ($locations as $location)
    if (!empty($location["plan_area"]))
    {
      $container = areaContainer($locations, $location["id"]);
      if ($container and $container["id"] == $containerID)
        $result[] = $location;
    }
  usort($result, fn($a, $b) => $a["name"] <=> $b["name"]);
  return $result;
}

// Locations with an area on the same plan as the given location, at any depth below it: the rooms of a floor
// and the cupboards in them, so the cupboards can be reached right from the floor.
// The outer areas come first, so the inner ones are drawn over them and get the mouse.
function planAreasDeep($locations, $containerID)
{
  $result = [];
  $level = [$containerID];
  $visited = [$containerID => true];
  while (!empty($level))
  {
    $next = [];
    foreach ($level as $parentID)
    {
      $areas = planAreas($locations, $parentID);
      foreach ($areas as $area)
        if (!isset($visited[$area["id"]]))
        {
          $visited[$area["id"]] = true;
          $area["nested"] = $parentID != $containerID;
          $result[] = $area;
          // A location with its own plan shows its insides on that plan
          if (!$area["has_plan"])
            $next[] = $area["id"];
        }
    }
    $level = $next;
  }
  return $result;
}

// The items in each location and the locations below it, indexed by the location id.
function planItems($locations)
{
  $children = [];
  foreach ($locations as $location)
    if (!empty($location["parent_location_id"]))
      $children[$location["parent_location_id"]][] = $location["id"];
  $direct = [];
  $rows = query("SELECT id, name, location_id, length(image) > 0 as has_image
                 FROM im_item
                 WHERE home_id=".homeID()." AND deleted=false")->fetch_all(MYSQLI_ASSOC);
  foreach ($rows as $row)
    $direct[$row["location_id"]][] = $row;
  $result = [];
  $collect = function($locationID, $visited) use (&$collect, &$result, $children, $direct)
  {
    if (isset($result[$locationID]))
      return $result[$locationID];
    $items = $direct[$locationID] ?? [];
    $visited[$locationID] = true;
    foreach ($children[$locationID] ?? [] as $childID)
      if (!isset($visited[$childID]))
        $items = array_merge($items, $collect($childID, $visited));
    return $result[$locationID] = $items;
  };
  foreach ($locations as $location)
    $collect($location["id"], []);
  return $result;
}

define("PLAN_TOOLTIP_ITEMS", 12);

// The tooltip of an area: its name and the items inside, with their pictures.
function planTooltip($area, $items)
{
  usort($items, 'compareByName');
  $result = "<div class=\"plan-tooltip-content\" data-location=\"".$area["id"]."\">";
  $result .= "<div class=\"plan-tooltip-title\">".htmlspecialchars($area["name"]).
             " <span class=\"item-count\">(".count($items)." item".(count($items) == 1 ? "" : "s").")</span></div>";
  if (!empty($items))
  {
    $result .= "<ul class=\"plan-tooltip-items\">";
    foreach (array_slice($items, 0, PLAN_TOOLTIP_ITEMS) as $item)
      $result .= "<li>".($item["has_image"] ? "<img loading=\"lazy\" src=\"image?source=item&amp;id=".$item["id"]."&amp;type=thumbnail\"/>" : "").
                 htmlspecialchars($item["name"])."</li>";
    $result .= "</ul>";
    if (count($items) > PLAN_TOOLTIP_ITEMS)
      $result .= "<div>and ".(count($items) - PLAN_TOOLTIP_ITEMS)." more</div>";
  }
  return $result."</div>";
}

// Renders the given location with the areas inside it: its own plan, or the plan above zoomed to its area.
function renderPlanOf($locations, $location, $highlightID = NULL, $editorFor = NULL)
{
  $areas = planAreasDeep($locations, $location["id"]);
  $items = $editorFor ? NULL : planItems($locations);
  if ($location["has_plan"])
    renderPlan($location, $areas, $highlightID, $editorFor, NULL, $items);
  else
    renderPlan(nearestPlanAncestor($locations, $location["id"]), $areas, $highlightID, $editorFor, $location["plan_area"], $items);
}

// Shows where the given location is: the location itself, or its closest ancestor with an area,
// highlighted where its area is shown. Used for the location of an item. Shows nothing when there is no such area.
function renderPlanWhere($locations, $locationID)
{
  $visited = [];
  while (!empty($locationID) and isset($locations[$locationID]) and !isset($visited[$locationID]))
  {
    $location = $locations[$locationID];
    $container = empty($location["plan_area"]) ? NULL : areaContainer($locations, $locationID);
    if ($container)
    {
      echo "<h2>In ".locationLink($container["id"], $container["name"])."</h2>";
      renderPlanOf($locations, $container, $locationID);
      return;
    }
    $visited[$locationID] = true;
    $locationID = $location["parent_location_id"];
  }
}

// Parses and validates the area polygon, returns the list of [x, y] points or NULL when invalid.
function parsePlanArea($area)
{
  $points = [];
  foreach (preg_split("/\s+/", trim($area)) as $pair)
  {
    if (!preg_match("/^(\d*\.?\d+),(\d*\.?\d+)$/", $pair, $match))
      return NULL;
    $x = (float)$match[1];
    $y = (float)$match[2];
    if ($x > 1 or $y > 1)
      return NULL;
    $points[] = [$x, $y];
  }
  if (count($points) < 3)
    return NULL;
  return $points;
}

function planAreaToSvgPoints($area, $width, $height)
{
  $points = parsePlanArea($area);
  if (!$points)
    return "";
  return implode(" ", array_map(fn($point) => round($point[0] * $width, 1).",".round($point[1] * $height, 1), $points));
}

function planAreaCenter($area, $width, $height)
{
  $points = parsePlanArea($area);
  $x = array_sum(array_column($points, 0)) / count($points);
  $y = array_sum(array_column($points, 1)) / count($points);
  return [round($x * $width, 1), round($y * $height, 1)];
}

// The name of the area, written in the area so it doesn't overlap the neighbours: on one line, or a word per line
// when that allows a bigger font, and smaller than the default size when the area is too small.
function planLabel($area, $width, $height, $viewWidth)
{
  $points = parsePlanArea($area["plan_area"]);
  $areaWidth = (max(array_column($points, 0)) - min(array_column($points, 0))) * $width;
  $areaHeight = (max(array_column($points, 1)) - min(array_column($points, 1))) * $height;
  [$centerX, $centerY] = planAreaCenter($area["plan_area"], $width, $height);
  $defaultSize = max(10, round($viewWidth / 50));
  $fittingSize = function($lines) use ($areaWidth, $areaHeight, $defaultSize)
  {
    $longest = max(array_map(fn($line) => function_exists("mb_strlen") ? mb_strlen($line) : strlen($line), $lines));
    return min($defaultSize, $areaWidth * 0.9 / ($longest * 0.6), $areaHeight * 0.9 / (count($lines) * 1.2));
  };
  $text = empty($area["code"]) ? $area["name"] : $area["code"];
  $lines = [$text];
  $words = preg_split("/\s+/", trim($text));
  if (count($words) > 1 and $fittingSize($words) > $fittingSize($lines))
    $lines = $words;
  $fontSize = max(6, round($fittingSize($lines), 1));
  $result = "<text class=\"plan-label\" x=\"".$centerX."\" y=\"".$centerY."\" font-size=\"".$fontSize."\">";
  foreach ($lines as $index => $line)
    $result .= "<tspan x=\"".$centerX."\" dy=\"".($index == 0 ? round(-(count($lines) - 1) * 0.6, 1) : 1.2)."em\">".htmlspecialchars($line)."</tspan>";
  return $result."</text>";
}

// Renders the plan of $planLocation with the areas of $areas.
// The outlines and names of the areas are always shown. The area of $highlightID is highlighted, the others on hover.
// With $editorFor, clicking on the plan draws the area of that location (see plan_editor in common.js).
// With $zoomArea, only that part of the plan is shown, and the surroundings are faded.
// With $items (see planItems), hovering an area shows the items inside it (see planTooltipInit in common.js).
function renderPlan($planLocation, $areas, $highlightID = NULL, $editorFor = NULL, $zoomArea = NULL, $items = NULL)
{
  $width = (int)$planLocation["plan_width"];
  $height = (int)$planLocation["plan_height"];
  $viewBox = [0, 0, $width, $height];
  $zoomPoints = $zoomArea ? parsePlanArea($zoomArea) : NULL;
  if ($zoomPoints)
  {
    $left = min(array_column($zoomPoints, 0)) * $width;
    $top = min(array_column($zoomPoints, 1)) * $height;
    $right = max(array_column($zoomPoints, 0)) * $width;
    $bottom = max(array_column($zoomPoints, 1)) * $height;
    $margin = max($right - $left, $bottom - $top) * 0.05;
    $viewBox = array_map(fn($value) => round($value, 1), [$left - $margin, $top - $margin, $right - $left + 2 * $margin, $bottom - $top + 2 * $margin]);
  }
  echo "<div class=\"plan\">";
  echo "<svg class=\"plan-svg\" viewBox=\"".implode(" ", $viewBox)."\" xmlns=\"http://www.w3.org/2000/svg\"".
       " data-plan-width=\"".$width."\" data-plan-height=\"".$height."\"".
       ($editorFor ? " data-editor-for=\"".$editorFor."\"" : "").">";
  echo "<image href=\"image?source=location&amp;id=".$planLocation["id"]."&amp;type=plan\" width=\"".$width."\" height=\"".$height."\"/>";
  if ($zoomPoints)
    echo "<path class=\"plan-surroundings\" fill-rule=\"evenodd\" d=\"M0,0 H".$width." V".$height." H0 Z M".
         planAreaToSvgPoints($zoomArea, $width, $height)." Z\"/>";
  foreach ($areas as $area)
  {
    if ($editorFor and $area["id"] == $editorFor)
      continue; // drawn by the editor
    $name = htmlspecialchars($area["name"]);
    // While drawing, the other areas are only shown for reference, so clicks go to the editor
    $classes = "plan-link".(empty($area["nested"]) ? "" : " plan-nested");
    if ($editorFor)
      echo "<g class=\"".$classes." plan-reference\">";
    else
      echo "<a href=\"location?id=".$area["id"]."\" class=\"".$classes.($area["id"] == $highlightID ? " plan-highlight" : "")."\" data-location=\"".$area["id"]."\">";
    echo "<title>".$name."</title>";
    echo "<polygon class=\"plan-area\" points=\"".planAreaToSvgPoints($area["plan_area"], $width, $height)."\"/>";
    echo planLabel($area, $width, $height, $viewBox[2]);
    echo $editorFor ? "</g>" : "</a>";
  }
  echo "</svg>";
  if ($items !== NULL)
  {
    echo "<div class=\"plan-tooltips\" hidden>";
    foreach ($areas as $area)
      echo planTooltip($area, $items[$area["id"]] ?? []);
    echo "</div>";
  }
  echo "</div>";
}

// Processes the uploaded plan file (input "plan"): SVG, PNG, JPEG, GIF or WebP.
// Returns the plan data, or a string with an error, or NULL when nothing was uploaded.
function tryToProcessPlanUpload()
{
  if (!isset($_FILES["plan"]) || $_FILES["plan"]["error"] == UPLOAD_ERR_NO_FILE)
    return NULL;
  if ($_FILES["plan"]["error"] != UPLOAD_ERR_OK)
    return imageUploadErrorMessage($_FILES["plan"]["error"]);
  if (!is_uploaded_file($_FILES["plan"]["tmp_name"]))
    return "Plan upload failed.";
  if (filesize($_FILES["plan"]["tmp_name"]) > PLAN_MAX_SIZE)
    return "The plan is too big.";

  $data = file_get_contents($_FILES["plan"]["tmp_name"]);
  $imageInfo = @getimagesizefromstring($data);
  if ($imageInfo !== false)
  {
    if (!in_array($imageInfo["mime"], ["image/png", "image/jpeg", "image/gif", "image/webp"]))
      return "Unsupported plan format ".$imageInfo["mime"].".";
    return ["data" => $data, "type" => $imageInfo["mime"], "width" => $imageInfo[0], "height" => $imageInfo[1]];
  }

  $size = svgSize($data);
  if (!$size)
    return "Unrecognized plan format, use SVG, PNG, JPEG, GIF or WebP.";
  return ["data" => $data, "type" => "image/svg+xml", "width" => $size[0], "height" => $size[1]];
}

// Size of the SVG drawing, from the viewBox or the width and height attributes, or NULL when it isn't an SVG.
function svgSize($data)
{
  $previous = libxml_use_internal_errors(true);
  $svg = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NONET);
  libxml_use_internal_errors($previous);
  if ($svg === false or $svg->getName() != "svg")
    return NULL;
  $viewBox = preg_split("/[\s,]+/", trim((string)$svg["viewBox"]));
  if (count($viewBox) == 4 and (float)$viewBox[2] > 0 and (float)$viewBox[3] > 0)
    return [(float)$viewBox[2], (float)$viewBox[3]];
  $width = (float)$svg["width"];
  $height = (float)$svg["height"];
  if ($width > 0 and $height > 0)
    return [$width, $height];
  return NULL;
}
?>
