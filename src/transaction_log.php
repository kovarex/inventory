<?php

function itemCreated($locationID, $comment)
{
  query("INSERT INTO im_transaction (item_id, to_location_id, user_id, comment)
        values(LAST_INSERT_ID(), ".escape($locationID).", ".userID().", ".escape($comment).")");
}

?>
