<?php
/**
 * @pakage LTforum
 * @version 0.1.6 (new folders structure) (view subcontroller) (add,edit,update) improvements
 */

/**
 * All code, that is not stuck into its own file
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
/**
 * My exception for exception in normal operations, like border situations.
 */
class OperationalException extends Exception {}
 
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
      //$this->s("forum","testDb");// DEBUG!!!
    }
}
 
class SessionRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
}

class ViewRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
}

function makeMsg($a,$t,$c="") {
    return ( array("author"=>$a,"message"=>$t,"comment"=>$c) );
}

function showAlert (PageRegistry $pr, SessionRegistry $sr, $alertMessage) {
  $pr->s( "alert",$alertMessage );
  include ($sr->g("templatePath")."alert.php");
  exit(0);
}

function charsInString($what,$charsString) {
  if (strtok($what,$charsString)!==$what) return (true);
}

function prepareInputText($txt, SessionRegistry $sr) {
  if( strlen($txt) > $sr->g("maxMessageBytes") ) $txt=substr($txt,0,$sr->g("maxMessageBytes"));  
  require_once ($sr->g("mainPath")."mask_tags.php");
  $keep_tags=array (
    'bbc' => array ("[s]","[/s]","[i]","[/i]","[b]","[/b]","[u]","[/u]"),
    'empty' => array ("br","br ","br/"),
    'markup' => array ("center","em","del","s","u","i","b","a ")
  );
  $txt=mask_tags($txt,$keep_tags["bbc"],$keep_tags["empty"],$keep_tags["markup"]);
  return($txt);
}

function redirectToView (PageRegistry $pr) {
  $url = 'http://';
  if ( $_SERVER['HTTPS'] ) $url = 'https://';
  $url .= $_SERVER['HTTP_HOST'];            // Get the server
  $url .= rtrim(dirname($_SERVER['PHP_SELF']), '/\\'); // Get the current directory
  $url .= "/?length=".$pr->g("length")."&user=".$pr->g("user");
  //echo $url;
  header("Location: ".$url);
  exit(0);
}

// MAIN

//echo ("\r\nI'm LTforum/LTforum/LTforum.php");

// instantiate and initialize Page Registry and Session Registry

$pr=PageRegistry::getInstance( false,array() );
$pr->load();
$pr->s("forum",$forumName); // $forumName comes from index.php
if ($forumTitle) $pr->s("title",$forumTitle);
else $pr->s("title","LTforum::".$forumName);

$sr=SessionRegistry::getInstance( true, array( "lang"=>"en", "viewDefaultLength"=>20, "viewOverlay"=>1, "toPrintOutcome"=>1,"mainPath"=>$mainPath, "templatePath"=>$templatePath, "assetsPath"=>$assetsPath, "maxMessageBytes"=>"1200")
);

// processing act=new --------------------------------------------
if ( $pr->g("act")=="new" ) {
  include ($sr->g("templatePath")."new.php");
  exit(0);
}

// processing act=el (edit last) ----------------------------------
if ( $pr->g("act")=="el" ) {
  // checkings
  $messages=new CardfileSqlt( $pr->g("forum"), false);
  $lastMsg=$messages->getLastMsg();
  if( $lastMsg["author"]!=$pr->g("user") ) showAlert ($pr,$sr,"Usernames are different!");  
  if( $lastMsg["id"]!=$pr->g("end") ) showAlert ($pr,$sr,"Sorry, something is wrong with message number. Looks like it's not the latest one now.");
  // transfer message and comment
  $pr->s("txt",$lastMsg["message"]);
  $pr->s("comm",$lastMsg["comment"]);
  // show form
  include ($sr->g("templatePath")."edit.php");
  exit(0);
}

// processing act=upd
if ( $pr->g("act")=="upd" ) {
  $pr->dump();
  // checkings -- same as act=el
  $messages=new CardfileSqlt( $pr->g("forum"), false);
  $lastMsg=$messages->getLastMsg();
  if( $lastMsg["author"]!=$pr->g("user") ) showAlert ($pr,$sr,"Usernames are different!");  
  if( $lastMsg["id"]!=$pr->g("end") ) showAlert ($pr,$sr,"Sorry, something is wrong with message number. Looks like it's not the latest one now.");
  
  if ( !empty($pr->g("del")) ) {
    // simply delete
    $messages->deletePackMsg($pr->g("end"),$pr->g("end"));
    if ( empty($pr->g("snap")) ) showAlert ($pr,$sr,"Message ".$pr->g("end")." has been deleted by ".$pr->g("user"));
    redirectToView ($pr);
    //showAlert ($pr,$sr,"Message ".$pr->g("end")." has been deleted by ".$pr->g("user"));
    // stub, from here we should go viewing
  }
  
  $txt=$pr->g("txt");
  if( empty($txt) ) showAlert ($pr,$sr,"Please, leave something in the Message field");
  $txt=prepareInputText($txt,$sr);
  $comm=$pr->g("comm");
  $comm=prepareInputText($comm,$sr);
  
  $lastMsg["message"]=$txt;
  $lastMsg["comment"]=$comm;
  $lastMsg["time"]=$lastMsg["date"]="";// current date and time will be set

  $messages->addMsg($lastMsg,true);// overwrite
  // if it goes to view, CardfileSqlt can be instantiated again 
  // see CardfileSqlt line 17
  
  if ( empty($pr->g("snap")) ) showAlert ($pr,$sr,"Message ".$pr->g("end")." has been updated by ".$pr->g("user"));
  // goes on to viewer
  redirectToView ($pr);
}

// processing act=add --------------------------------------------
if ( $pr->g("act")=="add" ) {
  $pr->dump();
  $user=$pr->g("user");
  $txt=$pr->g("txt");
  if( empty($user) ) showAlert ($pr,$sr,"Please, introduce yourself");
  if( empty($txt) ) showAlert ($pr,$sr,"Please, write some message");
  if ( charsInString($user,"<>&\"':;()") ) {
    $pr->s("user","");
    showAlert ($pr,$sr,"Username ".htmlspecialchars($user)." contains forbidden symbols");
  }
  if( strlen($txt) > 60 ) $txt=substr($txt,0,60);
  
  $txt=prepareInputText($txt,$sr);

  $newMsg=makeMsg($user,$txt);
  $messages=new CardfileSqlt( $pr->g("forum"), false);
  $messages->addMsg($newMsg);
  // if it goes to view, CardfileSqlt can be instantiated again 
  // see CardfileSqlt line 17
  
  if ( empty($pr->g("snap")) ) showAlert ($pr,$sr,"Message was added successfully");
  redirectToView ($pr);
}

// processing act=view --------------------------------------------
try {

$messages=new CardfileSqlt( $pr->g("forum"), true);

//$firstMsg=$messages->getOneMsg(1);
//print_r ($firstMsg);
/*for ($j=2;$j<=100;$j++) {
  $m=makeMsg("Creator","This is message number ".$j);
  $messages->addMsg($m);
}*/
//$messages->deletePackMsg(1,15);


$messages->getLimits($fb,$fe,$a);

// the uppermost message can be edited/deleted by its author
$topIsEditable=( strcmp($a,$pr->g("user"))==0 );

$rr=ViewRegistry::getInstance( true, array( 
"forumBegin"=>$fb, "forumEnd"=>$fe, "title"=>$pr->g("title"), "overlay"=>$sr->g("viewOverlay"), "length"=>"", "begin"=>"", "end"=>"", "base"=>"", "pageCurrent"=>"", "pageEnd"=>"", "msgGenerator"=>""
) );

if ( empty($pr->g("length")) || $pr->g("length")<0 ) $l=$sr->g("viewDefaultLength");
else $l=$pr->g("length");
$rr->s("length",$l);

if ( !empty($pr->g("begin")) && !empty($pr->g("end")) ) throw new UsageException ("You cannot send both \"begin\" and \"end\"; use length=* to see all messages.");

if ( $l=="*" ) { //print ("Got length=* ");
  $rr->s("end",$fe);
  $rr->s("base","begin");
  $rr->s("begin",$fb);
  
  $b=$fb;
  $e=$fe;
  $l=$fe-$fb+1+10;// exact size conflicts with pagePanel
  $rr->s("length",$l);
}
else if ( empty($pr->g("begin")) && empty($pr->g("end")) ) {
  $e=$fe;
  $rr->s("end",$fe);
  $rr->s("base","end");
  $b = $fe - $l + 1;
  if ( $b < $fb ) $b=$fb;
  $rr->s("begin",$b);    
}
else {
  if ( !empty($pr->g("end")) ) {
    $rr->s("base","end");
    $e=$pr->g("end");
    if ( $e <= 0 || $e > $fe ) $e=$fe;
    $rr->s("end",$e);
    $b = $e - $l + 1;
    if ( $b < $fb ) $b=$fb;
    $rr->s("begin",$b);  
  }
  else { // begin is set
    $rr->s("base","begin");
    $b=$pr->g("begin");
    if ( $b < $fb ) $b=$fb;
    if ( $b > $fe ) $b=$fe;
    $rr->s("begin",$b);    
    $e = $b + $l - 1;
    if ( $e > $fe ) $e=$fe;
    $rr->s("end",$e);
  }
}

$o=$rr->g("overlay");
$lastPage_b=(int)ceil(($fe-$fb)/($l-$o));// +1
$lastPage_e=(int)ceil(($fe-$fb-$o+1)/($l-$o));
$currentPage_b=(int)ceil(($b-$fb)/($l-$o)+1);
$currentPage_e=(int)ceil(($e-$fb-$o+1)/($l-$o));
if ( $rr->g("base")=="begin" ) {
  $rr->s("pageEnd",$lastPage_e);
  $rr->s("pageCurrent",$currentPage_e);
}
else if ( $rr->g("base")=="end" ) {
  $rr->s("pageEnd",$lastPage_b);
  $rr->s("pageCurrent",$currentPage_b);
}
else throw new UsageException ("Illegal value at \"base\" key :".$bs.'!');

$toShow=$messages->yieldPackMsg($rr->g("begin"),$rr->g("end"));
$rr->s("msgGenerator",$toShow);

//$rr->s("no_such_key",0);// check catch UsageException

$rr->dump();

include ($sr->g("templatePath")."roll.php");
exit(0);
}
catch (AccessException $ae) {
  $pr->s( "alert",$ae->getMessage() );
  include ($sr->g("templatePath")."alert.php");
}
catch (UsageException $ue) {
  $pr->s( "alert",$ue->getMessage() );
  include ($sr->g("templatePath")."alert.php");
}

  
?>