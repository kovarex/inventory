<?php
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html>
  <head>
    <title>
      Inventory management
    </title>
    <link rel="stylesheet" href="<?php echo $resourceAddress ?? "."; ?>/style.css?v=<?php echo filemtime(__DIR__."/../style.css"); ?>"/>
    <script src="<?php echo $resourceAddress ?? "."; ?>/common.js?v=<?php echo filemtime(__DIR__."/../common.js"); ?>" type="text/javascript"></script>
  </head>
<body>
