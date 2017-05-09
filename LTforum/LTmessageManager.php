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

$ret=AdminAct::go($apr,$asr);
if ($ret===false) $ret=UserManager::go($aar,$apr,$asr);

if ($ret===false ) {
  if ( $apr->g("act") ) {
    $ret=AdminAct::showAlert ("Unknown admin command:".$apr->g("act"));
  }
  else {
    $ret=ViewRegistry::getInstance(2,[
      "alert"=>"", "requireFiles"=>null,"includeTemplate"=>"admin.php",
      "userList"=>"", "adminList"=>""
    ]);
  }
}

viewWrapper($asr,$apr,$ret);

/**
 * Allows for decoupling the View from the Controller.
 * First checks if redirect is demanded by ViewRegistry::redirectUri.
 * Then includes template classes as is stated in ViewRegistry::requireFiles,
 * and includes template itself as stated in ViewRegistry::includeTemplate
 * Makes shure SessionRegistry and PageRegistry instances are $sr and $pr as required by templates
 * @return void
 */
function viewWrapper(SessionRegistry $sr,PageRegistry $pr,$vr=null) {
  //var_dump($vr);
  if (is_object($vr)) {
    if ( $vr->checkNotEmpty("redirectUri")) {
      header("Location: ".$vr->g("redirectUri"));
      exit(0);
    }
    if ($vr->checkNotEmpty("requireFiles")) {
      $files=explode(",",$vr->g("requireFiles"));
      foreach($files as $classFile) {
        require_once($sr->g("templatePath").$classFile);
      }
    }
    if ($vr->checkNotEmpty("includeTemplate")) {
      include ($sr->g("templatePath").$vr->g("includeTemplate"));
    }
    else {
      throw new UsageException("Empty ViewRegistry::includeTemplate");
    }
  }
  else {
    var_dump($vr);
    throw new UsageException ("Non-object 3rd argument");
  }
}


?>