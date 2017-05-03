<?php
/**
 * @pakage LTforum
 * @version 1.2 added Access Controller and User Manager
 */
/**
 * LTforum Admin panel, common for all forum-threads.
 * Requires forumName and, if authentication is absent, PIN
 */

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

$ivb=SessionRegistry::initVectorForBackend($mainPath,$templatePath,$assetsPath,null,$forumsPath);
// strict=1 required as assetsPath is modified for the export command
$asr=SessionRegistry::getInstance( 1, $ivb );

// we need forumName from input and we need paths that depend on it, so PageRegistry is created before authentication
$apr=PageRegistry::getInstance( 0,[] );
$apr->initAdmBeforeAuth(null,null, $asr, "Act", $adminTitle);

// here goes the Access Controller
//$ivb=AuthRegistry::initVectorForBackend($apr->g("forum"),$forumsPath,$templatePath,$assetsPath);
$ivb=AuthRegistry::initVector($apr->g("forum"), $forumsPath.$apr->g("forum")."/", $templatePath, $assetsPath, 1);
$aar=AuthRegistry::getInstance(1,$ivb);
$ac=new AccessController($aar);
$acRet=$ac->go($aar);// so short
//echo("Trace:".$aar->g("trace")."\n");
//echo( "Alert:".$aar->g("alert")."\n" );
if ( $acRet !== true ) exit($acRet);

// continue PageRegistry inits with database
$apr->initAdmAfterAuth($asr);

try {
  switch ( $apr->g("act") ) {
    case ("exp"):
      //print("export");
      //print_r($apr);
      AdminAct::exportHtml ($apr,$asr);
      //Act::view($apr,$asr);
      exit(0);
    case ("imp"):
      //print("export");
      AdminAct::importHtml ($apr,$asr);
      //Act::showAlert($apr,$asr,$error);
      //else Act::showAlert($apr,$asr,"Import is complete");
      //Act::view($apr,$asr);
      exit(0);
    case ("dr"):
      //print("delete");
      AdminAct::deleteRange ($apr,$asr);
      exit(0);
    case ("ea"):
      //print("edit any");
      AdminAct::editAny ($apr,$asr);
      exit(0);
    case ("ua"):
      AdminAct::updateAny ($apr,$asr);
      exit(0);
  }

  //print_r($_REQUEST);
  UserManager::init($aar->g("targetPath"),$apr->g("forum"));
  switch ( $apr->g("act") ) {
    case ("lu"):
      $apr->s("userList",implode(", ",UserManager::listUsers() ) );
      break;
    case ("la"):
      $apr->s("adminList",implode(", ",UserManager::listAdmins() ) );
      break;
    case ("uAdd"):
      $ret=UserManager::manageUser("add",$apr->g("uEntry"));
      if ($ret) {
        Act::showAlert ($apr,$asr,$ret);
        exit;
      }
      $apr->s("userList",implode(", ",UserManager::listUsers() ) );
      break;
    case ("uDel"):
      $ret=UserManager::manageUser("del",$apr->g("uEntry"));
      if ($ret) {
        Act::showAlert ($apr,$asr,$ret);
        exit;
      }
      $apr->s("userList",implode(", ",UserManager::listUsers() ) );
      break;
    case ("aAdd"):
      $ret=UserManager::manageAdmin("add",$apr->g("aUser"));
      if ($ret) {
        Act::showAlert ($apr,$asr,$ret);
        exit;
      }
      $apr->s("adminList",implode(", ",UserManager::listAdmins() ) );
      break;
    case ("aDel"):
      $ret=UserManager::manageAdmin("del",$apr->g("aUser"));
      if ($ret) {
        Act::showAlert ($apr,$asr,$ret);
        exit;
      }
      $apr->s("adminList",implode(", ",UserManager::listAdmins() ) );
      break;
    case (""):
      break;
    default ;
      Act::showAlert ($apr,$asr,"Unknown admin command:".$apr->g("act"));
  }

} catch (AccessException $e) {
  Act::showAlert ($apr,$asr,$e->getMessage());
}

include ($asr->g("templatePath")."admin.php");
exit(0);

?>