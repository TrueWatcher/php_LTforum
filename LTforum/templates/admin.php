<?php
/**
 * @pakage LTforum
 * @version 0.3.2 (tests and bugfixing) (needs admin panel and docs) workable export-import 
 */
 
/**
 * Simplistic page to show alerts and error messages.
 * @uses PageRegistry $pr
 * @uses SessionRegistry $sr
 */
 
  //print("Hi, I am admin.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru" xml:lang="ru">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php print( $apr->g("title") ); ?></title>
  <link rel="stylesheet" type="text/css" href="<?php print($asr->g("assetsPath")."talk.css"); ?>" media="all" />
</head>
<body>
<h1><?php print($apr->g("forum")); ?></h1>
<p>Messages: <?php print( $apr->g("forumBegin")."..".$apr->g("forumEnd") ); ?></p>
<hr />
<p><?php print($apr->g("alert")); ?></p>

<form action="" method="get">
  <p>
    Export to html,&nbsp;begin: 
    <input type="text" name="begin" />, end:
    <input type="text" name="end" /> or max size (KB):
    <input type="text" name="kb" /><br />
    New IDs start from (blank to discard IDs, * to copy from forum):
    <input type="text" name="newBegin" /><br />
    Target file: <input type="text" name="obj" />.html    
    <input type="hidden" name="adm" value="exp" />
    <input type="hidden" name="forum" value="<?php print( $apr->g("forum") ) ?>" />
    <input type="hidden" name="pin" value="<?php print( $apr->g("pin") ) ?>" />
    <input type="submit" value="Export" />
  </p>
</form>

<form action="" method="get">
  <p>
    Import from html, file:
    <input type="text" name="obj" />.html&nbsp;,<!--
    number&nbsp;from: <input type="text" name="begin" /> ,<br />-->
    order: <input type="radio" name="order" value="desc" checked="checked" />from latest,descending 
    <input type="radio" name="order" value="asc" />from oldest,ascending 
    <input type="hidden" name="adm" value="imp" />
    <input type="hidden" name="forum" value="<?php print( $apr->g("forum") ) ?>" />
    <input type="hidden" name="pin" value="<?php print( $apr->g("pin") ) ?>" />    
    <input type="submit" value="Import" />
  </p>
</form>

<!--
Edit any message
Check numbering
Renumber from
-->


<p id="footer"><?php 
$outcome="alert~".$apr->g("alert"); 
if( $asr->g("toPrintOutcome") ) print("<!--Outcome:".$outcome."-->"); 
?></p>
<table class="low"><tr>
  <td><?php
  if( !empty($apr->g("formLink")) )
  print ( "<a href=\"{$apr->g("formLink")}\">Try again</a>" );
  ?></td>
  <td><?php
  if( !empty($apr->g("viewLink")) )
  print ( "<a href=\"{$apr->g("viewLink")}\">Go read messages</a>" );
  ?></td>
</tr></table>
</body>
</html>