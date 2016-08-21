<?php
/**
 * @pakage LTforum
 * @version 0.2.0 (add,edit,update) (improvements) cleaning
 */

/**
 * Helper class for Controller
 */
class Act {

  public static function newMessage(PageRegistry $pr,SessionRegistry $sr) {
    include ($sr->g("templatePath")."new.php");
    exit(0);
  }

  public static function editLast(PageRegistry $pr,SessionRegistry $sr) {
    // checkings
    //$pr->g("cardfile")=new CardfileSqlt( $pr->g("forum"), false);
    $lastMsg=$pr->g("cardfile")->getLastMsg();
    if( $lastMsg["author"]!=$pr->g("user") ) self::showAlert ($pr,$sr,"Usernames are different!");  
    if( $lastMsg["id"]!=$pr->g("end") ) self::showAlert ($pr,$sr,"Sorry, something is wrong with message number. Looks like it's not the latest one now.");
    // transfer message and comment
    $pr->s("txt",$lastMsg["message"]);
    $pr->s("comm",$lastMsg["comment"]);
    // show form
    include ($sr->g("templatePath")."edit.php");
    exit(0);
  }
  
  public static function updateLast(PageRegistry $pr,SessionRegistry $sr) {
    $pr->dump();
    // check user -- same as act=el
    $lastMsg=$pr->g("cardfile")->getLastMsg();
    if( $lastMsg["author"]!=$pr->g("user") ) self::showAlert ($pr,$sr,"Usernames are different!");  
    if( $lastMsg["id"]!=$pr->g("end") ) self::showAlert ($pr,$sr,"Sorry, something is wrong with message number. Looks like it's not the latest one now.");
  
    if ( !empty($pr->g("del")) ) {
      // simply delete
      $pr->g("cardfile")->deletePackMsg($pr->g("end"),$pr->g("end"));
      if ( empty($pr->g("snap")) ) self::showAlert ($pr,$sr,"Message ".$pr->g("end")." has been deleted by ".$pr->g("user"));
      self::redirectToView ($pr);
    }
    // check input strings 
    $txt=$pr->g("txt");
    if( empty($txt) ) self::showAlert ($pr,$sr,"Please, leave something in the Message field");
    $txt=self::prepareInputText($txt,$sr);
    $comm=$pr->g("comm");
    $comm=self::prepareInputText($comm,$sr);
  
    $lastMsg["message"]=$txt;
    $lastMsg["comment"]=$comm;
    $lastMsg["time"]=$lastMsg["date"]="";// current date and time will be set
    $pr->g("cardfile")->addMsg($lastMsg,true);// true for overwrite
  
    if ( empty($pr->g("snap")) ) self::showAlert ($pr,$sr,"Message ".$pr->g("end")." has been updated by ".$pr->g("user"));
    self::redirectToView ($pr);  
  } 
  
  public static function add(PageRegistry $pr,SessionRegistry $sr) {
    $pr->dump();
    $user=$pr->g("user");
    $txt=$pr->g("txt");
    if( empty($user) ) self::showAlert ($pr,$sr,"Please, introduce yourself");
    if( empty($txt) ) self::showAlert ($pr,$sr,"Please, write some message");
    if ( charsInString($user,"<>&\"':;()") ) {
      $pr->s("user","");
      self::showAlert ($pr,$sr,"Username ".htmlspecialchars($user)." contains forbidden symbols");
    }
    
    if( strlen($txt) > 60 ) $txt=substr($txt,0,60);
    $txt=self::prepareInputText($txt,$sr);

    $newMsg=self::makeMsg($user,$txt);
    $pr->g("cardfile")->addMsg($newMsg);
  
    if ( empty($pr->g("snap")) ) self::showAlert ($pr,$sr,"Message was added successfully");
    self::redirectToView ($pr);  
  }
  
  public static function view(PageRegistry $pr,SessionRegistry $sr) { 
    try {
      $pr->g("cardfile")->getLimits($fb,$fe,$a);
      $o=$sr->g("viewOverlay");
      
      if ( empty($pr->g("length")) || $pr->g("length")<0 ) $l=$sr->g("viewDefaultLength");
      else $l=$pr->g("length");
     
      if ( !empty($pr->g("begin")) && !empty($pr->g("end")) ) throw new UsageException ("You cannot send both \"begin\" and \"end\"; use length=* to see all messages.");

      if ( $l=="*" ) { 
        $bs="end";
        $b=$fb;
        $e=$fe;
        $l=$fe-$fb+1+10;
      }
      else if ( empty($pr->g("begin")) && empty($pr->g("end")) ) {
        $bs="end";  
        $e=$fe;
        $b = $fe - $l + 1;
        if ( $b < $fb ) $b=$fb; 
      }
      else {
        if ( !empty($pr->g("end")) ) {
          $bs="end";
          $e=$pr->g("end");
          if ( $e <= 0 || $e > $fe ) $e=$fe;
          if ( $e < $fb ) $e=$fb;
          $b = $e - $l + 1;
          if ( $b < $fb ) $b=$fb; 
        }
        else { // begin is set
          $bs="begin"; 
          $b=$pr->g("begin");
          if ( $b < $fb ) $b=$fb;
          if ( $b > $fe ) $b=$fe;  
          $e = $b + $l - 1;
          if ( $e > $fe ) $e=$fe;
        }
      }
      
      if ( $bs=="begin" ) {
        // count pages numbers from end message numbers
        $cp=(int)ceil(($e-$fb-$o+1)/($l-$o));
        $lp=(int)ceil(($fe-$fb-$o+1)/($l-$o));
      }
      else if ( $bs=="end" ) {
        // count from begin message numbers
        $cp=(int)ceil(($b-$fb)/($l-$o)+1);
        $lp=(int)ceil(($fe-$fb)/($l-$o));
      }
      else throw new UsageException ("Illegal value at \"base\" key :".$bs.'!');

      $toShow=$pr->g("cardfile")->yieldPackMsg($b,$e);
      
      $rr=ViewRegistry::getInstance( true, array( "forumBegin"=>$fb, "forumEnd"=>$fe, "overlay"=>$o, "length"=>$l, "begin"=>$b, "end"=>$e, "base"=>$bs, "pageCurrent"=>$cp, "pageEnd"=>$lp, "msgGenerator"=>$toShow
      ) );
      //$rr->s("no_such_key",0);// check catch UsageException

      //$rr->dump();
      include ($sr->g("templatePath")."roll.php");
      exit(0);
    }
    catch (Exception $e) {
      self::showAlert ($pr,$sr,$e->getMessage()); 
    }
  }// end view
  
  // UTILITIES ----------------------------------
  
  public static function showAlert (PageRegistry $pr, SessionRegistry $sr, $alertMessage) {
    $pr->s( "alert",$alertMessage );
    include ($sr->g("templatePath")."alert.php");
    exit(0);
  }
  
  public static function prepareInputText($txt, SessionRegistry $sr) {
    if( strlen($txt) > $sr->g("maxMessageBytes") ) $txt=substr($txt,0,$sr->g("maxMessageBytes"));  
    require_once ($sr->g("mainPath")."MaskTags.php");
    $keep_tags=array (
      'bbc' => array ("[s]","[/s]","[i]","[/i]","[b]","[/b]","[u]","[/u]"),
      'empty' => array ("br","br ","br/"),
      'markup' => array ("center","em","del","s","u","i","b","a ")
    );
    $txt=MaskTags::mask_tags($txt,$keep_tags["bbc"],$keep_tags["empty"],$keep_tags["markup"]);
    return($txt);
  }
  
  public static function makeMsg($a,$t,$c="") {
    return ( array("author"=>$a,"message"=>$t,"comment"=>$c) );
  }
  
  public static function charsInString($what,$charsString) {
    if (strtok($what,$charsString)!==$what) return (true);
  }
  
  public static function myAbsoluteUri () {
    $url = 'http://';
    if ( $_SERVER['HTTPS'] ) $url = 'https://';
    $url .= $_SERVER['HTTP_HOST'];            // Get the server
    $url .= rtrim(dirname($_SERVER['PHP_SELF']), '/\\'); // Get the current directory
    return ($url);
  }
  
  public static function redirectToView (PageRegistry $pr) {
    $url=self::myAbsoluteUri();
    $url.="/?length=".$pr->g("length")."&user=".$pr->g("user");
    //echo $url;
    header("Location: ".$url);
    exit(0);
  }
  
  
}// end Act
?>