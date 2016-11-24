<?php
/**
 * @pakage LTforum
 * @version 1.2 added SessionManager
 */

/**
 * Generic Form for authentication: plaintext, opportunistic digest, strict digest
 * @uses ViewRegistry $vr
 * @uses PageRegistry $apr
 * @uses SessionRegistry $asr
 */

$cc=$ar->g("controlsClass");
if ( !is_subclass_of($cc,"AuthElements") ) throw new UsageException ("Layout authForm.php should be used with subclasses of AuthElements, where ".$cc." does not belong");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru" xml:lang="ru">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php print( /*$pr->g("title").*/" : ".$cc::titleSuffix($ar) ); ?></title>
  <link rel="stylesheet" type="text/css" href="<?php print(/*$sr->g("assetsPath").*/"form.css") ?>" media="all" />
  <?php print ( $cc::scriptHelper() ); ?>  
</head>
<body>
<form action="" method="post" id="authForm" >
  <fieldset>
    <?php print ( $cc::hiddenFields($ar) ); ?>
    <table>
      <?php
      print ( $cc::realmP($ar) );
      print ( $cc::authorInput("Your name :","user","",null,null) );
      print ( $cc::pswInput("Your password :","ps","",null,null) );
      print ( $cc::plainChkbx () );
      print ( $cc::alertP ($ar) );      ?>
      <tr>
        <td id="enter" colspan="2"><input class="submit" type="submit" value="Submit" /></td>
      </tr>
    </table>
  </fieldset>
</form>
<?php
  print ( $cc::scriptOnready() );
?>
</body>
</html>