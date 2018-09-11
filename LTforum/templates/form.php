<?php
/**
 * @package LTforum
 * @version 1.4 added ini files
 */

/**
 * Generic Form for new, editLast, editAny.
 * @uses ViewRegistry $vr
 * @uses PageRegistry $pr
 * @uses SessionRegistry $sr
 */

$cc=$vr->g("controlsClass");
if ( !is_subclass_of($cc,"FormElements") ) throw new UsageException ("Layout form.php should be used with subclasses of FormElements, where ".$cc." does not belong");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru" xml:lang="ru">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php print( $pr->g("title")." : ".$cc::titleSuffix($vr) ); ?></title>
  <link rel="stylesheet" type="text/css" href="<?php print($sr->g("assetsPath")."form.css") ?>" media="all" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.5" />
</head>
<body>
<form action="" method="post" >
  <fieldset>
    <?php print ( $cc::hiddenFields($pr) ); ?>
    <table>
      <?php
      print ( $cc::idP($vr) );
      print ( $cc::authorInput(null,null,null,$vr,$pr) );
      print ( $cc::deleteChkbx () );
      print ( $cc::clearChkbx () );
      print ( $cc::txtText ($sr,$vr) );
      print ( $cc::commText ($sr,$vr) );
      print ( $cc::snapChkbx () );
      ?>
      <tr>
        <td id="enter" colspan="2">
          <input class="submit" type="submit" value="<?php echo(l("Submit")); ?>" />
        </td>
      </tr>
    </table>
  </fieldset>
</form>
<?php
  print ( $cc::script1() );
  print ( $cc::script2() );
  print ( $cc::script3() );
?>
</body>
</html>

