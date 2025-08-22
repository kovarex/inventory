<?php
require("src/header.php");
require("src/transaction_log.php");
require_once("constants.php");

echo "<h1>Items</h1>";
$queryRightCheck = " and home_id=".homeID();

function checkCategoryAndLocation()
{
  global $db;
  if (query("SELECT im_location.id
            from im_location
            where
              im_location.id=".escape($_POST["location_id"])." and
              im_location.home_id=".homeID())->num_rows == 0)
    return false;

  if (query("SELECT im_category.id
            from im_category
            where
              im_category.id=".escape($_POST["category_id"])." and
              im_category.home_id=".homeID())->num_rows == 0)
    return false;

  return true;
}

if (@is_uploaded_file($_FILES["item_image"]["tmp_name"]))
{
  $imageFileName = $_FILES["item_image"]["tmp_name"];
  $imageInfo = @getimagesize($imageFileName);
  if ($imageInfo===false)
  {
    echo "<div>Unrecognized image file format!</div>";
    goto ZaTo; // Jirka has learned something new
  }

  if ($imageInfo['mime']=='image/png')
    $imageOriginal = imagecreatefrompng($imageFileName);
  elseif (in_array($imageInfo['mime'],['image/jpg','image/jpeg','image/pjpeg']))
    $imageOriginal = imagecreatefromjpeg($imageFileName);
  else
  {
    echo "<div>Unsupported image file format!</div>";
    goto ZaTo;
  }

  $aspect = $imageInfo['0'] / $imageInfo['1'];

  if (!($imageInfo['0']<BIG_IMAGE_SIZE && $imageInfo['1']<BIG_IMAGE_SIZE)) {
    $scaleBigWidth=($aspect>1)?BIG_IMAGE_SIZE:BIG_IMAGE_SIZE*$aspect;
    $scaleBigHeight=($aspect>1)?BIG_IMAGE_SIZE/$aspect:BIG_IMAGE_SIZE;
    $imageResizedBig = imagescale($imageOriginal, (int)$scaleBigWidth, (int)$scaleBigHeight, IMG_BICUBIC);

    ob_start();
    imagejpeg($imageResizedBig,NULL,80);
    $imageResizedBigContents = ob_get_contents();
    ob_end_clean();
  } else {
    $imageResizedBigContents = file_get_contents($imageFileName);
  }

  $scaleThumbnailWidth=($aspect>1)?THUMBNAIL_IMAGE_SIZE:THUMBNAIL_IMAGE_SIZE*$aspect;
  $scaleThumbnailHeight=($aspect>1)?THUMBNAIL_IMAGE_SIZE/$aspect:THUMBNAIL_IMAGE_SIZE;
  $imageResizedThumbnail = imagescale($imageOriginal, (int)$scaleThumbnailWidth, (int)$scaleThumbnailHeight, IMG_BICUBIC);

  ob_start();
  imagejpeg($imageResizedThumbnail,NULL,80);
  $imageResizedThumbnailContents = ob_get_contents();
  ob_end_clean();

  $hexImageBig = bin2hex($imageResizedBigContents);
  $hexImageThumbnail = bin2hex($imageResizedThumbnailContents);
}
ZaTo:

if (@$_POST["action"] == "edit" and checkCategoryAndLocation())
{
  $beforeChange = query("SELECT * FROM im_item where im_item.id=".escape($_POST["id"]))->fetch_assoc();

  query("UPDATE im_item SET ".
        "  name=".escape($_POST["name"]).",".
        "  description=".escape($_POST["description"]).",".
        "  category_id=".escape($_POST["category_id"]).",".
        "  location_id=".escape($_POST["location_id"]).
        (isset($hexImageBig) ?  ",image=X".escape($hexImageBig) : "").
        (isset($hexImageThumbnail) ?  ",thumbnail=X".escape($hexImageThumbnail) : "").
        "WHERE id=".escape($_POST["id"]).$queryRightCheck);
  if (!empty($beforeChange["location_id"]) and !empty($_POST["location_id"]))
    itemMoved($_POST["id"], $beforeChange["location_id"], $_POST["location_id"], $_POST["comment"]);
}

if (@$_POST["action"] == "add" and checkCategoryAndLocation())
{
  query("INSERT INTO im_item(name,description,home_id,location_id,image, thumbnail, category_id)
        value(".
        escape($_POST["name"]).",".
        escape($_POST["description"]).",".
        homeID().",".
        escape($_POST["location_id"]).",".
        (isset($hexImageBig) ?  "X".escape($hexImageBig) : "NULL").",".
        (isset($hexImageThumbnail) ?  "X".escape($hexImageThumbnail) : "NULL").",".
        escape($_POST["category_id"]).")");
  itemCreated($_POST["location_id"], $_POST["comment"]);
}
if (@$_POST["action"] == "delete")
  query("DELETE FROM im_item where id=".escape($_POST["id"]).$queryRightCheck);

$formAction = "add";
if (@$_POST["action"] == "start-edit")
{
  $result = query("SELECT * FROM im_item where id=".escape($_POST["id"]).$queryRightCheck);
  $itemToEdit = $result->fetch_assoc();
  $formAction = "edit";
}
?>

<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="action" value="<?= $formAction ?>"/>
  <input type='hidden' name='id' value="<?= @$itemToEdit['id'] ?>"/>
  <table>
    <tr>
      <td><label for="name">Name:</label></td>
      <td><input type="text" name="name" value="<?= @$itemToEdit['name'] ?>"/></td>
    </tr>
    <tr>
      <td><label for="description">Description:</label></td>
      <td><input type="text" name="description" value="<?= @$itemToEdit['description'] ?>"/></td>
    </tr>
    <tr>
      <td><label for="category_id">Category:<label</td>
      <td>
        <select name="category_id">
        <?php

          $result = query("SELECT
                             im_category.id,
                             im_category.name
                           FROM im_category
                           where im_category.home_id=".homeID());
          $rows = $result->fetch_all(MYSQLI_ASSOC);

          foreach($rows as $row)
          {
            $selected = $row["id"] == $itemToEdit["category_id"] ? " selected" : "";
            echo "<option value='{$row["id"]}'{$selected}>{$row["name"]}</option>";
          }
        ?>
        </select>
    </tr>
    <tr>
      <td><label for="location_id">Location:<label</td>
      <td>
        <select name="location_id">
        <?php

          $result = query("SELECT
                             im_location.id,
                             im_location.name
                           FROM im_location
                           where im_location.home_id=".homeID());
          $rows = $result->fetch_all(MYSQLI_ASSOC);

          foreach($rows as $row)
          {
            $selected = $row["id"] == $itemToEdit["location_id"] ? " selected" : "";
            echo "<option value='{$row["id"]}'{$selected}>{$row["name"]}</option>";
          }
        ?>
        </select>
    </tr>
    <tr>
      <td><label for="item_image">Image:</label></td>
      <td>
        <input type="file" name="item_image"/>
      </td>
    </tr>
    <tr>
      <td><?= $formAction == "add" ? "Creation comment" : "Move comment" ?></td>
      <td><input type="text" name="comment"/></td>
    </tr>
  </table>
  <input type="submit" value="<?= $formAction == "add" ? "Add Item" : "Edit" ?>"/>
</form>
<hr>
<form method=get class="search-form">
  <input type="text" name="search" value="<?= @htmlspecialchars(@$_GET['search']) ?>"/>
  <input type=hidden name="action" value="search"/>
  <input type=submit value="Search"/>
</form>
<?php
if (@$_GET['action']==="search") {
  echo <<<HTML
<form method=get class="search-form">
  <input type=submit value="X"/>
</form>
HTML;
  $searchQuery=$db->real_escape_string($_GET['search']);
  $searchSQL=" AND (im_item.name LIKE '%{$searchQuery}%' OR im_item.description LIKE '%{$searchQuery}%')";
} else {
  $searchSQL="";
}

$result = query("SELECT
                   im_item.id,
                   im_item.name,
                   im_item.description,
                   parent_location.name as parent_name,
                   im_category.name as category_name,
                   length(im_item.image) as image_size
                 FROM im_category, im_item
                 left join im_location parent_location on im_item.location_id=parent_location.id
                 where im_item.category_id = im_category.id and im_item.home_id=".homeID().$searchSQL);
$rows = $result->fetch_all(MYSQLI_ASSOC);

if (count($rows) != 0)
{
  echo "<table class='data-table'><tr><th>Name</th><th>Description</th><th>Category</th><th>Location</th></tr>";
  foreach($rows as $row)
  {
    $image = ($row['image_size'] > 0) ? "<img src=\"image.php?source=item&id={$row['id']}&type=thumbnail\"/>" : "";
    echo <<<HTML
    <tr>
      <td>
        <a href="item.php?id={$row["id"]}"/>{$row["name"]}</a>
      </td>
      <td>
        {$row["description"]}
      </td>
      <td>
        {$row["category_name"]}
      </td>
      <td>
        {$row["parent_name"]}
      </td>
      <td>
        {$image}
      </td>
      <td>
        <form method="post" >
          <input type="submit" value="Delete"/>
          <input type="hidden" name="id" value="{$row["id"]}"/>
          <input type="hidden" name="action" value="delete">
        </form>
      </td>
      <td>
        <form method="post" >
          <input type="submit" value="Edit"/>
          <input type="hidden" name="id" value="{$row["id"]}"/>
          <input type="hidden" name="action" value="start-edit">
        </form>
      </td>
    </tr>
  HTML;
  }
}

echo "</table>";

require("src/footer.php");
?>

