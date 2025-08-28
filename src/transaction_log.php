<?php
require_once("constants.php");

function itemCreated($locationID, $comment)
{
  query("INSERT INTO im_transaction (item_id, to_location_id, user_id, comment)
        values(LAST_INSERT_ID(), ".escape($locationID).", ".userID().", ".escape($comment).")");
}

function itemMoved($id, $fromLocationID, $toLocationID, $comment)
{
  query("INSERT INTO im_transaction (item_id, from_location_id, to_location_id, user_id, comment)
        values(".escape($id).", ".escape($fromLocationID).",".escape($toLocationID).", ".userID().", ".escape($comment).")");
}

function locationMoved($id, $fromLocationID, $toLocationID, $comment, $childrenFlat=NULL)
{
  if ($childrenFlat==NULL) $childrenFlat = locationChildrenFlat($id);
  $locationList=implode(",", $childrenFlat);
  $items = query("SELECT im_item.id FROM im_item WHERE im_item.location_id IN ($locationList)")->fetch_all(MYSQLI_ASSOC);
  if (count($items)==0)
    return;
  $itemsList = NULL;
  foreach ($items as $item) {
    if (empty($itemsList))
      $itemsList = $item['id'];
    else
      $itemsList .= ',' . $item['id'];
  }
  query("INSERT INTO im_transaction (item_id, parent_location_id, parent_from_location_id, parent_to_location_id, user_id, comment)
         SELECT im_item.id, ".escape($id).", ".escape($fromLocationID).", ".escape($toLocationID).", ".userID().", ".escape($comment)." FROM im_item WHERE 
         im_item.id IN ($itemsList)");
}

function generateTransactionDescription($row, $context = "none")
{
   $itemContext = $context == "item" ? "" : " ".itemLink($row["item_id"], $row["item_name"]);
   if (empty($row["from_location_id"]) and !empty($row["to_location_id"]))
     return "Created".$itemContext." in ".locationLink($row["to_location_id"], $row["to_location_name"]);
   if (!empty($row["from_location_id"]) and !empty($row["to_location_id"]))
     return "Moved ".$itemContext."from ".locationLink($row["from_location_id"], $row["from_location_name"]).
          " to ".locationLink($row["to_location_id"], $row["to_location_name"]);
   if (!empty($row["parent_from_location_id"]) and !empty($row["parent_to_location_id"]))
     return ((context == "item") ? "" : $itemContext." indirectly moved because ").
            locationLink($row["parent_location_id"], $row["parent_location_name"])." moved from ".
            locationLink($row["parent_from_location_id"], $row["parent_from_location_name"]).
            " to ".locationLink($row["parent_to_location_id"], $row["parent_to_location_name"]);
   return "Unknown operation";
}

?>
