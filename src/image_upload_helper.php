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

// imagescale() returns false with some GD builds (it did on the server for IMG_BICUBIC),
// imagecopyresampled() works everywhere.
function resizeImage($image, $width, $height)
{
  $width = max(1, (int)round($width));
  $height = max(1, (int)round($height));
  $resized = imagecreatetruecolor($width, $height);
  // White background for transparent PNGs, the result is a JPEG
  imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
  imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, imagesx($image), imagesy($image));
  return $resized;
}

function imageToJpeg($image)
{
  ob_start();
  imagejpeg($image, NULL, 80);
  return ob_get_clean();
}

// Returns whether the image was changed
function applyExifOrientation(&$image, $fileName, $mime)
{
  if ($mime != 'image/jpeg' || !function_exists("exif_read_data"))
    return false;
  $exif = @exif_read_data($fileName);
  $orientation = $exif["Orientation"] ?? 1;
  if ($orientation < 2 || $orientation > 8)
    return false;

  if (in_array($orientation, [2, 5, 7]))
    imageflip($image, IMG_FLIP_HORIZONTAL);
  if ($orientation == 4)
    imageflip($image, IMG_FLIP_VERTICAL);
  $angle = [3 => 180, 5 => 90, 6 => -90, 7 => -90, 8 => 90][$orientation] ?? 0;
  if ($angle != 0)
  {
    $rotatedImage = imagerotate($image, $angle, 0);
    if ($rotatedImage === false)
      return false;
    $image = $rotatedImage;
  }
  return true;
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
    $imageOriginal = @imagecreatefrompng($imageFileName);
  elseif (in_array($imageInfo['mime'], ['image/jpg', 'image/jpeg', 'image/pjpeg']))
    $imageOriginal = @imagecreatefromjpeg($imageFileName);
  else
  {
    echo "<div>Unsupported image file format!</div>";
    return;
  }

  if ($imageOriginal === false)
  {
    echo "<div>The image couldn't be read, it may be damaged or too big.</div>";
    return;
  }

  // Phone cameras store the picture sideways and only mark the rotation in EXIF
  $rotated = applyExifOrientation($imageOriginal, $imageFileName, $imageInfo['mime']);
  $width = imagesx($imageOriginal);
  $height = imagesy($imageOriginal);
  $aspect = $width / $height;

  if ($width >= BIG_IMAGE_SIZE || $height >= BIG_IMAGE_SIZE)
  {
    $scaleBigWidth = $aspect > 1 ? BIG_IMAGE_SIZE : (BIG_IMAGE_SIZE * $aspect);
    $scaleBigHeight = $aspect > 1 ? BIG_IMAGE_SIZE / $aspect : BIG_IMAGE_SIZE;
    $imageResizedBigContents = imageToJpeg(resizeImage($imageOriginal, $scaleBigWidth, $scaleBigHeight));
  }
  elseif ($rotated)
    $imageResizedBigContents = imageToJpeg($imageOriginal);
  else
    $imageResizedBigContents = file_get_contents($imageFileName);

  $scaleThumbnailWidth = $aspect > 1 ? THUMBNAIL_IMAGE_SIZE : (THUMBNAIL_IMAGE_SIZE * $aspect);
  $scaleThumbnailHeight = $aspect > 1 ? (THUMBNAIL_IMAGE_SIZE / $aspect) : THUMBNAIL_IMAGE_SIZE;
  $imageResizedThumbnailContents = imageToJpeg(resizeImage($imageOriginal, $scaleThumbnailWidth, $scaleThumbnailHeight));

  $result["big"] = bin2hex($imageResizedBigContents);
  $result["thumbnail"] = bin2hex($imageResizedThumbnailContents);
  return $result;
}

?>