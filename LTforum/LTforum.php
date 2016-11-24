<?php
/**
 * @pakage LTforum
 * @version 1.2 added SessionManager
 */

/**
 * Controller upper part: Initialization and Command resolver.
 * Commands are in Act.php
 */

require_once ($mainPath."CardfileSqlt.php");
require_once ($mainPath."AssocArrayWrapper.php");
require_once ($mainPath."Act.php");
require_once ($mainPath."MyExceptions.php");

// Classes and function, which have not found their own files


class PageRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;// private causes access error

    public function load() {
      $inputKeys=array("act","current","begin","end","length","user","txt","comm","snap","del","query","searchLength","order");
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

// MAIN

//echo ("\r\nI'm LTforum/LTforum/LTforum.php");

// instantiate and initialize Page Registry and Session Registry
$sr=SessionRegistry::getInstance( 2, array( "lang"=>"en", "viewDefaultLength"=>20, "viewOverlay"=>1, "toPrintOutcome"=>0,"mainPath"=>$mainPath, "templatePath"=>$templatePath, "assetsPath"=>$assetsPath, "maxMessageBytes"=>"1200", "narrowScreen"=>640, "forum"=>$forumName)
);

// here goes the Session Manager
  $ar=AuthRegistry::getInstance(1, ["realm"=>$forumName, "targetPath"=>"", "templatePath"=>$templatePath, "assetsPath"=>$assetsPath, "admin"=>0, "authName"=>"", "serverNonce"=>"",  "serverCount"=>0, "clientCount"=>0, "secret"=>"", "authMode"=>1, "minDelay"=>3, "maxDelayAuth"=>300, "maxDelayPage"=>3600, "act"=>"", "user"=>"", "ps"=>"", "cn"=>"", "responce"=>"", "plain"=>"", "pers"=>"", "alert"=>"", "controlsClass"=>"" ] );
  $sm=new SessionManager;
  $smRet=$sm->go($ar);
  //echo("\r\nTrace: ".$sm->trace." ");

  //if ( $alert=$ar->g("alert") ) echo($alert);
  if($smRet===false) exit;
  //if($smRet!==true) exit($ret);// see after $pr

$pr=PageRegistry::getInstance( 0,array() );
$pr->load();
//$pr->s("forum",$forumName); // $forumName comes from index.php
if ($forumTitle) $pr->s("title",$forumTitle);
else $pr->s("title","LTforum::".$forumName);
$pr->s( "viewLink",Act::addToQueryString($pr,"end=-1","length","user")."#footer" );//
//print ($pr->g( "viewLink"));
//exit(0);

if($smRet!==true) Act::showAlert($pr,$sr,$smRet);

try {
  $pr->s("cardfile",new CardfileSqlt($forumName,true));
}
catch (Exception $e) {
  Act::showAlert ($pr,$sr,$e->getMessage());
}

// some testing code
//$messages=new CardfileSqlt( $pr->g("forum"), true);

//$firstMsg=$pr->g("cardfile")->getOneMsg(1);
//print_r ($firstMsg);
/*for ($j=2;$j<=100;$j++) {
  $m = Act::makeMsg("Creator","This is message number ".$j);
  $pr->g("cardfile")->addMsg($m);
}*/
//$pr->g("cardfile")->deletePackMsg(1,15);
/*$search=[" ак ","http","-IT"];//,"dvd"];"AR",
$order="";//"desc";
$limit=10;
$results="";
$toShow=$pr->g("cardfile")->yieldSearchResults($search,$order,$limit);
foreach($toShow as $i=>$res) {
  $results.="<hr />".implode(":",$res)."<hr />";
  //$results.= ("!".strpos(implode("  ",$res),$search[1]) );
  //if(strpos(implode("  ",$res),$search[1])===false) print ("<!");
}
print($results);
exit();*/

$action=$pr->g("act");
if ( empty($action) && empty($pr->g("begin")) && empty($pr->g("end")) ) Act::redirectToView($pr);
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