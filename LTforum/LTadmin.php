<?php
/**
 * @package LTforum
 * @version 1.4 added ini files
 */
/**
 * LTforum Admin panel, common for all forum-threads.
 * Requires forumName and, if authentication is absent, PIN
 */
$mainPath=$adminEntryParams["mainPath"];
require_once ($mainPath."AssocArrayWrapper.php");
require_once ($mainPath."Registries.php");
require_once ($mainPath."CardfileSqlt.php");
require_once ($mainPath."Act.php");
require_once ($mainPath."MyExceptions.php");
require_once ($mainPath."AdminAct.php");
require_once ($mainPath."AccessHelper.php");
require_once ($mainPath."AccessController.php");
require_once ($mainPath."Applicant.php");
require_once ($mainPath."UserManager.php");
require_once ($mainPath."Translator.php");

$adminDefaults=SessionRegistry::getDefaultsBackend();
$asr=SessionRegistry::getInstance(1,$adminDefaults);
$asr->overrideValuesBy($adminEntryParams);

// we need forumName from input and we need paths that depend on it, so PageRegistry is created before authentication
$apr=PageRegistry::getInstance( 0,[] );
$apr->initAdmBeforeAuth(null, null, $asr, "Act", $asr->g("adminTitle"));

// here goes the Access Controller
$authDefaults=AuthRegistry::getDefaults();
$aar=AuthRegistry::getInstance(1,$authDefaults);
$fromSr=$asr->exportToAdminAuth($apr->g("forum"));
$aar->overrideValuesBy($fromSr);
$ac=new AccessController($aar);
$acRet=$ac->go($aar);// so short
//echo("Trace:".$aar->g("trace")."\n");
//echo( "Alert:".$aar->g("alert")."\n" );
if ( $acRet !== true ) exit($acRet);

// continue PageRegistry inits with database
$apr->initAdmAfterAuth($asr);

$ret=AdminAct::go($apr,$asr);
if ($ret===false) $ret=UserManager::go($aar,$apr,$asr);

if ($ret===false ) {
  if ( $apr->g("act") ) {
    $ret=AdminAct::showAlert ("Unknown admin command:".$apr->g("act"));
  }
  else {
    $ret=ViewRegistry::getInstance(2, ViewRegistry::getAdminDefaults());
  }
}

if ( ! $ret instanceof ViewRegistry) {
  var_dump($ret);
  throw new UsageException ("Non-object result");  
}
$ret->display($asr,$apr);

?>