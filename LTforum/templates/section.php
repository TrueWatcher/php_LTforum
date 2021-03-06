<?php
/**
 * @package LTforum
 * @version 1.4
 */
 /**
 * A View to display a list of messages plus some nice control elements.
 * Workable :).
 * @uses $vr ViewRegistry
 * @uses $pr PageRegitry
 * @uses $sr SessionRegitry
 */

$cc=$vr->g("controlsClass");
if ( !is_subclass_of($cc,"SectionElements") ) throw new UsageException ("Layout form.php should be used with subclasses of SectionElements, where ".$cc." does not belong");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php print( $pr->g("title")." : ".$cc::titleSuffix($vr) ); ?></title>
  <link rel="stylesheet" type="text/css" href="<?php print($sr->g("assetsPath")."talk.css") ?>" media="all" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.5" />
</head>
<body>
<table class="low"><tr>
  <td><?php print ( $cc::prevPageLink($vr,l("Previous page"),false,"footer") ); ?></td>
  <td><?php print ( $cc::numberForm($vr) ); ?></td>
  <td><?php print ( $cc::searchLinkForm($vr) ); ?></td>
</tr></table>
<?php if( $pr->checkNotEmpty("alert") ) print("<hr/><p class=\"n\">".l($pr->g("alert"))."</p>"); ?>
<?php
$j=0;
if ( $vr->checkNotEmpty("msgGenerator") ) {
  foreach ($vr->g("msgGenerator") as $j=>$msg) {
    print( $cc::oneMessage($msg,$cc::localControls($msg,$vr,$pr),$vr) );
  }
}
?>
<?php if( ($ba=$cc::BottomAlert($pr,$j)) ) print("<hr/><p class=\"n\">".$ba."</p>"); ?>
<hr />
<table class="low"><tr>
  <td><?php
  print ( $cc::nextPageLink($vr,$lastPage) );
  if ($lastPage) print ( $cc::newMsgLink($vr) );
  ?></td>
  <td><?php print ( $cc::pagePanel($vr) ); ?></td>
  <td><?php print ( $cc::lengthForm($vr) ); ?></td>
  <?php print ( $cc::logoutLink() ); ?>
</tr></table>
<p id="footer"></p>
<?php print ( $cc::onreadyScript($sr) ); ?>
</body>
</html>
