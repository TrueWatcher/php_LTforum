<?php
/**
 * @package LTforum
 * @version 1.5 added features to auth subsystem
 */

class AuthRegistry extends SingletAssocArrayWrapper {
  protected static $me=null;

  function exportToSession() {
    $what=["pers","secret","clientCount","serverCount"];
    $exp=[];
    foreach ($what as $k) {
      $exp[$k]=$this->g($k);
    }
    return($exp);
  }
  
  function readCommands($superGlobal=null) {
    // function readCommands($superGlobal=$_REQUEST) does not work
    if (!isset($superGlobal)) $superGlobal=$_REQUEST;
    $what=["reg","act"];
    foreach ($what as $k) {
      if ( array_key_exists($k,$superGlobal) ) $this->s($k,$superGlobal[$k]);
    }
  }

  function readInput($superGlobal=null) {
    if (!isset($superGlobal)) $superGlobal=$_REQUEST;
    $what=["reg","user","ps","cn","response","plain","pers"];
    foreach ($what as $k) {
      if ( array_key_exists($k,$superGlobal) ) $this->s($k,$superGlobal[$k]);
    }
  }

  function readSession ( $session=null ) {
    // readSession ( $session=$_SESSION )
    if ( !isset($session) ) {
      if ( !isset($_SESSION) ) throw new UsageException("Reading empty SESSION");
      $session=$_SESSION;
    }
    $what=["serverNonce"];
    if (! isset($session["serverNonce"]) ) throw new UsageException("Missing serverNonce!"); 
    $this->s("serverNonce",$session["serverNonce"]);
  }
  
  function trace($str) {
    if($str===false) $str="false";
    if($str===true) $str="true";
    $separator=">";
    $t=$this->g("trace").$separator.$str;
    $this->s("trace",$t);
  }
  
  public static function getDefaults() {
    $r=[
      "realm"=>"", "targetPath"=>"", "templatePath"=>"", "assetsPath"=>"", "isAdminArea"=>1, "authName"=>"", "serverNonce"=>"", "serverCount"=>0, "clientCount"=>0, "secret"=>"", "authMode"=>1,  "minDelay"=>5, "maxDelayAuth"=>5*60, "maxDelayPage"=>60*60, "maxTimeoutGcCookie"=>5*24*3600, "minRegenerateCookie"=>1*24*3600, "guestsAllowed"=>false, "masterRealms"=>"", "expireWarnInterval"=>300, "reg"=>"", "act"=>"", "user"=>"", "ps"=>"", "cn"=>"", "response"=>"", "plain"=>"", "pers"=>"", "alert"=>"", "header"=>"", "requireFiles"=>"", "includeTemplate"=>"", "controlsClass"=>"", "trace"=>""
    ];
    return $r;
  }
  
  public function adjustForFrontend(SessionRegistry $sr) {
    $a=[
      "realm"=>$sr->g("forum"),
      "targetPath"=>"",/*$sr->g("targetPath"),*/
      "templatePath"=>$sr->g("templatePath"),
      "assetsPath"=>$sr->g("assetsPath"),
      "isAdminArea"=>0
    ];
    $this->overrideValuesBy($a);  
  }
  
  public function adjustForAdmin(SessionRegistry $asr,PageRegistry $apr) {
    $a=[
      "realm"=>$apr->g("forum"),
      "targetPath"=>$asr->g("forumsPath").$apr->g("forum")."/",
      "templatePath"=>$asr->g("templatePath"),
      "assetsPath"=>$asr->g("assetsPath"),
      "isAdminArea"=>1
    ];
    $this->overrideValuesBy($a);  
  }
  
  public function guestPassAllowed() {
    return( empty($this->arr["reg"]) && ! $this->arr["isAdminArea"] && $this->arr["guestsAllowed"] );
  }
}

class AccessController {
  
  static function hello() {
    return ("Hi, I'm AccessController class");
  }
  
  protected $c;// context
  protected $r;// input, normally $_REQUEST
  protected $session;// session.normally $_SESSION
  protected $helper;// helper class (library), normally AccessHelper
  
  /**
   * Attaches context, input, session, helper objects. Is essential for unit testing.
   * @param object $context
   * @param array|null $request an array to simulate the request string or null to attach $_REQUEST
   * @param array|null $session !input-output an array to simulate the session data or null to attach $_SESSION
   * @param string $helperClass
   */
  function __construct(AuthRegistry $context,$request=null,&$session=null, $helperClass="AccessHelper") {
    if ( ! isset($request)) $request=$_REQUEST;
    if ( ! is_array($request)) throw new UsageException("Wrong argument request!");
    if ( ! class_exists($helperClass) ) throw new UsageException("Wrong helper class ".$helperClass."!");
    $this->c = $context;
    $this->r = $request;
    $this->session = &$session;
    $this->helper = $helperClass;
    //echo("helper:".$this->helper);
  }
  
  /**
    * Access Controller top level. Initializations and command/state logic.
    * @uses Applicant
    * @uses AccessHelper for interfaces to View, session control, Header
    * @see LTforum/AccessController_table.rtf for command/state table
    * @return true on success, false or message on success/redirect, false or error message on failure
    */
  public function go () {
    $ar=$this->c;
    $hc=$this->helper;// $this->helper::func() causes error
    $note="";
    $pauseFail=rand(5,15);
    $pauseAllow=2;//rand(2,5);
    $return=false;
    
    $this->c->readCommands($this->r);
    if ($this->c->guestPassAllowed() && $hc::tryPassAsGuest($this->c)) {
      // If there's an active session, resume it. Useful for things like "Edit/Delete last message"
      if( isset($_COOKIE) && ! empty($_COOKIE) ) $hc::startSession($this->c);
      $return=true;
      $this->c->trace($return);
      return ($return);
    }

    $hc::startSession($this->c);
    //echo(" After start: ");
    //if (isset($_SESSION)) print_r($_SESSION);// DEBUG
    if (isset($_SESSION) && !isset($this->session) ) $this->session = &$_SESSION;// important & !
    //$this->c->dump();
    $a = new Applicant ( $this->c, $this->r, $this->session, $this->helper );
    $a->initMin();

    switch ($this->c->g("reg")) {

    case "reset":
      if ( $a->statusEquals("active") || $a->statusEquals("postAuth") ) {
        $a->setStatus("zero");
        $hc::regenerateId();          
      }
      // any state > preAuth
      // redirect to cleaned uri without reg=deact
      $targetUri=$hc::makeRedirectUri($this->c);
      $hc::makeRedirect($targetUri,$this->c);
      $return="redirected to ".$targetUri;
      break;        

    case "deact":
      if ( $a->statusEquals("active") || $a->statusEquals("postAuth") ) {
        $ret=$a->checkRealm($this->c);
        if ($ret!==true) {
          // Error: reg=deact with wrong realm
          $a->setStatus("zero");
          $hc::regenerateId();
          $a->demandReg($ret,"preAuth");
          break;
        }
        if ( $a->statusEquals("active")) {
          // active > postAuth
          $hc::regenerateId();
          $a->demandReg("Session reset by user","postAuth");
          break;
        }
        else {
          // postAuth > postAuth
          // redirect to cleaned uri without reg=deact
          $targetUri=$hc::makeRedirectUri($this->c);
          $hc::makeRedirect($targetUri,$this->c);
          //$return="redirected to ".$targetUri;
          break;
        }
      }
      if ( empty($this->session) || $a->statusEquals("zero") || $a->statusEquals("preAuth") ) {
        // same as reg=reset
        $a->demandReg("","preAuth");
        break;
      }
      throw new UsageException ("Wrong Command/State reg=".$this->c->g("reg")."/".$a->getStatus()."!");

    case "authPlain":
    case "authOpp":
    case "authDigest":
      if ( $a->statusEquals("active") ) {
        // error, possibly an attack
        // alert and no state change
        $hc::makeAuthAlert($this->c,"Unappropriate registration request");
        break;
      }
      if ( empty($this->session) || $a->statusEquals("zero") ) {
        // this also should not happen normally
        // zero > preAuth
        $a->demandReg("A wrong-timed request","preAuth");
        break;
      }
      if ( $a->statusEquals("preAuth") || $a->statusEquals("postAuth") ) {
        $ret=$a->checkSessionKeys(["notBefore","activeUntil"],false);
        if ( $ret===true ) $ret=$a->checkActiveUntil();
        if ( $ret!==true ) {
          // preAuth or postAuth > preAuth
          $a->demandReg($ret,"preAuth");
          break;
        }
        $ret=$a->checkNotBefore();
        if ( $ret!==true ) {
          $hc::makeAuthAlert($this->c,"Please, wait a few seconds and refresh the page");
          //$a->setStatus("noChange");
          break;
        }

        // additional initializations
        //echo(" Trying to register ");
        $this->c->readInput($this->r);
        $this->c->readSession($this->session);
        $a->initFull();
        // pre-registration checks and registration processaing
        $hc::removeExpiredVisitors($this->c->g("realm"),$this->c);
        //exit;        
        $ret=$a->beforeAuth();
        if ( $ret===true ) $ret=$a->processAuth();
        if ( $ret!==true ) {
          // preAuth or postAuth > preAuth
          sleep($pauseFail);
          $a->demandReg($ret,"preAuth");
          break;
        }
        // successful registration
        // preAuth or postAuth > active
        if ( $a->statusEquals("preAuth") ) {
          $hc::regenerateId();
          $a->markCookieTime();
        }
        // on postAuth > active regeneration is skipped as there's keepCookie in postAuth checks
        sleep($pauseAllow);
        $a->setTimeLimits( 0,$this->c->g("maxDelayPage") );
        $a->setStatus("active");
        // make something more if needed
        $hc::onRegistrationEvent($this->c,$this->session);
        // redirect ?
        if ( $a->optionalRedirect() ) { 
          // header has been sent
          break;
        }
        // continue to main controller
        $return=true;
        break;
      }
      throw new UsageException ("Wrong Command/State reg=".$this->c->g("reg")."/".$a->getStatus()."!");

    case "":
      if ( empty($this->session) || $a->statusEquals("zero") || $a->statusEquals("preAuth") ) {
        $a->demandReg("","preAuth");
        break;
      }
      if ( $a->statusEquals("active") ) {
        $ret = $a->checkActiveParams();
        if ( $ret===true ) $ret = $a->checkRealm();
        if ( $ret===true ) $ret = $a->checkAdmin();
        if ( $ret!==true ) {
          // active > preAuth
          $a->demandReg($ret,"preAuth");
          break;
        }
        $ret = $a->checkExpiredAndWarn();
        if ( $ret!==true ) {
          $hc::makeAuthAlert($this->c,$ret);
          break;
        }
        $ret = $a->checkNotBefore();
        if ( $ret!==true ) {
          $hc::makeAuthAlert($this->c,"Please, wait a few seconds and refresh the page");
          //$a->setStatus("active");
          break;
        }
        $ret = $a->checkActiveUntil();
        if ( $ret!==true ) {
          // active > postAuth
          $note=$hc::getUnanswered($this->session["authName"],$this->c);
          //session_regenerate_id(true);
          $a->markCookieTime();
          $a->demandReg($note,"postAuth");
          break;
        }
        
        // happy end
        // continue to main controller
        $a->setTimeLimits( 0,$this->c->g("maxDelayPage") );
        //$a->setStatus("active");
        $return=true;
        break;
      }
      if ( $a->statusEquals("postAuth") ) {
        $ret = $a->checkRealm();
        if ($ret===true) {
          // postAuth > postAuth
          $note=$hc::getUnanswered($this->session["authName"],$this->c);
          $a->keepCookie();// regenerate cookie and id if the session is aged (more than 1 day typically)
          $a->demandReg($note,"postAuth");
          break;
        }
        else {
          // different realm : interpreted as request to a fresh new registration
          // postAuth > preAuth
          $a->setStatus("zero");
          $hc::regenerateId();
          $a->demandReg($ret,"preAuth");
          break;
        }
      }
      throw new UsageException ("Wrong Command/State reg=".$this->c->g("reg")."/".$a->getStatus()."!");

    default:
      $hc::makeAuthAlert($this->c,["Wrong command reg=%s!",$this->c->g("reg")]);
      // state noChange
      break;
    }// end switch
    
    //echo(" Re-reading session: ");
    //if (isset($_SESSION)) print_r($_SESSION);// DEBUG

    $this->c->trace($return);
    return ($return);

  }// end go

  /**
   * Sends headers and displays login or alert page as is prepared in AccessRegidtry.
   * @return void
   */
  public function display() {
    $ar=$this->c;
    //$ar->dump();
    if ( $ar->checkNotEmpty("header")) {
      header($ar->g("header"));
      //return;
    }
    if ($ar->checkNotEmpty("requireFiles")) {
      $files=$ar->g("requireFiles");
      if(is_string($files)) $files=explode(",",$files);
      foreach($files as $classFile) {
        require_once($ar->g("templatePath").$classFile);
      }
    }
    if ($ar->checkNotEmpty("includeTemplate")) {
      include ($ar->g("templatePath").$ar->g("includeTemplate"));
    }
    else {
      //echo(" Empty AuthRegistry::includeTemplate ");
      //throw new UsageException("Empty AuthRegistry::includeTemplate");
    }
  }// end display

  /**
   * The debug mode of display().
   * @return void
   */  
  public function displayTitle() {
    $ar=$this->c;
    //$ar->dump();  
    if ($ar->checkNotEmpty("requireFiles")) {
      $files=$ar->g("requireFiles");
      if(is_string($files)) $files=explode(",",$files);
      foreach($files as $classFile) {
        require_once($ar->g("templatePath").$classFile);
      }
    }
    if ($ar->checkNotEmpty("includeTemplate")) {
      $cc=$ar->g("controlsClass");
      echo("\nPage:".$cc::titleSuffix($ar)."\n");
    }  
  }
}// end AccessController