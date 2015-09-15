<?php

if (isset($_GET['dev'])) $_POST = $_POST + $_GET; //REMOVE AFTER DEVELOPMENT

function reasonable_filename($name) {
  //Make sure we're looking at something resembling an image
  $parts = explode('.', $name);
  if (preg_match('/[^\w\-\.]/', $name)) {
    return false;
  }
  if (count($parts) < 2) {
    return false;
  }
  $ext = strtolower(end($parts));
  if (!in_array($ext, array('jpg', 'jpeg', 'png'))) {
    return false;
  }
  return true;
}


if (isset($_POST['submit'])) {
  $image = $_FILES['file'];
  if (!$image) {
    die('Something went wrong.');
  }
  if (!$_POST['filename']) {
    $_POST['filename'] = $image['name'];
  }
  if (!reasonable_filename($image['name'])) {
    die('Bad image name.');
  }
  if (!reasonable_filename($_POST['filename'])) {
    die('Bad image name.');
  }
  if (!preg_match('/^\d{4}\/[\w\-]+$/', $_POST['slug'])) {
    die('Bad hack folder name.');
  }
  $dir = "./by_year/{$_POST['slug']}";
  if (!file_exists(dir)) {
    $tmp_dir_used = true;
    $dir = "./tmp";
  }
  if ($_POST['url']) {
    $_POST['contact'] = "href=\"{$_POST['url']}\"";
  }
  if ($_POST['email']) {
    $_POST['contact'] = "email=\"{$_POST['email']}\"";
  }
  $_POST['for-index'] = intval($_POST['for-index'] === 'on');
  $_POST['hidecredit'] = intval($_POST['hidecredit'] === 'on');

  $ext = strtolower(end(explode('.', $image['name'])));
  if (!move_uploaded_file($image['tmp_name'], "./$dir/{$_POST['filename']}")) {
    die('Photo upload failure');
  }

  // header('Content-type: text/javascript');
  // print_r($_POST);
  // print_r($_FILES);
  $photo = <<<EOD
<photo src="{$_POST['filename']}" align="{$_POST['align']}" use-size="{$_POST['use-size']}" for-index="{$_POST['for-index']}">
  <credit name="{$_POST['name']}" {$_POST['contact']} hide="{$_POST['hidecredit']}"/>
  <caption>{$_POST['caption']}</caption>
</photo>
EOD;

}

/*
header('Content-type: text/javascript');
/*/
header('Content-type: text/html; charset=utf-8');
header('X-UA-Compatible: IE=edge,chrome=1');
//*/
?>
<!doctype html>
<html lang="en-us">
<head>
  <title>Hack Submission Generator</title>
  <style>
    body{font-size:14px;}
    label{font-weight:bold;}
    label.required{color:red;}
    em{font-size:12px;}
    li{margin-bottom:10px;}
    pre{margin:5px 10px 0px;}
  </style>
  <script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
</head>
<body>
<? if ($photo) { ?>
<h3>Photo successfully uploaded!</h3>
<p>
  The photo is now located in <?= "$dir/$_POST['filename']" ?>.
</p>
<p>
  Here is the XHTML to paste into your writeup:
  <pre>
<?= htmlentities($photo) ?></pre>
</p>
<? } else { ?>
<h2>IHTFP Hack Gallery Submission Photo Upload</h2>
<h3>Trying to simplify the process.</h3>
<form id="photoupload" method="post" action="" enctype="multipart/form-data">
<p>
  Warning: only upload photos once you have generated the hack folder.<br>
  <label for="slug">Hack slug:</label>
  <input id="slug" name="slug" type="text" placeholder="1994/cp_car" required /><br>
  <label for="file">Photo:</label>
  <input id="file" name="file" type="file" required /><br>
  <label for="filename">Rename photo to (optional):</label>
  <input id="filename" name="filename" type="text" placeholder="foobar.jpg" /><br>
  <em>The FILENAME should be the root filename of the original file (eg "foo_bar.jpg") without any size modifiers.</em><br>
  <label for="align">Align:</label>
  <select id="align" name="align">
    <option value="left">Left</option>
    <option value="center" selected>Center</option>
    <option value="right">Right</option>
  </select><br>
  <label for="use-size">Use size:</label>
  <select id="use-size" name="use-size">
    <option value="original">Original</option>
    <option value="large">Large</option>
    <option value="medium" selected>Medium</option>
    <option value="small">Small</option>
    <option value="thumb">Thumb</option>
  </select>
  <label for="for-index">Use on index?</label>
  <input id="for-index" name="for-index" type="checkbox" />
</p>

<p>
  <label for="name">Photo credit name:</label>
  <input id="name" name="name" type="text" placeholder="Alyssa P Hacker" /><br>
  <em>You can use either a "email" or "href" attribute (but not both) to reference the photographer.</em><br>
  <label for="email">Photo credit email:</label>
  <input id="email" name="email" type="text" placeholder="aphacker@mit.edu" /><br>
  <label for="url">Photo credit URL:</label>
  <input id="url" name="url" type="text" placeholder="http://aphacker.mit.edu/" /><br>
  <label for="hidecredit">Hide photo credit?</label>
  <input id="hidecredit" name="hidecredit" type="checkbox" />
</p>

<p>
  <label for="caption">Photo caption:</label><br>
  <em>This should be contained within the photo tag and is a caption for the photo. This can be fairly arbitrary XHTML (within reason).</em><br>
  <textarea id="caption" name="caption" cols="80" rows="5"></textarea>
</p>

<input id="submit" name="submit" type="submit" value="Upload and Generate &lt;photo&gt; Tag" />

</form>
<script type="text/javascript">
function badForm(reason) {
  alert('Sorry, the form failed validation. Check things and resubmit.');
  console.log(reason);
  return false;
}
$(function() {
  $('#hackgen').on('submit', function() {
    if (!$('#year').val().match(/\d{4}/)) {
      return badForm('Please enter a valid year');
    }
    if (!$('#slug').val().match(/\w+/)) {
      return badForm('Please enter a valid slug');
    }
    if (!$('#title').val()) {
      return badForm('Please enter a title');
    }
    if (!$('#whenstart').val().match(/\d{4}\.\d\d\.\d\d/)) {
      return badForm('Please enter a valid date, formatted YYYY.MM.DD');
    }
    if (!$('#wheredesc').val()) {
      return badForm('Please enter a description of the location where the hack took place');
    }
    return true;
  });
});
</script>
<? } ?>
</body>
</html>
