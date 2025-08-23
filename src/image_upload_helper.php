<?php

function tryToProcessImageUpload()
{
  if (@!is_uploaded_file($_FILES["item_image"]["tmp_name"]))
    return;

  $imageFileName = $_FILES["item_image"]["tmp_name"];
  $imageInfo = @getimagesize($imageFileName);
  if ($imageInfo === false)
  {
    echo "<div>Unrecognized image file format!</div>";
    return;
  }

  if ($imageInfo['mime'] == 'image/png')
    $imageOriginal = imagecreatefrompng($imageFileName);
  elseif (in_array($imageInfo['mime'], ['image/jpg', 'image/jpeg', 'image/pjpeg']))
    $imageOriginal = imagecreatefromjpeg($imageFileName);
  else
  {
    echo "<div>Unsupported image file format!</div>";
    return;
  }

  $aspect = $imageInfo['0'] / $imageInfo['1'];

  if (!($imageInfo['0']<BIG_IMAGE_SIZE && $imageInfo['1'] < BIG_IMAGE_SIZE))
  {
    $scaleBigWidth=($aspect>1) ? BIG_IMAGE_SIZE : (BIG_IMAGE_SIZE * $aspect);
    $scaleBigHeight=($aspect>1) ? BIG_IMAGE_SIZE / $aspect:BIG_IMAGE_SIZE;
    $imageResizedBig = imagescale($imageOriginal, (int)$scaleBigWidth, (int)$scaleBigHeight, IMG_BICUBIC);

    ob_start();
    imagejpeg($imageResizedBig,NULL,80);
    $imageResizedBigContents = ob_get_contents();
    ob_end_clean();
  }
  else
    $imageResizedBigContents = file_get_contents($imageFileName);

  $scaleThumbnailWidth = $aspect > 1 ? THUMBNAIL_IMAGE_SIZE : (THUMBNAIL_IMAGE_SIZE * $aspect);
  $scaleThumbnailHeight = $aspect > 1 ? (THUMBNAIL_IMAGE_SIZE / $aspect) : THUMBNAIL_IMAGE_SIZE;
  $imageResizedThumbnail = imagescale($imageOriginal, (int)$scaleThumbnailWidth, (int)$scaleThumbnailHeight, IMG_BICUBIC);

  ob_start();
  imagejpeg($imageResizedThumbnail, NULL, 80);
  $imageResizedThumbnailContents = ob_get_contents();
  ob_end_clean();

  $result["big"] = bin2hex($imageResizedBigContents);
  $result["thumbnail"] = bin2hex($imageResizedThumbnailContents);
  return $result;
}

?>