<?php

if (isset($_GET['dev'])) $_POST = $_POST + $_GET; //REMOVE AFTER DEVELOPMENT

function dieJSON($obj) {
  header("Content-type: text/javascript");
  die(json_encode($obj));
}

$src = $_POST['src'];

$mime_type = getimagesize($src)['mime'];
switch ($mime_type) {
  case 'image/jpeg':
    $image = imagecreatefromjpeg($src);
    break;
  case 'image/png':
    $image = imagecreatefrompng($src);
    break;
  default:
    dieJSON(array(
      'error' => 'Unsupported image type for rotation. Contact dannybd@mit.edu for more help.',
    ));
    break;
}

$rotated = imagerotate($image, -90, 0);

switch ($mime_type) {
  case 'image/jpeg':
    imagejpeg($rotated, $src);
    break;
  case 'image/png':
    imagepng($rotated, $src);
    break;
  default:
    dieJSON(array(
      'error' => 'Unsupported image type for rotation. Contact dannybd@mit.edu for more help.',
    ));
    break;
}

imagedestroy($image);
imagedestroy($rotated);

dieJSON(array(
  'src' => $src,
));
