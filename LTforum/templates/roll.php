<?php
/**
 * @pakage LTforum
 * @version 1.0 experimental deployment
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
  <title><?php print( $pr->g("title")." : ".$vr->g("begin")."..".$vr->g("end")." (".$vr->g("pageCurrent")."/".$vr->g("pageEnd").")" ); ?></title>
  <link rel="stylesheet" type="text/css" href="<?php print($sr->g("assetsPath")."talk.css") ?>" media="all" />
</head>
<body>
<table class="low"><tr>
  <td><?php print ( RollElements::prevPageLink($vr) ); ?></td>
  <td><?php print ( RollElements::numberForm($vr) ); ?></td>
</tr></table>
<?php
foreach ($vr->g("msgGenerator") as $i=>$msg) { 
  print( RollElements::oneMessage($msg,RollElements::editLink($msg,$vr,$pr).RollElements::idTitle($msg)) ); 
}
?>
<hr />
<p id="footer"><?php 
$outcome="viewed~".$i; 
if( $sr->g("toPrintOutcome") ) print("<!--Outcome:".$outcome."-->"); 
?></p>
<table class="low"><tr>
  <td><?php 
  print ( RollElements::nextPageLink($vr,$lastPage) );
  if ($lastPage) print ( RollElements::newMsgLink($vr) );
  ?></td>
  <td><?php print ( RollElements::pagePanel($vr) ); ?></td>
  <td><?php print ( RollElements::lengthForm($vr) ); ?></td>
  <td></td>
</tr></table>
</body>
</html>
