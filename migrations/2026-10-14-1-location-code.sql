-- A short code of a location (like "1A" for a cupboard), shown on the plan instead of the name when set.
ALTER TABLE im_location
  ADD `code` varchar(32) default NULL;
