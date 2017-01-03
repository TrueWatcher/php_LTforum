<?php
/**
 * @pakage LTforum
 * @version 1.2 added Access Controller and User Manager
 */

/**
 * Controller upper part: Initializations, AccessController call and Command resolver.
 * Commands are in Act.php
 */

require_once ($mainPath."CardfileSqlt.php");
require_once ($mainPath."AssocArrayWrapper.php");
require_once ($mainPath."Act.php");
require_once ($mainPath."MyExceptions.php");
require_once ($mainPath."AccessController.php");
require_once ($mainPath."Applicant.php");

class PageRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;// private causes access error

    public function readInput() {
      $inputKeys=[ "act", "current", "begin", "end", "length", "user", "txt", "comm", "snap", "del", "query", "searchLength", "order" ];
      foreach ($inputKeys as $k) {
        if ( array_key_exists($k,$_REQUEST) ) $this->s($k,$_REQUEST[$k]);
        else $this->s($k,"");
      }
      if (array_key_exists('PHP_AUTH_USER',$_SERVER) ) $this->s("user",$_SERVER['PHP_AUTH_USER']);// will be overwritten by self::readSession
    }

    public function readSession() {
      $keys=["authName"=>"user", "current"=>"current", "updated"=>"updated" ];
      if ( !$_SESSION ) throw new UsageException("PageRegistry: no active session found !");//return(false);
      $i=0;
      foreach($keys as $sessKey=>$regKey) {
        if ( array_key_exists($sessKey,$_SESSION) && $_SESSION[$sessKey] ) {
          $this->s($regKey,$_SESSION[$sessKey]);
          $i++;
        }
      }
      return($i);
    }

    /*public function exportToSession() {
      $keys=[ "current"=>"current" ];//"authName"=>"user",
      $regKey="current";
      $sessKey=$keys[$regKey];
      $_SESSION[$sessKey] = $this->g($regKey);
    }*/
}

class SessionRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
}

class ViewRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
}

// MAIN

//echo ("\r\nI'm LTforum/LTforum/LTforum.php");

// instantiate and initialize the Session Registry
$sr=SessionRegistry::getInstance( 2, array( "lang"=>"en", "viewDefaultLength"=>10, "viewOverlay"=>1, "toPrintOutcome"=>0,"mainPath"=>$mainPath, "templatePath"=>$templatePath, "assetsPath"=>$assetsPath, "maxMessageBytes"=>"1200", "narrowScreen"=>640, "forum"=>$forumName)
);

// here goes the Access Controller
$ar=AuthRegistry::getInstance(1, ["realm"=>$forumName, "targetPath"=>"", "templatePath"=>$templatePath, "assetsPath"=>$assetsPath, "isAdminArea"=>0, "authName"=>"", "serverNonce"=>"",  "serverCount"=>0, "clientCount"=>0, "secret"=>"", "authMode"=>1, "minDelay"=>5, "maxDelayAuth"=>5*60, "maxDelayPage"=>60*60, "maxTimeoutGcCookie"=>5*24*3600, "minRegenerateCookie"=>1*24*3600, "reg"=>"", "user"=>"", "ps"=>"", "cn"=>"", "response"=>"", "plain"=>"", "pers"=>"", "alert"=>"", "controlsClass"=>"" ] );
$ac=new AccessController;
$acRet=$ac->go($ar);
//if ( $alert=$ar->g("alert") ) echo($alert);
if($acRet!==true) exit($acRet);

//  instantiate and initialize the Page Registry
$pr=PageRegistry::getInstance( 0,array() );
$pr->readInput();
$pr->readSession();
if ($forumTitle) $pr->s("title",$forumTitle);
else $pr->s("title","LTforum::".$forumName);
$pr->s( "viewLink",Act::addToQueryString($pr,"end=-1","length")."#footer" );//

try {
  $pr->s("cardfile",new CardfileSqlt($forumName,true));
}
catch (Exception $e) {
  Act::showAlert ($pr,$sr,$e->getMessage());
}

$action=$pr->g("act");
if ( empty($action) && empty($pr->g("begin")) && empty($pr->g("end")) )  Act::redirectToView($pr);

switch ($action) {
  case "new":
    Act::newMessage($pr,$sr);
    break;
  case "el":
    Act::editLast($pr,$sr);
    break;
  case "upd":
    Act::updateLast($pr,$sr);
    break;
  case "add":
    Act::add($pr,$sr);
    break;
  case "search":
    Act::search($pr,$sr);
    break;
  default:
    Act::view($pr,$sr);
}

?>