<?php
/**
 * @package LTforum
 * @version 1.4 added ini files
 */

/**
 * Helper class for The Frontend Controller (LTforum.php).
 */
class Act {

  /**
   * Resolves the command and calls the appropriate method.
   * Single entry point into frontend command processing.
   * @return object ViewRegistry
   */
  public static function go(SessionRegistry $sr,PageRegistry $pr) {
    $action=$pr->g("act");
    switch ($action) {
    case "new":
      return(self::newMessage($pr,$sr));
    case "el":
      return(self::editLast($pr,$sr));
    case "upd":
      return(self::updateLast($pr,$sr));
    case "add":
      return(self::add($pr,$sr));
    case "search":
      return(self::search($pr,$sr));
    default:
      return(self::view($pr,$sr));
    }    
  }

  /**
   * Displays form to write a new message.
   * @return object ViewRegistry
   */
   public static function newMessage(PageRegistry $pr,SessionRegistry $sr) {
    $vr=ViewRegistry::getInstance( 2, [ 
      "id"=>"", "message"=>"",  "controlsClass"=>"NewElements",
      "requireFiles"=>"FormElements.php,SubFormElements.php", "includeTemplate"=>"form.php"
    ] );
    return $vr;
  }

  /**
   * Processes form, presented by Act::newMessage.
   * @return object ViewRegistry
   */
  public static function add(PageRegistry $pr,SessionRegistry $sr) {
    //$pr->dump();
    $user=$pr->g("user");
    $txt=$pr->g("txt");
    $pr->s( "formLink",self::addToQueryString($pr,"act=new","length","user") );
    if( empty($user) ) {
      return(self::showAlert ("Please, introduce yourself"));
    }
    if( empty($txt) ) {
      return(self::showAlert ("Please, write some message"));
    }
    if ( self::charsInString($user,"<>&\"':;()") ) {
      $pr->s("user","");
      return(self::showAlert (["Username %s contains forbidden symbols",htmlspecialchars($user)]));
    }

    if( strlen($user) > 60 ) $user=substr($user,0,60);
    $txt=self::prepareInputText($txt,$sr);

    $newMsg=self::makeMsg($user,$txt);
    $pr->g("cardfile")->addMsg($newMsg);

    if ( empty($pr->g("snap")) ) self::showAlert (["Message from %s has been added successfully",$user]);
    return(self::redirectToView ($pr));
  }

  /**
   * Displays form to edit the last message, if its author is the current user.
   * Stores that message's Id in SESSION to keep track
   * @return object ViewRegistry
   */
  public static function editLast(PageRegistry $pr,SessionRegistry $sr) {
    // checkings
    $lastMsg=$pr->g("cardfile")->getLastMsg();
    if( $lastMsg["author"] != $pr->g("user") ) {
      return(self::showAlert ("Editing is denied: user names are different !"));
    }
    // $pr::"current" comes from an Edit link
    if( $lastMsg["id"] != $pr->g("current") ) {
      return(self::showAlert (["Sorry, something is wrong with the message number. Looks like it's not the latest one now: %s/%s.", $lastMsg["id"], $pr->g("current") ]));
    }
    // store Id in SESSION
    $_SESSION["current"]=$lastMsg["id"];
    // make sure Updated is cleared
    if ( array_key_exists("updated",$_SESSION) ) unset($_SESSION["updated"]);
    // transfer message and comment
    $vr=ViewRegistry::getInstance( 2, [
      "id"=>$lastMsg["id"], "author"=>$lastMsg["author"], "message"=>$lastMsg["message"], "comment"=>$lastMsg["comment"], "controlsClass"=>"EditElements",
      "requireFiles"=>"FormElements.php,SubFormElements.php", "includeTemplate"=>"form.php"
    ] );
    return $vr;
  }

  /**
   * Processes the Edit form, presented by Act::editLast.
   * Takes the Id from SESSION, so one update is guranteed even if there came some more messages.
   * Clears the Id from SESSION on failure or removal, keeps it on successfull update.
   * Sets the Updated flag in the SESSION on successfull first update.
   * @return object ViewRegistry
   */
  public static function updateLast(PageRegistry $pr,SessionRegistry $sr) {
    //$pr->dump();
    // $pr::"current" comes from SESSION 
    //$current=$pr->g("current"); conflicts with $_REQUEST["current"]
    $current=$_SESSION["current"];
    if ( !$current ) { 
      return(self::showAlert ("Updating denied. Click the EDIT link again."));
    }
    $targetMsg = $pr->g("cardfile")->getOneMsg( $current );
    if ( !$targetMsg ) {
      return(self::showAlert (["Failed to find message number %s in the database",$current]));
    }
    if( $targetMsg["author"]!=$pr->g("user") ) {
      return(self::showAlert ("Usernames are different!"));
    }

    $pr->s( "formLink",self::addToQueryString($pr,"act=el","length"/*,"user"*/,"current") );

    if ( !empty($pr->g("del")) ) { // delete this message
      // check if it is still last
      $lastMsg = $pr->g("cardfile")->getLastMsg();
      if( $lastMsg["id"] != $current ) {
        // fail
        return(self::showAlert ("Sorry, deleting denied as there is some new message below."));
      }
      // simply delete
      $pr->g("cardfile")->deletePackMsg($current,$current);
      if ( empty($pr->g("snap")) ) {
        return(self::showAlert (["Message %s has been deleted",$current]));
      }
      // no more updates by this Id
      unset($_SESSION["current"]);
      return(self::redirectToView ($pr));
    }
    // try to update this message
    // check the input strings
    $txt=$pr->g("txt");
    if( empty($txt) ) {
      return(self::showAlert ("Please, leave something in the Message field"));
    }
    $txt=self::prepareInputText($txt,$sr);
    $comm=$pr->g("comm");
    $comm=self::prepareInputText($comm,$sr);
    $targetMsg["message"]=$txt;
    $targetMsg["comment"]=$comm;
    $targetMsg["time"]=$targetMsg["date"]="";// current date and time will be set

    // look at the state
    if ( !array_key_exists("updated",$_SESSION) ) {
      // one update allowed without check
      $_SESSION["updated"]=true;
    }
    else {
      // more updates only through last message check
      $lastMsg = $pr->g("cardfile")->getLastMsg();
      if( $lastMsg["id"] != $current ) {
        // fail
        unset($_SESSION["current"]);
        unset($_SESSION["updated"]);
        return(self::showAlert ("Sorry, updating denied as there is some new message below. Create a new message."));
      }
    }
    // update the message in database
    $pr->g("cardfile")->addMsg($targetMsg,true);// true for overwrite

    if ( empty($pr->g("snap")) ) {
      return(self::showAlert (["Message %s has been updated",$current]));
      //"Message ".$current." has been updated"
    }
    return(self::redirectToView ($pr));
  }

  /**
   * Displays a page of messages. Default View.
   * @return object ViewRegistry
   */
  public static function view (PageRegistry $pr,SessionRegistry $sr) {
    try {
      $pr->g("cardfile")->getLimits($forunBegin,$forumEnd,$a);
      $overlay=$sr->g("viewOverlay");

      if ( empty($pr->g("length")) || $pr->g("length")<0 ) $length=$sr->g("viewDefaultLength");
      else $length=$pr->g("length");

      if ( $length=="*" ) { // show all messages
        $base="end";
        $begin=$forunBegin;
        $end=$forumEnd;
        $length=$forumEnd-$forunBegin+1+10;
      }
      else { // combinations of begin= and end=
        if ( empty($pr->g("begin")) && empty($pr->g("end")) ) {// show last page
          $base="end";
          $end=$forumEnd;
          $begin = $forumEnd - $length + 1;
          if ( $begin < $forunBegin ) $begin=$forunBegin;
        }
        if ( empty($pr->g("begin")) && !empty($pr->g("end")) ) {
          $base="end";
          $end=$pr->g("end");
          if ( $end <= 0 || $end > $forumEnd ) $end=$forumEnd;
          if ( $end < $forunBegin ) $end=$forunBegin;
          $begin = $end - $length + 1;
          if ( $begin < $forunBegin ) $begin=$forunBegin;
        }
        if ( !empty($pr->g("begin")) && empty($pr->g("end")) ) {
          $base="begin";
          $begin=$pr->g("begin");
          if ( $begin < $forunBegin ) $begin=$forunBegin;
          if ( $begin > $forumEnd ) $begin=$forumEnd;
          $end = $begin + $length - 1;
          if ( $end > $forumEnd ) $end=$forumEnd;
        }
        if ( !empty($pr->g("begin")) && !empty($pr->g("end")) ) {
          throw new UsageException ("You cannot set both \"begin\" and \"end\"; use length=* to see all messages.");
        }
      }
      // find current and last page numbers
      if ($forumEnd == 0) throw new UsageException ("Thread should not be empty");
      if ($forumEnd <= $overlay) {
        $pageCurrent=$pageEnd=1;
      }
      else if ( $base=="begin" ) {
        // calculate from end message numbers
        $pageCurrent=(int)ceil(($end-$forunBegin-$overlay+1)/($length-$overlay));
        $pageEnd=(int)ceil(($forumEnd-$forunBegin-$overlay+1)/($length-$overlay));
      }
      else if ( $base=="end" ) {
        // calculate from begin message numbers
        $pageCurrent=(int)ceil(($begin-$forunBegin)/($length-$overlay)+1);
        $pageEnd=(int)ceil(($forumEnd-$forunBegin)/($length-$overlay));
      }
      else throw new UsageException ("Illegal value at \"base\" key :".$base.'!');

      $toShow=$pr->g("cardfile")->yieldPackMsg($begin,$end);
      
      if($end==$forumEnd && $sr->checkNotEmpty("autoRefresh")) $autoRefresh=$sr->g("autoRefresh");
      else $autoRefresh=false;

      $vr=ViewRegistry::getInstance( 1, [
        "controlsClass"=>"RollElements", "forumBegin"=>$forunBegin, "forumEnd"=>$forumEnd, "overlay"=>$overlay, "length"=>$length, "begin"=>$begin, "end"=>$end, "base"=>$base, "pageCurrent"=>$pageCurrent, "pageEnd"=>$pageEnd, "msgGenerator"=>$toShow,
        "autoRefresh"=>$autoRefresh, 
        "requireFiles"=>"SectionElements.php,RollElements.php", "includeTemplate"=>"section.php"
      ] );
      //$vr->s("no_such_key",0);// check catch UsageException
      //$vr->dump();
      return $vr;
    }
    catch (Exception $e) {
      return(self::showAlert($e->getMessage()));
    }
  }// end view

  /**
   * Displays the Search form and a page of found messages.
   * @return object ViewRegistry
   */
  public static function search (PageRegistry $pr, SessionRegistry $sr) {
    mb_internal_encoding("UTF-8");// ! important
    $skipSearch=0;

    $q=$pr->g("query");
    $andTerms=[$q];

    if ( strlen($q)<2 ) {
      $pr->s("alert","Please, enter the search string");
      $skipSearch=1;
    }
    if ( Act::charsInString($q,"<>'") ) {
      $pr->s("alert","Sorry, your query \"".htmlspecialchars($q)."\" contains forbidden symbols");
      $skipSearch=1;
    }

    $lim=$pr->g("searchLength");
    if ( empty($lim) ) $lim=$pr->g("length");
    $order=$pr->g("order");
    if ( empty($order) ) $order="desc";

    if ($skipSearch) $toShow=null;
    else {
      // if SPACE  is present in the middle of the query, remind the rules
      if ( strpos($q," ")>0 && strrpos($q," ")<strlen($q)-1 ) $pr->s("alert","Use \"foo&bar\" to find messages containing both \"foo\" and \"bar\"<br/>Use \"foo bar\" to find messages containing  \"foo[SPACE]bar\"");
      // if & is present in the middle of the query, break up the query into array
      if ( strpos($q,"&")>0 && strrpos($q,"&")<strlen($q) ) $andTerms=explode("&",$q);
      // do other good things to terms
      try {
        $andTerms=self::prepareTerms($andTerms);
      }
      catch (OperationalException $oe) {
        $searchLink=self::addToQueryString($pr,"act=search&amp;query=","searchLength","length","order");
        $pr->s("formLink",$searchLink);
        return(self::showAlert ( $oe->getMessage() ));
      }

      $toShow=$pr->g("cardfile")->yieldSearchResults($andTerms,$pr->g("order"),$lim);
    }

    $vr=ViewRegistry::getInstance( 2, [
      "controlsClass"=>"SearchElements", "query"=>$pr->g("query"), "order"=>$order, "searchLength"=>$lim, "length"=>$pr->g("length"), "msgGenerator"=>$toShow, "searchTerms"=>$andTerms, "highlight"=>1,
      "requireFiles"=>"SectionElements.php,SearchElements.php", "includeTemplate"=>"section.php"
    ] );
    return $vr;
  }

  /**
   * Displays Alert message, possibly with Back and Ok links.
   * @param
   * @param
   * @param string $alertMessage
   * @return void
   */
  public static function _showAlert (PageRegistry $pr, SessionRegistry $sr, $alertMessage) {
    $pr->s( "alert",$alertMessage );
    include ($sr->g("templatePath")."alert.php");
    exit(0);
  }
  
  /**
   * Prepares Alert message, possibly with Back and Ok links.
   * @param string $alertMessage
   * @return object ViewRegistry 
   */
  public static function showAlert ($alertMessage) {
    ViewRegistry::clearInstance();
    $vr=ViewRegistry::getInstance( 2,[
      "alert"=>$alertMessage, "requireFiles"=>null, "includeTemplate"=>"alert.php"
    ] );
    return $vr; 
  }

  // UTILITIES ----------------------------------

  /**
   * Filters texts, which were recieved from user.
   * @uses MaskTags::mask_tags
   * @param string $txt raw text
   * @param object SessionRegistry $sr
   * @return string truncated and tag-filtered text
   */
  public static function prepareInputText($txt, SessionRegistry $sr) {
    mb_internal_encoding("UTF-8");
    //if( strlen($txt) > $sr->g("maxMessageBytes") ) $txt=substr($txt,0,$sr->g("maxMessageBytes"));
    // some browsers use \r\n, some \n as Newline
    //http://stackoverflow.com/questions/7642257/javascript-string-length-differs-from-php-mb-strlen-on-textarea-row-line-break
    $txt=str_replace("\r\n","\n",$txt);
    //echo (">".$sr->g("maxMessageLetters").":".mb_strlen($txt));
    if ( mb_strlen($txt) > $sr->g("maxMessageLetters") ) { 
      $txt = mb_substr( $txt, 0, $sr->g("maxMessageLetters") );
    }
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

  /**
   * Checks if a string contains any of given symbols.
   * @param string $object string to test
   * @param string $charsString "[symbol1][symbol2]..." like "<&'"
   * @return boolean true if found, false if not
   */
  public static function charsInString($object,$charsString) {
    if ( empty($object) ) return (false);
    if (strtok($object,$charsString)!==$object) return (true);
  }

  /**
   * Cleverly finds the basis for making absolute URIs from relative ones.
   * @return string
   */
  public static function myAbsoluteUri () {
    $url = 'http://';
    if ( (array_key_exists("HTTPS",$_SERVER)) && $_SERVER['HTTPS'] ) $url = 'https://';
    $url .= $_SERVER['HTTP_HOST'];// Get the server
    $url .= rtrim(dirname($_SERVER['PHP_SELF']), '/\\');// Get the current directory
    return ($url);
  }

  /**
   * Takes from the Page Registry the actual link to the viewer ( as query string, relative ), turns it into valid absolute address and sends it as REDIRECT header.
   * @return void
   */
  public static function redirectToView (PageRegistry $pr) {
    $uri=$pr->g("viewLink");
    $uri=str_replace("&amp;","&",$uri);// it's a header rather than link -- entity is mistake
    $uri=self::myAbsoluteUri()."/".$uri;
    $vr=ViewRegistry::getInstance(2,[ "redirectUri"=>$uri ]);
    return $vr;
  }

  /**
   * Helps to create query string from Registry parametrs.
   * @param     PageRegistry    $pr registry
   * @param     string          $command this will be inserted as such
   * @param     string          name of registry parameter, will be inserted as &name=value
   * @param     string          more of these
   * @returns string ready Query String with leading ? and middle ampersands as &amp;
   */
  public static function addToQueryString (PageRegistry $pr,$command) {
    $qs=$command;
    for ($i=2;$i<func_num_args();$i++) {
      $n=func_get_arg($i);
      if( !empty($qs) && !empty($pr->g($n)) ) $qs.="&amp;";
      if( !empty($pr->g($n)) ) $qs.=$n."=".urlencode($pr->g($n));
    }
    if( !empty($qs) ) $qs="?".$qs;
    return ($qs);
  }

  /**
   * Performs conditioning on search terms.
   * Removes quotes, checks length and turns to lower case
   * @param     array $what search terms
   * @returns   array same array after workup
   * @throws    OperationalException if meets a short or empty term
   */
  private function prepareTerms($what) {
    // remove quotes if present
    $res=[];
    foreach ($what as $k=>$andTerm ) {
      if ( strpos($andTerm,'"')===0 && strrpos($andTerm,'"')===(strlen($andTerm)-1) ) {
        $andTerm=trim($andTerm,'"');
        //print("@$andTerm@");
      }
      if ( mb_strlen($andTerm)<=1 ) throw new OperationalException ("Too short or empty search term  \"".$andTerm.'"');
      // make the search case-insensitive
      $andTerm=mb_strtolower($andTerm);
      $res[]=$andTerm;
    }
    return($res);
  }

  /** Does the searching.
   * Performs AND on hits, collects their positions for highlighting.
   * @param     string  $haystack string to search
   * @param     array   $what array of search terms
   * @param     boolean $collectAll tells to return all found positions disregarding the AND result
   * @returns   array   On failure: empty, on success: array
      [ [start1,start2,...], [end1,end2,...] ] -- positions of term1,term2,...
   */
  public function searchInString ($haystack,array $what,$collectAll=false) {
    $starts=[];
    $ends=[];

    $res=true;
    $haystack=mb_strtolower($haystack);

    foreach ($what as $j=>$andTermM ) {
      $pos=null;
      if (mb_substr($andTermM,0,1)==="-") {// minus-word
        $andTermM=mb_substr($andTermM,1);
        $andResult=( mb_strpos($haystack,$andTermM)===false );
      }
      else {
        $p=mb_strpos($haystack,$andTermM);
        if ($p!==false) {
          $andResult=true;
          if ($collectAll) { // add positions to hit lists
            do {
              $starts[]=$p-1;
              $ends[]=$p+mb_strlen($andTermM)-1;
            } while ( ($p=mb_strpos($haystack,$andTermM,$p+1))!==false );
          }
        }
        else $andResult=false;
      }
      $res=($res && $andResult);
    }
    if ($res && $collectAll) return (array($starts,$ends));
    else return ($res);

  }

}// end Act
?>