<?php

if (isset($_GET['dev'])) $_POST = $_POST + $_GET; //REMOVE AFTER DEVELOPMENT

$xml = null;

function commas_to_tags($list, $pre, $post) {
  $list = explode(',', $list);
  foreach ($list as &$item) {
    if ($item) {
      $item = "$pre$item$post";
    }
  }
  return implode('', $list);
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
        publish="0"
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
    <type tag="calendar/ro">More description of type</type>
    {$_POST['types']}
    {$_POST['keywords']}
    {$_POST['related']}
    <writeup>{$_POST['writeup']}</writeup>
    <additional-photos>{$_POST['additional-photos']}</additional-photos>
    <version rcsid="$Header$"
       lastmod="$Date$" />
  </hack>
</hack-gallery>

EOD;

  $tmpfname = tempnam('./tmp', 'hackgen_');
  chmod($tmpfname, 0644);
  $handle = fopen($tmpfname, 'w');
  fwrite($handle, $xml);
  fclose($handle);
  // print_r($_POST);
  // die();
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
<? if ($xml) { ?>
<ol>
  <li>
    Create the hack's directory:<br>
    <pre>
cd /afs/sipb.mit.edu/contrib/hacks
aklog sipb.mit.edu
mkdir -p by_year/<?= $_POST['path'] ?>
    </pre>
  </li>
  <li>
    Copy photos into <tt>by_year/<?= $_POST['path'] ?></tt>.
  </li>
  <li>
    Move the generated XML into the hack directory:
    <pre>
cp <?= $tmpfname ?> by_year/<?= $_POST['path'] ?>/<?= $_POST['slug'] ?>.hack.xml</pre>
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
    Keep making edits and running <tt>scripts/hackgen <?= $_POST['path'] ?></tt> until you're satisfied.
  </li>
  <li>
    Publish, check in to RCS, and update the index:
    <pre>
sed -i bak '0,/publish="0"/{s/publish="0"/publish="1"/}' by_year/<?= $_POST['path'] ?>/<?= $_POST['slug'] ?>.hack.xml
ci -u by_year/<?= $_POST['path'] ?>/<?= $_POST['slug'] ?>.hack.xml
scripts/hackgen <?= $_POST['path'] ?>

scripts/hackgen index
    </pre>
  </li>
</ol>
<? } else { ?>
<h2>IHTFP Hack Gallery Submission Generator</h2>
<h3>Trying to simplify the hack addition process.</h3>
<form id="hackgen" method="post" action="">
<p>
  <label for="year" class="required">Year of hack:</label><br>
  <input id="year" name="year" type="text" placeholder="<?= date('Y') ?>" required />
</p>

<p>
  <label for="slug" class="required">Hack slug:</label><br>
  <em>This is the unique identifier for the hack. The file should also be named "HACK_NAME.hack.xml".</em><br>
  <input id="slug" name="slug" type="text" placeholder="hack_name" required />
</p>

<p>
  <label for="title" class="required">Hack title:</label><br>
  <em>This should be similar to a headline in length and style. We tend to capitalize proper nouns and not capitalize other words (similar to newspaper headlines).</em><br>
  <input id="title" name="title" type="text" placeholder="Name of the Hack" required />
</p>

<p>
  <label for="summary" class="required">Hack summary:</label><br>
  <em>The summary should be a one or two sentence summary of the hack. It may be XHTML containing tags such as &lt;i&gt; and &lt;b&gt;. It will be included on detailed index pages below the title.</em><br>
  <textarea id="summary" name="summary" cols="80" placeholder="A thing went into a place!" required></textarea>
</p>

<p>
  <label for="writeup" class="required">Hack writeup:</label><br>
  <em>The writeup section is the primary writeup for the hack. It may contain fairly arbitrary XHTML. For example, paragraphs should be in &lt;p&gt;...&lt;/p&gt; blocks. You may also have multiple &lt;photo&gt; tags within a writeup (which are described in great detail <a href="http://hacks.mit.edu/admin-docs/howto-update.html#photos">here</a>).</em><br>
  <textarea id="writeup" name="writeup" cols="80" rows="20" required></textarea>
</p>

<p>
</p>

<p>
  <label for="additional-photos">Additional photos (optional):</label><br>
  <em>This may be used as a container for more &lt;photo&gt; tags that you don't want in the main page. They will be displayed on the additional information page.</em><br>
  <textarea id="additional-photos" name="additional-photos" cols="80"></textarea>
</p>

<p>
  <label for="whenstart" class="required">Date of hack [YYYY.MM.DD]:</label><br>
  <input id="whenstart" name="whenstart" type="text" placeholder="YYYY.MM.DD" maxlength="12" required /><br>
  <label for="whenend">End date of hack [YYYY.MM.DD] (optional):</label><br>
  <input id="whenend" name="whenend" type="text" placeholder="YYYY.MM.DD" maxlength="12" /><br>
  <label for="whendesc">Date description (optional):</label><br>
  <em>Include a description inside of the tag. If this is used, the description will be used in the summary on the hack write-up page (and thus should contain the date as well).</em><br>
  <input id="whendesc" name="whendesc" type="text" placeholder="December 24, 2007 (Christmas Eve)" />
</p>

<p>
  <label for="wheredesc" class="required">Description of where hack took place:</label><br>
  <input id="wheredesc" name="wheredesc" type="text" placeholder="The Great Dome (Building 10)" required /><br>
  <label for="where">Building number where hack took place (optional):</label><br>
  <input id="where" name="where" type="text" placeholder="10" /><br>
  <label for="locations">Location tags where hack took place (optional):</label><br>
  <em>Comma-separated. The location tag entries may be used to tag the hack as having been in various locations or types of locations. These may be used to add it to various index files. <a href="http://hacks.mit.edu/admin-docs/tags.html#location">Here is a list of the current location tags in use.</a> You should try and use existing tags or tags of similar styles.</em><br>
  <input id="locations" name="locations" type="text" placeholder="dome/m10,grounds/killian" />
</p>

<p>
  <label for="whoid">Group ID of perpetrators, if known (optional):</label><br>
  <em>If the hack is explictly self-signed such that the public knows who pulled it, fill this in with a short identifier for the group along with a description of them.</em><br>
  <input id="whoid" name="whoid" type="text" placeholder="ORK" /><br>
  <label for="whoname">Name of perpetrators, if known (optional):</label><br>
  <input id="whoid" name="whoname" type="text" placeholder="Order of the Random Knights (ORK)" />
</p>

<p>
  <label for="types">Hack types (optional):</label><br>
  <em>Comma-separated. These provide tags about the hack which can be used to include it in various index files. For example, the type-tags may describe the location, target, or class of hack. <a href="http://hacks.mit.edu/admin-docs/tags.html#types">Here is a list of the current type tags in use.</a> You should try and use existing tags or tags of similar styles.</em><br>
  <input id="types" name="types" type="text" placeholder="event/harvard-yale,target/harvard" />
</p>

<p>
  <label for="keywords">Keywords (optional):</label><br>
  <em>Comma-separated. Keywords may contain spaces.</em><br>
  <input id="keywords" name="keywords" type="text" placeholder="foobar,barfoo" />
</p>

<p>
  <label for="related">Related hacks (optional):</label><br>
  <em>Comma-separated. Enter in YEAR/HACK_NAME format.</em><br>
  <input id="related" name="related" type="text" placeholder="1994/cp_car,2014/foobar" />
</p>

<input id="submit" name="submit" type="submit" value="Next Steps" />

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
