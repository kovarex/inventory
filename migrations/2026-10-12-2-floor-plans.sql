-- Floor plans: a location can have a plan image (floor, room, ...),
-- and a location can have an area drawn on the plan of its nearest ancestor that has a plan.
-- plan_area is a polygon "x,y x,y ..." with coordinates relative to the plan size (0..1).
ALTER TABLE im_location
  ADD `plan` longblob default NULL,
  ADD `plan_type` varchar(64) default NULL,
  ADD `plan_width` int(11) default NULL,
  ADD `plan_height` int(11) default NULL,
  ADD `plan_area` varchar(4096) default NULL;
