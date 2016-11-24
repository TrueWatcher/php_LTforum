<?php
/**
 * @pakage LTforum
 * @version 1.1 added Search command, refactored View classes
 */
/**
 * LTforum Admin panel, common for all forum-threads.
 * Requires forumName and, if authentication is absent, PIN
 */

require_once ($mainPath."CardfileSqlt.php");
require_once ($mainPath."AssocArrayWrapper.php");
require_once ($mainPath."Act.php");
require_once ($mainPath."MyExceptions.php");
require_once ($mainPath."AdminAct.php");

class PageRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;// private causes access error

    public function load() {
      $inputKeys=array("act","forum","pin","current","begin","end","length","obj","order","kb","newBegin","txt","comm","author","clear");
      foreach ($inputKeys as $k) {
        if ( array_key_exists($k,$_REQUEST) ) $this->s($k,$_REQUEST[$k]);
        else $this->s($k,"");
      }
      if (array_key_exists('PHP_AUTH_USER',$_SERVER) ) $this->s("user",$_SERVER['PHP_AUTH_USER']);
      //else $this->s("user","Creator");
    }
}

class SessionRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
}
class ViewRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
}

require_once ($mainPath."Hopper.php");
require_once ($mainPath."SessionManager.php");

// instantiate and initialize Page Registry and Session Registry
// strict=1 required as assetsPath is modified for the export command
$asr=SessionRegistry::getInstance( 1, array( "lang"=>"en","viewOverlay"=>1, "toPrintOutcome"=>1,"mainPath"=>$mainPath, "templatePath"=>$templatePath, "assetsPath"=>$assetsPath,"forumsPath"=>$forumsPath, "maxMessageBytes"=>"1200","pin"=>1 )
);

$apr=PageRegistry::getInstance( 0,array() );
$apr->load();
$apr->s("title",$adminTitle);
$targetPath=$forumsPath.$apr->g("forum")."/".$apr->g("forum");
$apr->s("targetPath",$targetPath);

/*if ( $error=AdminAct::checkThreadPin($apr,$asr) ) {
  Act::showAlert($apr,$asr,$error);
}*/
$apr->s( "title",$adminTitle." : ".$apr->g("forum") );
$apr->s( "viewLink",Act::addToQueryString($apr,"","forum","pin") );

//echo(">>".$apr->g("targetPath"));
// here goes the Session Manager
  $aar=AuthRegistry::getInstance(1, [ "realm"=>$apr->g("forum"), "targetPath"=>$forumsPath.$apr->g("forum")."/", "templatePath"=>$templatePath, "assetsPath"=>$assetsPath, "admin"=>"YES", "authName"=>"", "serverNonce"=>"",  "serverCount"=>0, "clientCount"=>0, "secret"=>"", "authMode"=>1, "minDelay"=>3, "maxDelayAuth"=>300, "maxDelayPage"=>3600, "act"=>"", "user"=>"", "ps"=>"", "cn"=>"", "responce"=>"", "plain"=>"", "pers"=>"", "alert"=>"", "controlsClass"=>"" ] );
  $sm=new SessionManager;
  $smRet=$sm->go($aar);
  echo("\r\nTrace: ".$sm->trace." ");

  //if ( $alert=$ar->g("alert") ) echo($alert);
  if($smRet===false) exit;
  //if($smRet!==true) exit($ret);// see after $pr
  // one more piece of SessionManager
  if($smRet!==true) Act::showAlert($apr,$asr,$smRet);  

try {
  $apr->s("cardfile",new CardfileSqlt($targetPath,false));
}
catch (Exception $e) {
  Act::showAlert ($apr,$asr,$e->getMessage());
}

$total=$apr->g("cardfile")->getLimits($forumBegin,$forumEnd,$a,true);
$apr->s("forumBegin",$forumBegin);
$apr->s("forumEnd",$forumEnd);
$missing=$forumEnd-$forumBegin+1-$total;
if ($missing) Act::showAlert($apr,$asr,"There are ".$missing." missing messages");

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
} catch (AccessException $e) {
  Act::showAlert ($apr,$asr,$e->getMessage());
}


include ($asr->g("templatePath")."admin.php");
exit(0);

?>