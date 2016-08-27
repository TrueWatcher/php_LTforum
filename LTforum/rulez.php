<?php
/**
 * @pakage LTforum
 * @version 0.2.0 (functional viewAddEditDelete) refactored and cleaned
 */
/**
 * LTforum Admin panel, common for all forum-threads.
 * Requires forumName and, if authentication is absent, PIN
 */

//echo ("I'm LTforum/demo/index.php"); 
 
//$forumName="test";// canonical forum name
$adminTitle="LTforum administration panel";// page title
$mainPath="";// relative to here
$templatePath="templates/"; // relative to main LTforum folder
$assetsPath="../assets/"; // relative to main LTforum folder

//require_once ($mainPath."LTforum.php");
require_once ($mainPath."CardfileSqlt.php");
require_once ($mainPath."AssocArrayWrapper.php");
require_once ($mainPath."Act.php");
require_once ($mainPath."MyExceptions.php");
require_once ($mainPath."AdminAct.php");

class PageRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;// private causes access error
    
    public function load() {
      $inputKeys=array("adm","forum","pin","begin","end","length","obj","order","txt","comm","del");
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

// instantiate and initialize Page Registry and Session Registry
$asr=SessionRegistry::getInstance( 2, array( "lang"=>"en","viewOverlay"=>1, "toPrintOutcome"=>1,"mainPath"=>$mainPath, "templatePath"=>$templatePath, "assetsPath"=>$assetsPath, "maxMessageBytes"=>"1200","pin"=>1 )
);

$apr=PageRegistry::getInstance( 0,array() );
$apr->load();
$apr->s("title",$adminTitle);
$targetPath="../".$apr->g("forum")."/".$apr->g("forum");
$apr->s("targetPath",$targetPath);

if ( $error=AdminAct::checkThreadPin($apr,$asr) ) {
  Act::showAlert($apr,$asr,$error);
}
$apr->s( "title",$adminTitle." : ".$apr->g("forum") );
$apr->s( "viewLink",Act::addToQueryString($apr,"","forum","pin") );

try {
  $apr->s("cardfile",new CardfileSqlt($targetPath,false));
}
catch (Exception $e) {
  Act::showAlert ($apr,$asr,$e->getMessage()); 
}

switch ( $apr->g("adm") ) {
  case ("exp"):
    //print("export");
    AdminAct::exportHtml ($apr,$asr);
    //Act::view($apr,$asr);
    exit(0);
  case ("imp"):
    //print("export");
    if ( $error=AdminAct::importHtml ($apr,$asr) ) Act::showAlert($apr,$asr,$error);
    else Act::showAlert($apr,$asr,"Going on...");
    //Act::view($apr,$asr);
    exit(0);  
}

$apr->g("cardfile")->getLimits($forumBegin,$forumEnd,$a);
$apr->s("begin",$forumBegin);
$apr->s("end",$forumEnd);
include ($asr->g("templatePath")."admin.php");
exit(0);

?>