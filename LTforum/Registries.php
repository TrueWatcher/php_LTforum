<?php
/**
 * @pakage LTforum
 * @version 1.2 added Access Controller and User Manager
 */
 
class PageRegistry extends SingletAssocArrayWrapper {
  protected static $me=null;// private causes access error
  
  
  public function readInput($inputKeys) {
    foreach ($inputKeys as $k) {
      if ( array_key_exists($k,$this->input) ) $this->s($k,$this->input[$k]);
      else $this->s($k,"");
    }
  }

  public function readSession() {
    $keys=["authName"=>"user", /*"current"=>"current", conflicts with readInput*/ "updated"=>"updated" ];
    if ( empty($this->session)/*!$_SESSION*/ ) throw new UsageException("PageRegistry: no active session found !");//return(false);
    $i=0;
    foreach($keys as $sessKey=>$regKey) {
      if ( array_key_exists($sessKey,$this->session) && $this->session[$sessKey] ) {
        $this->s($regKey,$this->session[$sessKey]);
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

  public function tryInitCardfile(SessionRegistry $sr, $helperClass) {
    try {
      $this->s( "cardfile",new CardfileSqlt( $sr->g("forum"),true ) );
    }
    catch (Exception $e) {
      $helperClass::showAlert ($this,$sr,$e->getMessage());
    }      
  }
  
  protected $input;
  protected $session;
  protected $helperClass;
  
  /**
   * All pageRegistry initialization.
   * Takes data from input, session, sessionRegistry. Allows for isolation testing.
   * @return void
   */
  public function initAllAfterAuth($input=null, $session=null, SessionRegistry $sr, $helperClass, $forumTitle, $forumName) {
    if( !isset($input) ) $this->input = $_REQUEST;
    else $this->input = $input;
    if( !isset($session) ) $this->session = & $_SESSION;
    else $this->session = &$session;
    $this->helperClass = $helperClass;
    
    $inputKeys=[ "act", "current", "begin", "end", "length", "user", "txt", "comm", "snap", "del", "query", "searchLength", "order" ];
    $this->readInput($inputKeys);
    $this->readSession();
    if ($forumTitle) $this->s("title",$forumTitle);
    else $this->s("title","LTforum::".$forumName);
    $this->s( "viewLink", $helperClass::addToQueryString($this,"end=-1","length")."#footer" );
    $this->tryInitCardfile($sr, $helperClass);
  }
  
  public function queryStringIsEmpty() {
    //echo('act='.$this->g("act").',begin='.$this->g("begin").',end='.$this->g("end") );
    return ( empty($this->g("act")) && empty($this->g("begin")) && empty($this->g("end")) );
  }
  
  public function initAdmBeforeAuth($input=null, $session=null, SessionRegistry $sr, $helperClass, $adminTitle) {
    if( !isset($input) ) $this->input = $_REQUEST;
    else $this->input = $input;
    if( !isset($session) ) $this->session = & $_SESSION;
    else $this->session = &$session;
    $this->helperClass = $helperClass;
    
    $inputKeys=["act","forum","current","begin","end","length","obj","order","kb","newBegin","txt","comm","author","clear","uEntry","user","aUser"];
    $this->readInput($inputKeys);
    $this->s("title",$adminTitle);
    if ( empty( $this->g("forum") ) ) {
      // SESSION will not work before AccessController
      sleep(10);
      exit("You should specify the target forum");
    }
    $forumName=$this->g("forum");
    $targetPath=$sr->g("forumsPath").$forumName."/".$forumName;
    $this->s("targetPath",$targetPath);
    $this->s( "title",$adminTitle." : ".$forumName );
    $this->s( "viewLink",$helperClass::addToQueryString($this,"","forum") );
  }
  
  public function initAdmAfterAuth(SessionRegistry $asr) {
    $hc=$this->helperClass;
    try {
      $this->s("cardfile",new CardfileSqlt($this->g("targetPath"),true));
    }
    catch (Exception $e) {
      // of no use as new db was created
      $hc::showAlert ($this,$asr,$e->getMessage());
    }
    $total=$this->g("cardfile")->getLimits($forumBegin,$forumEnd,$a,true);
    $this->s("forumBegin",$forumBegin);
    $this->s("forumEnd",$forumEnd);
    $missing=$forumEnd-$forumBegin+1-$total;
    if ($missing) $hc::showAlert($this,$asr,"There are ".$missing." missing messages");
  }
}

class SessionRegistry extends SingletAssocArrayWrapper {
  protected static $me=null;
  
  public static function initVectorForFrontend($mainPath,$templatePath,$assetsPath,$forumName,$lang="en") {
    $ivf=[
    "lang"=>$lang, "viewDefaultLength"=>10, "viewOverlay"=>1, "toPrintOutcome"=>0,"mainPath"=>$mainPath, "templatePath"=>$templatePath, "assetsPath"=>$assetsPath, "maxMessageLetters"=>750, "narrowScreen"=>640, "forum"=>$forumName ];
    return $ivf;
  }
  
  public static function initVectorForBackend($mainPath,$templatePath,$assetsPath,$forumName,$forumsPath) {
    $ivb=[
    "lang"=>"en", "viewOverlay"=>1, "toPrintOutcome"=>1,"mainPath"=>$mainPath, "templatePath"=>$templatePath, "assetsPath"=>$assetsPath,"forumsPath"=>$forumsPath, "maxMessageLetters"=>750
    ];
    return $ivb;
  }
}

class ViewRegistry extends SingletAssocArrayWrapper {
  protected static $me=null;
  
 /**
  * Allows for decoupling the View from the Controller.
  * First checks if redirect is demanded by ViewRegistry::redirectUri.
  * Then includes template classes as is stated in ViewRegistry::requireFiles,
  * and includes template itself as stated in ViewRegistry::includeTemplate
  * Makes shure SessionRegistry and PageRegistry instances are $sr and $pr as required by templates
  * @return void
  */
  public function display(SessionRegistry $sr,PageRegistry $pr) {
    //$this->dump();
    $vr=$this;// $vr is required for templates
    if ( $this->checkNotEmpty("redirectUri")) {
      header("Location: ".$this->g("redirectUri"));
      exit(0);
    }
    if ($this->checkNotEmpty("requireFiles")) {
      $files=explode(",",$this->g("requireFiles"));
      foreach($files as $classFile) {
        require_once($sr->g("templatePath").$classFile);
      }
    }
    if ($this->checkNotEmpty("includeTemplate")) {
      include ($sr->g("templatePath").$this->g("includeTemplate"));
    }
    else {
      throw new UsageException("Empty ViewRegistry::includeTemplate");
    }
  }
}

?>