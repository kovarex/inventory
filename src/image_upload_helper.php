<?php

function imageUploadErrorMessage($error)
{
  switch ($error)
  {
    case UPLOAD_ERR_INI_SIZE:
      return "The image is too big, the server limit (upload_max_filesize) is ".ini_get("upload_max_filesize").".";
    case UPLOAD_ERR_FORM_SIZE:
      return "The image is too big.";
    case UPLOAD_ERR_PARTIAL:
      return "The image was only partially uploaded.";
    case UPLOAD_ERR_NO_TMP_DIR:
      return "The server is missing a temporary folder for uploads.";
    case UPLOAD_ERR_CANT_WRITE:
      return "The server failed to write the uploaded image to disk.";
    case UPLOAD_ERR_EXTENSION:
      return "A PHP extension stopped the image upload.";
    default:
      return "Image upload failed (error code ".$error.").";
  }
}

function tryToProcessImageUpload()
{
  if (!isset($_FILES["image"]) || $_FILES["image"]["error"] == UPLOAD_ERR_NO_FILE)
    return;

  if ($_FILES["image"]["error"] != UPLOAD_ERR_OK)
  {
    echo "<div>".imageUploadErrorMessage($_FILES["image"]["error"])."</div>";
    return;
  }

  if (!is_uploaded_file($_FILES["image"]["tmp_name"]))
    return;

  if (!function_exists("imagecreatefromjpeg"))
  {
    echo "<div>Image processing isn't available, the PHP GD extension (php-gd) is not installed for this PHP version.</div>";
    return;
  }

  $imageFileName = $_FILES["image"]["tmp_name"];
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