<?php

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

function locationMoved($id, $fromLocationID, $toLocationID, $comment)
{
  $children=locationChildren($id);
  $allLocations=[$id];
  foreach($children as $row)
  {
    for ($i = 1; $i <= 3; $i++)
    {
      $locationID = $row["level{$i}_location_id"];
      if (!empty($locationID))
      {
        $allLocations[]=$locationID;
      }
    }
  }
  $locationList=implode(",",$allLocations);
  $items=query("SELECT im_item.id FROM im_item WHERE im_item.location_id IN ($locationList)")->fetch_all(MYSQLI_ASSOC);
  $itemsList=NULL;
  foreach ($items as $item) {
    if (empty($itemsList))
      $itemsList=$item['id'];
    else
      $itemsList.=','.$item['id'];
  }
  query("INSERT INTO im_transaction (item_id, parent_location_id, parent_from_location_id, parent_to_location_id, user_id, comment)
         SELECT im_item.id, $id, $fromLocationID, $toLocationID, ".userID().", '".$comment."' FROM im_item WHERE 
         im_item.id IN ($itemsList)");
}

?>
