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

?>
