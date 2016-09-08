<?php
/**
 * @pakage LTforum
 * @version 1.1 + search command
 */
 
/**
 * Generic Form for new, editLast, editAny.
 * @uses ViewRegistry $vr
 * @uses PageRegistry $apr
 * @uses SessionRegistry $asr
 */
 
$cc=$vr->g("controlsClass");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru" xml:lang="ru">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php print( $apr->g("title")." : ".$cc::titleSuffix("",$vr) ); ?></title>
  <link rel="stylesheet" type="text/css" href="<?php print($sr->g("assetsPath")."form.css") ?>" media="all" />
</head>
<body>
<form action="" method="post" >
  <fieldset>
    <?php print ( $cc::hiddenFields($pr) ); ?>
    <table>
      <?php 
      print ( $cc::idP($vr) );
      print ( $cc::authorInput(null,null,null,$vr) );
      print ( $cc::deleteChkbx () );
      print ( $cc::clearChkbx () );
      print ( $cc::txtText ($sr,$vr) );
      print ( $cc::commText ($sr,$vr) );
      print ( $cc::snapChkbx () );      
      ?>
      <tr>
        <td id="enter" colspan=2><input class="submit" type="submit" value="Submit" /></td>
      </tr>
    </tr></table>
  </fieldset>
</form>
<?php
  print ( $cc::script1() ); 
  print ( $cc::script2() );
  print ( $cc::script3() );
?>
</body>
</html>
  
  