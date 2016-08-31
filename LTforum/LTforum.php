<?php
/**
 * @pakage LTforum
 * @version 1.0 experimental deployment
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
      $inputKeys=array("act","begin","end","length","user","txt","comm","snap","del");
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

// MAIN

//echo ("\r\nI'm LTforum/LTforum/LTforum.php");

// instantiate and initialize Page Registry and Session Registry
$sr=SessionRegistry::getInstance( 2, array( "lang"=>"en", "viewDefaultLength"=>20, "viewOverlay"=>1, "toPrintOutcome"=>1,"mainPath"=>$mainPath, "templatePath"=>$templatePath, "assetsPath"=>$assetsPath, "maxMessageBytes"=>"1200", "forum"=>$forumName)
);

$pr=PageRegistry::getInstance( 0,array() );
$pr->load();
//$pr->s("forum",$forumName); // $forumName comes from index.php
if ($forumTitle) $pr->s("title",$forumTitle);
else $pr->s("title","LTforum::".$forumName);
$pr->s( "viewLink",Act::addToQueryString($pr,"","length","user") );//

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

$action=$pr->g("act");
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
  default:
    Act::view($pr,$sr);
}
  
?>