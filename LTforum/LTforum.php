<?php
/**
 * @pakage LTforum
 * @version 0.1.4 (new folders structure) view subcontroller
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
 
class PageRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;// private causes access error
    
    public function load() {
      $inputKeys=array("act","begin","end","length");
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

class ViewRegistry extends SingletAssocArrayWrapper {
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

$sr=SessionRegistry::getInstance( true, array( "lang"=>"en","viewDefaultLength"=>20,"viewOverlay"=>1,"toPrintOutcome"=>1
) );


// processing act=view --------------------------------------------
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
"forumBegin"=>$fb, "forumEnd"=>$fe, "topIsEditable"=>$topIsEditable, "title"=>$pr->g("title"), "overlay"=>$sr->g("viewOverlay"), "length"=>"", "begin"=>"", "end"=>"", "base"=>"", "pageCurrent"=>"", "pageEnd"=>"", "msgGenerator"=>""
) );

if ( empty($pr->g("length")) || $pr->g("length")<0 ) $l=$sr->g("viewDefaultLength");
else $l=$pr->g("length");
$rr->s("length",$l);

if ( empty($pr->g("begin")) && empty($pr->g("end")) ) {
  $e=$fe;
  $rr->s("end",$fe);
  $rr->s("base","end");
  $b = $fe - $l + 1;
  if ( $b < $fb ) $b=$fb;
  $rr->s("begin",$b);    
}
else {
  if ( empty($pr->g("begin")) ) {
    $rr->s("base","end");
    $e=$pr->g("end");
    if ( $e < 0 || $e > $fe ) $e=$fe;
    $rr->s("end",$e);
    $b = $e - $l + 1;
    if ( $b < $fb ) $b=$fb;
    $rr->s("begin",$b);  
  }
  else {
    $rr->s("base","begin");
    $b=$pr->g("begin");
    if ( $b < $fb ) $b=$fb;
    $rr->s("begin",$b);    
    $e = $b + $l - 1;
    if ( $e > $fe ) $e=$fe;
    $rr->s("end",$e);
  }
}

$o=$rr->g("overlay");
$lastPage_b=(int)(($fe-$fb)/($l-$o)+1);
$rr->s("pageEnd",$lastPage_b);
$currentPage_b=(int)ceil(($b-$fb)/($l-$o)+1);
$rr->s("pageCurrent",$currentPage_b);

$toShow=$messages->yieldPackMsg($rr->g("begin"),$rr->g("end"));
$rr->s("msgGenerator",$toShow);

$rr->dump();
include ($templatePath."roll.php");
  
?>