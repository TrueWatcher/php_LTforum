<?php
/**
 * @pakage LTforum
 * @version 1.1 + search command
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

<form action="" method="get" id="export">
  <fieldset>
    Export to html<br />
    begin : <input type="text" name="begin" />,<br />
    end : <input type="text" name="end" /> or max size (x1000B) : <input type="text" name="kb" /><br />
    new IDs start from (blank to discard IDs, * to copy from forum) :
    <input type="text" name="newBegin" /><br />
    target file: <input type="text" name="obj" />.html<br />    
    <input type="hidden" name="act" value="exp" />
    <input type="hidden" name="forum" value="<?php print( $apr->g("forum") ) ?>" />
    <input type="hidden" name="pin" value="<?php print( $apr->g("pin") ) ?>" />
    <input type="submit" value="Export" />
  </fieldset>
</form>

<form action="" method="get" id="import">
  <fieldset>
    Import from html<br />
    file : <input type="text" name="obj" />.html<br />
    order : <input type="radio" name="order" value="desc" /> from new to old, descending&nbsp;&nbsp;  
    <input type="radio" name="order" value="asc" checked="checked" /> from old to new, ascending<br /> 
    <input type="hidden" name="act" value="imp" />
    <input type="hidden" name="forum" value="<?php print( $apr->g("forum") ) ?>" />
    <input type="hidden" name="pin" value="<?php print( $apr->g("pin") ) ?>" />    
    <input type="submit" value="Import" />
  </fieldset>
</form>

<form action="" method="get" id="delRange">
  <fieldset>
    Delete a message block near begin or end of the forum<br />
    from : <input type="text" name="begin" /> to : <input type="text" name="end" value="<?php print( $apr->g("end") ) ?>" /><br />
    <input type="hidden" name="act" value="dr" />
    <input type="hidden" name="forum" value="<?php print( $apr->g("forum") ) ?>" />
    <input type="hidden" name="pin" value="<?php print( $apr->g("pin") ) ?>" />    
    <input type="submit" value="Delete" />    
  </fieldset>
</form>

<form action="" method="get" id="editAny">
  <fieldset>
    Edit any message<br />
    id : <input type="text" name="end" /><br />
    <input type="hidden" name="act" value="ea" />
    <input type="hidden" name="forum" value="<?php print( $apr->g("forum") ) ?>" />
    <input type="hidden" name="pin" value="<?php print( $apr->g("pin") ) ?>" />    
    <input type="submit" value="Edit" />    
  </fieldset>
</form>

<!--

Renumber from
-->

<p id="footer"></p>
<!--<table class="low"><tr>
  <td><?php
  if( !empty($apr->g("formLink")) )
  print ( "<a href=\"{$apr->g("formLink")}\">Try again</a>" );
  ?></td>
  <td><?php
  if( !empty($apr->g("viewLink")) )
  print ( "<a href=\"{$apr->g("viewLink")}\">Go read messages</a>" );
  ?></td>
</tr></table>-->
</body>
</html>