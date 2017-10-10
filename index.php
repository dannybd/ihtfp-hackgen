<?php

if (isset($_GET['dev'])) $_POST = $_POST + $_GET; //REMOVE AFTER DEVELOPMENT

function parse_existing_hack_info() {
  if (isset($_POST['submit'])) {
    return $_POST;
  }
  if (!isset($_GET['path'])) {
    return array();
  }
  preg_match("/^(\d{4})\/(\w+)\/?$/", $_GET['path'], $path_matches);
  $hack = array();
  $hack['year'] = $path_matches[1];
  $hack['slug'] = $path_matches[2];

  $xml = @simplexml_load_file(
    './by_year/'.$hack['year'].'/'.$hack['slug'].'/'.$hack['slug'].'.hack.xml'
  );
  if (!$xml) {
    return $hack;
  }
  $hack_xml = $xml->hack;

  $hack['title'] = (string)$hack_xml->title;
  $hack['summary'] = preg_replace(
    "/<\/?summary\/?>/",
    "",
    $hack_xml->summary->asXML()
  );
  $hack['writeup'] = preg_replace(
    "/<\/?writeup\/?>/",
    "",
    $hack_xml->writeup->asXML()
  );
  $hack['additional-photos'] = preg_replace(
    "/<\/?additional-photos\/?>/",
    "",
    $hack_xml->{'additional-photos'}->asXML()
  );

  preg_match(
    "/^(\d{4}\.\d\d\.\d\d)-?(\d{4}\.\d\d\.\d\d)?$/",
    (string)$hack_xml->when['date'],
    $when_matches
  );

  $hack['whenstart'] = (string)$when_matches[1];
  $hack['whenend'] = (string)$when_matches[2];
  $hack['whendesc'] = (string)$hack_xml->when;

  $hack['wheredesc'] = trim((string)$hack_xml->where);
  $hack['where'] = (string)$hack_xml->where['building'];

  $hack['locations'] = '';
  foreach ($hack_xml->where->location as $location) {
    $hack['locations'] .= (string)$location['tag'].', ';
  }

  $hack['whoid'] = (string)$hack_xml->who['hide'];
  $hack['whoname'] = (string)$hack_xml->who;

  $hack['types'] = '';
  foreach ($hack_xml->type as $type) {
    $hack['types'] .= (string)$type['tag'].', ';
  }

  $hack['keywords'] = '';
  foreach ($hack_xml->keyword as $keyword) {
    $hack['keywords'] .= (string)$keyword.', ';
  }

  $hack['related'] = '';
  foreach ($hack_xml->related as $related) {
    $hack['related'] .= (string)$related['path'].', ';
  }

  return $hack;
}
$hack = parse_existing_hack_info();

function commas_to_tags($list, $pre, $post) {
  // Take a comma-separated list and map each item into an XHTML tag
  $list = explode(',', $list);
  foreach ($list as &$item) {
    if (trim($item)) {
      $item = $pre.trim($item).$post;
    }
  }
  return implode('', $list);
}

function get_existing_hack_slugs() {
  $directories = glob('./by_year/*/*', GLOB_ONLYDIR);
  $directories = array_filter(
    $directories,
    function($dir) {return preg_match('/^\.\/by_year\/\d{4}\/[\w\-]+$/', $dir);}
  );

  $directories = array_map(
    function($dir) {return substr($dir, strlen('./by_year/'));},
    $directories
  );
  return array_reverse($directories);
}

function get_existing_type_tags() {
  $src = file_get_contents('../../admin-docs/tags.html');
  $src = strstr($src, '<a name="type"></a>');
  $src = strstr($src, '<a name="location"></a>', true);
  preg_match_all('|<tr>\s*<td>.*?</td>\s*<td>(.*?)</td>|sm', $src, $matches);
  return array_values(array_filter($matches[1]));
}

function get_existing_location_tags() {
  $src = file_get_contents('../../admin-docs/tags.html');
  $src = strstr($src, '<a name="location"></a>');
  preg_match_all('|<tr>\s*<td>.*?</td>\s*<td>(.*?)</td>|sm', $src, $matches);
  return array_values(array_filter($matches[1]));
}

if (isset($_POST['submit'])) {
  $_POST['path'] = $_POST['year'].'/'.$_POST['slug'];
  $_POST['who'] = '<who hide="1">unknown</who>';
  if ($_POST['whoid']) {
    $_POST['who'] = (
      '<who group="'.$_POST['whoid'].'" hide="0">'.$_POST['whoname'].'</who>'
    );
  }
  $_POST['when'] = $_POST['whenstart'];
  if ($_POST['whenend']) {
    $_POST['when'] .= '-'.$_POST['whenend'];
  }
  if (!$_POST['whendesc']) {
    if ($_POST['whenend']) {
      $_POST['whendesc'] .= ' - '.date('F j, Y', strtotime(strtr(
        $_POST['whenend'], '.', '-'
      )));
    }
  }
  $_POST['locations'] = commas_to_tags($_POST['locations'], '<location tag="', '" />');
  $_POST['types'] = commas_to_tags($_POST['types'], '<type tag="', '" />');
  $_POST['keywords'] = commas_to_tags($_POST['keywords'], '<keyword>', '</keyword>');
  $_POST['related'] = commas_to_tags($_POST['related'], '<related path="', '" />');

  $xml =  <<<EOD
<?xml version="1.0" encoding='ISO-8859-1' standalone='no'?>

<!-- DO NOT EDIT DOCTYPE STANZA -->
<!DOCTYPE hack-gallery [
   <!ENTITY photo-index SYSTEM "photo-index.xml">
]>

<hack-gallery>
  <hack path="{$_POST['path']}"
        publish="1"
        complete="1"
        generate_writeup="1">
    <title>{$_POST['title']}</title>
    <summary>{$_POST['summary']}</summary>
    <when date="{$_POST['when']}">{$_POST['whendesc']}</when>
    <where building="{$_POST['where']}">
      {$_POST['wheredesc']}
      {$_POST['locations']}
    </where>
    {$_POST['who']}
    {$_POST['types']}
    {$_POST['keywords']}
    {$_POST['related']}
    <writeup>{$_POST['writeup']}</writeup>
    <additional-photos>{$_POST['additional-photos']}</additional-photos>
    <version rcsid="\$Header\$" lastmod="\$Date\$" />
  </hack>
</hack-gallery>

EOD;

  $base = './by_year/';
  @mkdir($base.$_POST['path'], 0644, true);
  $file_save = @file_put_contents($base.$_POST['path'].'/'.$_POST['slug'].'.hack.xml', $xml);
  $file_locked = $file_save === FALSE;
}

header('Content-type: text/html; charset=utf-8');
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
    .ui-autocomplete {
      max-height: 100px;
      overflow-y: auto;
      /* prevent horizontal scrollbar */
      overflow-x: hidden;
    }
  </style>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
  <script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
</head>
<body>
<?php if ($xml && !$file_locked) { ?>
<ol>
  <li>
    Copy photos into <tt>by_year/<?= $_POST['path'] ?></tt>.
  </li>
  <li>
    Run the hackgen script:
    <pre>
scripts/hackgen <?= $_POST['path'] ?>
    </pre>
  </li>
  <li>
    Check how things look here: <a href="http://hacks.mit.edu/by_year/<?= $_POST['path'] ?>">http://hacks.mit.edu/by_year/<?= $_POST['path'] ?></a>
  </li>
  <li>
    <strong>New!</strong> You can now edit the XML file more from this editor, here: <a href="http://hacks.mit.edu/admin-tools/addhack/?path=<?= $_POST['path'] ?>">http://hacks.mit.edu/admin-tools/addhack/?path=<?= $_POST['path'] ?></a>
  </li>
  <li>
    Keep making edits and running <tt>scripts/hackgen <?= $_POST['path'] ?></tt> until you're satisfied.
  </li>
  <li>
    Then, check in to RCS, and update the index:
    <pre>
ci -u by_year/<?= $_POST['path'] ?>/<?= $_POST['slug'] ?>.hack.xml

scripts/hackgen <?= $_POST['path'] ?>

scripts/hackgen index
    </pre>
  </li>
</ol>
<?php } else { ?>
<?php if ($file_locked) { ?>
<p>
  <strong>Warning:</strong> The XML file is currently locked! Run this:
  <pre>
co -l by_year/<?= $_POST['path'] ?>/<?= $_POST['slug'] ?>.hack.xml
  </pre>
  and then try again.
</p>
<hr />
<?php  } ?>
<h2>IHTFP Hack Gallery Submission Generator</h2>
<h3>Trying to simplify the hack addition process.</h3>
<p>
  <strong>New: Load an existing hack by adding <tt>?path=[year]/[slug]</tt> to the URL.</strong>
  Try it out with <a href="?path=2017/glados">this example</a>.
</p>
<form id="hackgen" method="post" action="">
<p>
  <label for="year" class="required">Year of hack:</label><br>
  <input id="year" name="year" type="text" placeholder="<?= date('Y') ?>" required value="<?= $hack['year'] ?>" />
</p>

<p>
  <label for="slug" class="required">Hack slug:</label><br>
  <em>This is the unique identifier for the hack. The file should also be named "HACK_NAME.hack.xml".</em><br>
  <input id="slug" name="slug" type="text" placeholder="hack_name" required value="<?= $hack['slug'] ?>" />
</p>

<p>
  <label for="title" class="required">Hack title:</label><br>
  <em>This should be similar to a headline in length and style. We tend to capitalize proper nouns and not capitalize other words (similar to newspaper headlines).</em><br>
  <input id="title" name="title" type="text" placeholder="Name of the Hack" required value="<?= $hack['title'] ?>" />
</p>

<p>
  <label for="summary" class="required">Hack summary:</label><br>
  <em>The summary should be a one or two sentence summary of the hack. It may be XHTML containing tags such as &lt;i&gt; and &lt;b&gt;. It will be included on detailed index pages below the title.</em><br>
  <textarea id="summary" name="summary" cols="80" placeholder="A thing went into a place!" required><?= $hack['summary'] ?></textarea>
</p>

<p>
  <label for="writeup" class="required">Hack writeup:</label><br>
  <em>The writeup section is the primary writeup for the hack. It may contain fairly arbitrary XHTML. For example, paragraphs should be in &lt;p&gt;...&lt;/p&gt; blocks. You may also have multiple &lt;photo&gt; tags within a writeup (which are described in great detail <a href="http://hacks.mit.edu/admin-docs/howto-update.html#photos">here</a>).</em><br>
  <textarea id="writeup" name="writeup" cols="80" rows="20" required><?= $hack['writeup'] ?></textarea>
</p>

<p>
  You can upload photos and generate the &lt;photo&gt; tags <a href="photos.php" onclick="window.open('photos.php','popup','width=600,height=600');return false;" target="popup">here</a>.
</p>

<p>
  <label for="additional-photos">Additional photos (optional):</label><br>
  <em>This may be used as a container for more &lt;photo&gt; tags that you don't want in the main page. They will be displayed on the additional information page.</em><br>
  <textarea id="additional-photos" name="additional-photos" cols="80"><?= $hack['additional-photos'] ?></textarea>
</p>

<p>
  <label for="whenstart" class="required">Date of hack [YYYY.MM.DD]:</label><br>
  <input id="whenstart" name="whenstart" type="text" placeholder="YYYY.MM.DD" maxlength="12" required value="<?= $hack['whenstart'] ?>" /><br>
  <label for="whenend">End date of hack [YYYY.MM.DD] (optional):</label><br>
  <input id="whenend" name="whenend" type="text" placeholder="YYYY.MM.DD" maxlength="12" value="<?= $hack['whenend'] ?>" /><br>
  <label for="whendesc">Date description (optional):</label><br>
  <em>Include a description inside of the tag. If this is used, the description will be used in the summary on the hack write-up page (and thus should contain the date as well).</em><br>
  <input id="whendesc" name="whendesc" type="text" placeholder="December 24, 2007 (Christmas Eve)" value="<?= $hack['whendesc'] ?>" />
</p>

<p>
  <label for="wheredesc" class="required">Description of where hack took place:</label><br>
  <input id="wheredesc" name="wheredesc" type="text" placeholder="The Great Dome (Building 10)" required value="<?= $hack['wheredesc'] ?>" /><br>
  <label for="where">Building number where hack took place (optional):</label><br>
  <input id="where" name="where" type="text" placeholder="10" value="<?= $hack['where'] ?>" /><br>
  <label for="locations">Location tags where hack took place (optional):</label><br>
  <em>Comma-separated. The location tag entries may be used to tag the hack as having been in various locations or types of locations. These may be used to add it to various index files. <a href="http://hacks.mit.edu/admin-docs/tags.html#location">Here is a list of the current location tags in use.</a> You should try and use existing tags or tags of similar styles.</em><br>
  <em><b>New!</b> Now supports auto-complete to make finding tags easier.</em><br>
  <input id="locations" name="locations" type="text" placeholder="dome/m10,grounds/killian" value="<?= $hack['locations'] ?>" />
</p>

<p>
  <label for="whoid">Group ID of perpetrators, if known (optional):</label><br>
  <em>If the hack is explictly self-signed such that the public knows who pulled it, fill this in with a short identifier for the group along with a description of them.</em><br>
  <input id="whoid" name="whoid" type="text" placeholder="ORK" value="<?= $hack['whoid'] ?>" /><br>
  <label for="whoname">Name of perpetrators, if known (optional):</label><br>
  <input id="whoid" name="whoname" type="text" placeholder="Order of the Random Knights (ORK)" value="<?= $hack['whoname'] ?>" />
</p>

<p>
  <label for="types">Hack types (optional):</label><br>
  <em>Comma-separated. These provide tags about the hack which can be used to include it in various index files. For example, the type-tags may describe the location, target, or class of hack. <a href="http://hacks.mit.edu/admin-docs/tags.html#types">Here is a list of the current type tags in use.</a> You should try and use existing tags or tags of similar styles.</em><br>
  <em><b>New!</b> Now supports auto-complete to make finding tags easier.</em><br>
  <input id="types" name="types" type="text" placeholder="event/harvard-yale,target/harvard" value="<?= $hack['types'] ?>" />
</p>

<p>
  <label for="keywords">Keywords (optional):</label><br>
  <em>Comma-separated. Keywords may contain spaces.</em><br>
  <input id="keywords" name="keywords" type="text" placeholder="foobar,barfoo" value="<?= $hack['keywords'] ?>" />
</p>

<p>
  <label for="related">Related hacks (optional):</label><br>
  <em>Comma-separated. Enter in YEAR/HACK_NAME format.</em><br>
  <em><b>New!</b> Now supports auto-complete to make finding related hacks easier.</em><br>
  <input id="related" name="related" type="text" placeholder="1994/cp_car,2014/foobar" value="<?= $hack['related'] ?>" />
</p>

<input id="submit" name="submit" type="submit" value="Next Steps" />

<div style="margin-bottom:200px;"></div>

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

  var hackSlugs = JSON.parse('<?= json_encode(get_existing_hack_slugs()) ?>');
  var typeTags = JSON.parse('<?= json_encode(get_existing_type_tags()) ?>');
  var locationTags = JSON.parse('<?= json_encode(get_existing_location_tags()) ?>');

  function split(val) {
    return val.split(/,\s*/);
  }
  function extractLast(term) {
    return split(term).pop();
  }
  function autocompleteTokenField(selector, tagSource) {
    $(selector)
      // don't navigate away from the field on tab when selecting an item
      .on("keydown", function(event) {
        if (event.keyCode === $.ui.keyCode.TAB &&
            $( this ).autocomplete( "instance" ).menu.active) {
          event.preventDefault();
        }
      })
      .autocomplete({
        minLength: 0,
        source: function(request, response) {
          // delegate back to autocomplete, but extract the last term
          response($.ui.autocomplete.filter(
            tagSource,
            extractLast(request.term)
          ));
        },
        focus: function() {
          // prevent value inserted on focus
          return false;
        },
        select: function(event, ui) {
          var terms = split(this.value);
          // remove the current input
          terms.pop();
          // add the selected item
          terms.push(ui.item.value);
          // add placeholder to get the comma-and-space at the end
          terms.push("");
          this.value = terms.join(", ");
          return false;
        }
      });
  }

  autocompleteTokenField("#locations", locationTags);
  autocompleteTokenField("#related", hackSlugs);
  autocompleteTokenField("#types", typeTags);
});
</script>
<?php } ?>
</body>
</html>
