<?php

if (isset($_GET['dev'])) $_POST = $_POST + $_GET; //REMOVE AFTER DEVELOPMENT

function dieJSON($obj) {
  header("Content-type: text/javascript");
  die(json_encode($obj));
}

if ($src = $_POST['src']) {
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
}

$valid_url = false;
if ($url = $_GET['url']) {
  $valid_url = true;
  if (!preg_match('/^by_year\/\d{4}\/[\w\-]+\//', $url) || strpos($url, '/..') !== false) {
    $valid_url = false;
    $error_msg = 'Bad image path! (Should be formatted like `by_year/2017/hack_name/photo.jpg`)';
  } else {
    $image_data = @getimagesize('./'.$url); // ['mime'];
    if (!$image_data) {
      $valid_url = false;
      $error_msg = 'Not an image! (Should be formatted like `by_year/2017/hack_name/photo.jpg`)';
    }
  }
}

header('Content-type: text/html; charset=utf-8');
header('X-UA-Compatible: IE=edge,chrome=1');
?>
<!doctype html>
<html lang="en-us">
<head>
  <title>Hack Submission Generator</title>
  <style>
    body{font-size:14px;font-family:sans-serif;}
    label{font-weight:bold;}
    label.required{color:red;}
    em{font-size:12px;}
    li{margin-bottom:10px;}
    pre{margin:5px 10px 0px;}
  </style>
  <script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
</head>
<body>
<h3>Photo rotator!</h3>
<?php if ($valid_url) { ?>
<p>
  Here is how this photo currently renders:<br>
  <img id="uploaded-photo" src="<?= $url ?>" style="max-height:600px;" /><br>
  Want to rotate it?
  <input type="button" id="rotate-right" value="Rotate Right" />
</p>
<script type="text/javascript">
var src = '<?= $url ?>';
$(function() {
  $('#rotate-right').on('click', function() {
    $('#rotate-right').attr('disabled', true);
    $.post('rotate_image.php', {src: src}, function(data) {
      $('#rotate-right').attr('disabled', false);
      if (data.error) {
        alert(data.error);
        return;
      }
      $("#uploaded-photo").attr('src', data.src+'?'+(new Date()).getTime());
    }, 'json');
  });
});
</script>
<?php } else { ?>
<?= $error_msg ? "<b>$error_msg</b>" : '' ?>
<p>
  Enter the path for the image you want to rotate.<br>
  The path you enter should be the part of the URL which starts at `by_year`:</br>
  <form method="get" action="rotate_image.php">
    <input
      type="text"
      name="url"
      size="60"
      placeholder="by_year/<?= date('Y') ?>/hack_name/photo.jpg"
      value="<?= $url ?>"
    /><br>
    <input type="submit" />
  </form>
</p>
<?php } ?>
</body>
</html>