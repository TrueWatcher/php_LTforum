<?php
/**
 * @package LTforum
 * @version 1.5 added features to auth subsystem
 */

/**
 * Frontend Controller, the upper part of.
 * Initializations, AccessController call and command processor call.
 * Commands are in Act.php
 */
$mainPath=$threadEntryParams["mainPath"];
require_once ($mainPath."AssocArrayWrapper.php");
require_once ($mainPath."Registries.php");
require_once ($mainPath."CardfileSqlt.php");
require_once ($mainPath."Act.php");
require_once ($mainPath."MyExceptions.php");
require_once ($mainPath."AccessHelper.php");
require_once ($mainPath."AccessController.php");
require_once ($mainPath."Applicant.php");
require_once ($mainPath."Translator.php");

// minimal initializations
$systemWideDefaults=SessionRegistry::getDefaultsFrontend();
$sr=SessionRegistry::getInstance(1,$systemWideDefaults);
$sr->overrideValuesBy($threadEntryParams);
//$sr->dump();

$iniParams=getIniParams("../".$sr->g("forum")."/");
//print_r($iniParams);
$sr->overrideValuesBy($iniParams["thread"]);

Translator::init($sr->g("lang"),$sr->g("mainPath").$sr->g("templatePath"),1);

// here goes the Access Controller
$authDefaults=AuthRegistry::getDefaults();
$ar=AuthRegistry::getInstance(1,$authDefaults);
$ar->adjustForFrontend($sr);
$ar->overrideValuesBy($iniParams["auth"]);
$ar->overrideValuesBy($iniParams["intervals"]);
$ac=new AccessController($ar);
$acRet=$ac->go();
//echo("Trace:".$ar->g("trace")."\n");
//echo( "Alert:".$ar->g("alert")."\n" );
if($acRet!==true) {
  $ac->display();
  exit($acRet);
}

// more initializations using database
$pr=PageRegistry::getInstance( 0, [] );
$pr->initAllAfterAuth(null, null, $sr, "Act", $sr->g("title"), $sr->g("forum"));

if ( $pr->queryStringIsEmpty() ) { $vr=Act::redirectToView($pr); }
else { $vr=Act::go($sr,$pr); }

if ( ! $vr instanceof ViewRegistry ) {
  var_dump($vr);
  throw new UsageException ("Non-object result");  
}

$vr->display($sr,$pr);

?>