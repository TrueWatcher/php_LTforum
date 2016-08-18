<?php
/**
 * @pakage LTforum
 * @version 0.1.3 new folders structure
 */

/**
 * All code, that is not stuck into it's own file
 */


require_once ($mainPath."CardfileSqlt.php");
require_once ($mainPath."AssocArrayWrapper.php");
 
/**
 * My exception for file access errors.
 */
class AccessException extends Exception {}
/**
 * My exception for unsupported/forbidden client operations.
 */
class UsageException extends Exception {} 
 
class PageRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;// private causes access error
    
    public function load() {
      $inputKeys=array("act","page","rec","len");
      foreach ($inputKeys as $k) {
        if ( array_key_exists($k,$_REQUEST) ) $this->s($k,$_REQUEST[$k]);
        else $this->s($k,"");
      }
      if (array_key_exists('PHP_AUTH_USER',$_SERVER) ) $this->s("user",$_SERVER['PHP_AUTH_USER']);
      else $this->s("user","Creator");
      //$this->s("forum","testDb");// DEBUG!!!
    }
}
 
class SessionRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
}
  
function makeMsg($a,$t,$c="") {
    return ( array("author"=>$a,"message"=>$t,"comment"=>$c) );
}
  
// MAIN

//echo ("\r\nI'm LTforum/LTforum/LTforum.php");

// instantiate and initialize Page Registry and Session Registry

$pr=PageRegistry::getInstance( false,array("there"=>"4321") );
$pr->load();
$pr->s("forum",$forumName); // $forumName comes from index.php
if ($forumTitle) $pr->s("title",$forumTitle);
else $pr->s("title","LTforum::".$forumName);

$sr=SessionRegistry::getInstance( false,array("lang"=>"en","viewLength"=>20,"viewOverlay"=>1,"toPrintOutcome"=>1) );

$messages=new CardfileSqlt( $pr->g("forum"), true);

//$firstMsg=$messages->getOneMsg(1);
//print_r ($firstMsg);

$messages->getLimits($l,$h,$a);
$pr->s("forumLow",$l);
$pr->s("forumHigh",$h);
//$pr->s("forumTopAuthor",$a);
// the uppermost message can be edited/deleted by its author
$topIsEditable=( strcmp($a,$pr->g("user"))==0 );
if ( empty($pr->g("low")) && empty($pr->g("page")) ) $pr->s("page","-1");

if ( $pr->g("page") <0 ) $pr->s( "low",$h-$sr->g("viewLength") );
$toShow=$messages->yieldPackMsg($pr->g("low"),$sr->g("viewLength"));

include ($templatePath."roll.php");
  
?>