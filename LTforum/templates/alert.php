<?php
/**
 * @pakage LTforum
 * @version 1.1 added Search command, refactored View classes
 */

/**
 * Simplistic page to show alerts and error messages.
 * @uses PageRegistry $pr
 * @uses SessionRegistry $sr
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru" xml:lang="ru">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php print( $pr->g("title")." : alert" ); ?></title>
  <link rel="stylesheet" type="text/css" href="<?php print($sr->g("assetsPath")."talk.css"); ?>" media="all" />
</head>
<body>
<!--<p style="text-align:center;"><?php print($pr->g("alert")); ?><br /></p>-->

<table class="low">
  <tr><td colspan="2"><?php print($pr->g("alert")); ?></td></tr>
  <tr>
  <td><?php
  if( !empty($pr->g("formLink")) )
  print ( "<a href=\"{$pr->g("formLink")}\">Back</a>" );
  ?></td>
  <td><?php
  if( !empty($pr->g("viewLink")) )
  print ( "<a href=\"{$pr->g("viewLink")}\">Ok</a>" );
  ?></td>
  </tr>
</table>

<p id="footer"><?php
$outcome="alert~".$pr->g("alert");
if( $sr->g("toPrintOutcome") ) print("<!--Outcome:".$outcome."-->");
?></p>
</body>
</html>