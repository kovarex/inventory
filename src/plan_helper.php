<?php
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

// Renders the given location with the areas inside it: its own plan, or the plan above zoomed to its area.
function renderPlanOf($locations, $location, $highlightID = NULL, $editorFor = NULL)
{
  $areas = planAreas($locations, $location["id"]);
  if ($location["has_plan"])
    renderPlan($location, $areas, $highlightID, $editorFor);
  else
    renderPlan(nearestPlanAncestor($locations, $location["id"]), $areas, $highlightID, $editorFor, $location["plan_area"]);
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
  $lines = [$area["name"]];
  $words = preg_split("/\s+/", trim($area["name"]));
  if (count($words) > 1 and $fittingSize($words) > $fittingSize($lines))
    $lines = $words;
  $fontSize = max(6, round($fittingSize($lines), 1));
  $result = "<text class=\"plan-label\" x=\"".$centerX."\" y=\"".$centerY."\" font-size=\"".$fontSize."\">";
  foreach ($lines as $index => $line)
    $result .= "<tspan x=\"".$centerX."\" dy=\"".($index == 0 ? round(-(count($lines) - 1) * 0.6, 1) : 1.2)."em\">".htmlspecialchars($line)."</tspan>";
  return $result."</text>";
}

// Renders the plan of $planLocation with the areas of $areas.
// The area of $highlightID is highlighted, the others on hover. The names of the areas are always shown.
// With $editorFor, clicking on the plan draws the area of that location (see plan_editor in common.js).
// With $zoomArea, only that part of the plan is shown, and the surroundings are faded.
function renderPlan($planLocation, $areas, $highlightID = NULL, $editorFor = NULL, $zoomArea = NULL)
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
    if ($editorFor)
      echo "<g class=\"plan-link plan-reference\">";
    else
      echo "<a href=\"location?id=".$area["id"]."\" class=\"plan-link".($area["id"] == $highlightID ? " plan-highlight" : "")."\">";
    echo "<title>".$name."</title>";
    echo "<polygon class=\"plan-area\" points=\"".planAreaToSvgPoints($area["plan_area"], $width, $height)."\"/>";
    echo planLabel($area, $width, $height, $viewBox[2]);
    echo $editorFor ? "</g>" : "</a>";
  }
  echo "</svg>";
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
