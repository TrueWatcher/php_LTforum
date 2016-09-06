<?php
/**
 * @pakage LTforum
 * @version 1.1 search command
 */
 /**
 * A View to display a list of messages plus some nice control elements.
 * Workable :).
 * @uses $vr ViewRegistry
 * @uses $pr PageRegitry
 * @uses $sr SessionRegitry
 */
require_once("RollElements.php");
$cc=$vr->g("controlsClass");
//print("@$cc");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php print( $pr->g("title")." : ".$cc::titleSuffix($vr) ); ?></title>
  <link rel="stylesheet" type="text/css" href="<?php print($sr->g("assetsPath")."talk.css") ?>" media="all" />
</head>
<body>
<table class="low"><tr>
  <td><?php print ( $cc::prevPageLink($vr,"Previous page",false,"footer") ); ?></td>
  <td><?php print ( $cc::numberForm($vr) ); ?></td>
  <td><?php print ( $cc::searchLinkForm($vr) ); ?></td>
</tr></table>
<?php if( $pr->g("alert") ) print("<hr/><p class=\"n\">".$pr->g("alert")."</p>"); ?>
<?php
$j=0;
if ( !empty($vr->g("msgGenerator")) ) {
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
</tr></table>
<p id="footer"><?php 
$outcome="viewed~".$j; 
if( $sr->g("toPrintOutcome") ) print("<!--Outcome:".$outcome."-->"); 
?></p>
<?php //print ( $cc::onreadyScript() ); ?>
</body>
</html>
