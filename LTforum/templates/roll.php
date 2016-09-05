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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php print( $pr->g("title")." : ".RollElements::titleSuffix($vr) ); ?></title>
  <link rel="stylesheet" type="text/css" href="<?php print($sr->g("assetsPath")."talk.css") ?>" media="all" />
</head>
<body>
<table class="low"><tr>
  <td><?php print ( RollElements::prevPageLink($vr) ); ?></td>
  <td><?php print ( RollElements::numberForm($vr) ); ?></td>
  <td><?php print ( RollElements::searchLinkForm($vr) ); ?></td>
</tr></table>
<?php if( $pr->g("alert") ) print("<hr/><p class=\"n\">".$pr->g("alert")."</p>"); ?>
<?php
foreach ($vr->g("msgGenerator") as $i=>$msg) { 
  print( RollElements::oneMessage($msg,RollElements::localControls($msg,$vr,$pr)) ); 
}
?>
<hr />
<table class="low"><tr>
  <td><?php 
  print ( RollElements::nextPageLink($vr,$lastPage) );
  if ($lastPage) print ( RollElements::newMsgLink($vr) );
  ?></td>
  <td><?php print ( RollElements::pagePanel($vr) ); ?></td>
  <td><?php print ( RollElements::lengthForm($vr) ); ?></td>
</tr></table>
<p id="footer"><?php 
$outcome="viewed~".$i; 
if( $sr->g("toPrintOutcome") ) print("<!--Outcome:".$outcome."-->"); 
?></p>
<?php print ( RollElements::onreadyScript() ); ?>
</body>
</html>
