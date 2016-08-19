<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru" xml:lang="ru">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php print( $pr->g("title")." : alert" ); ?></title>
  <link rel="stylesheet" type="text/css" href="<?php print($sr->g("assetsPath")."talk.css"); ?>" media="all" />
</head>
<body>
<p><?php print($pr->g("alert")); ?><br /></p>
<p id="footer"><?php 
$outcome="alert~".$pr->g("alert"); 
if( $sr->g("toPrintOutcome") ) print("<!--".$outcome."-->"); 
?>
  
</p>
</body>
</html>