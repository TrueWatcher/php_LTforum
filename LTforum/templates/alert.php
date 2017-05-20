<?php
/**
 * @package LTforum
 * @version 1.4
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
<?php 
if (isset($vr) && $vr->checkNotEmpty("alert")) $alert=l($vr->g("alert"));
else if ($pr->checkNotEmpty("alert")) $alert=l($pr->g("alert"));
else $alert="";
?>
<!--<p style="text-align:center;"><?php /*var_dump($alert);*/ print($alert); ?><br /></p>-->

<table class="low">
  <tr><td colspan="2"><?php print($alert); ?></td></tr>
  <tr>
  <td><?php
  if( $pr->checkNotEmpty("formLink") )
  print ( "<a href=\"{$pr->g("formLink")}\">".l("Back")."</a>" );
  ?></td>
  <td><?php
  if( $pr->checkNotEmpty("viewLink") )
  print ( "<a href=\"{$pr->g("viewLink")}\">Ok</a>" );
  ?></td>
  </tr>
</table>

<p id="footer"></p>
</body>
</html>