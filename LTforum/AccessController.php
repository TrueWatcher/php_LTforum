<?php
/**
 * @pakage LTforum
 * @version 1.2 added SessionManager
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
}

class AccessController {
  
  static function hello() {
    return ("Hi, I'm AccessController class");
  }
  
  protected $c;// context
  protected $r;// input, normally $_REQUEST
  protected $session;// session.normally $_SESSION
  protected $helper;// helper class (library), normally AccessHelper
  
  function __construct(AuthRegistry $context,$request=null,&$session=null, $helperClass="AccessHelper") {
    if ( !isset($request) ) $request=$_REQUEST;
    //if ( !isset($session) ) $session=$_SESSION;
    if (!is_array($request)) throw new UsageException("Wrong argument request!");
    //if (!is_array($session)) throw new UsageException("Wrong argument session!");
    if ( !class_exists($helperClass) ) throw new UsageException("Wrong helper class ".$helperClass."!");
    $this->c = $context;
    $this->r = $request;
    $this->session = &$session;
    $this->helper = $helperClass;
    //echo("helper:".$this->helper);
  }
  
  /**
    * Controller top level. Initializations and command/state logic.
    * @uses Applicant
    * @uses AccessHelper for interfaces to View, session control, Header
    * @see LTforum/AccessController_table.rtf for command/state table
    * @return true on success, false or message on success/redirect, false or error message on failure
    */
  public function go () {
    $ar=$this->c;
    $hc=$this->helper;// $this->helper::func() causes error
    $note="";
    $pauseFail=rand(10,20);
    $pauseAllow=3;//rand(2,5);
    $return=false;

    $hc::startSession($this->c);
    echo(" After start: ");
    if (isset($_SESSION)) print_r($_SESSION);// DEBUG
    if (isset($_SESSION) && !isset($this->session) ) $this->session = &$_SESSION;// important & !
    $this->c->readCommands($this->r);
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
      $hc::sendRedirect($targetUri);
      //header( "Location: ".$targetUri );
      //return ( "redirected to ".$targetUri );
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
          //return(false);
          break;
        }
        if ( $a->statusEquals("active")) {
          // active > postAuth
          $hc::regenerateId();
          $a->demandReg("Session reset by user","postAuth");
          //return(false);
          break;
        }
        else {
          // postAuth > postAuth
          // redirect to cleaned uri without reg=deact
          $targetUri=$hc::makeRedirectUri($this->c);
          $hc::sendRedirect($targetUri);
          //header( "Location: ".$targetUri );
          //return ( "redirected to ".$targetUri );
          $return="redirected to ".$targetUri;
          break;
        }
      }
      if ( empty($this->session) || $a->statusEquals("zero") || $a->statusEquals("preAuth") ) {
        // same as reg=reset
        $a->demandReg("","preAuth");
        //return(false);
        break;
      }
      throw new UsageException ("Wrong Command/State reg=".$this->c->g("reg")."/".$a->getStatus()."!");

    case "authPlain":
    case "authOpp":
    case "authDigest":
      if ( $a->statusEquals("active") ) {
        // error, possibly an attack
        // active > zero > preAuth
        $hc::regenerateId();
        $a->setStatus("zero");
        // fall-through
      }
      if ( empty($this->session) || $a->statusEquals("zero") ) {
        // this also should not happen normally
        // zero > preAuth
        $a->demandReg("A wrong-timed request","preAuth");
        //return(false);
        break;
      }
      if ( $a->statusEquals("preAuth") || $a->statusEquals("postAuth") ) {
        $ret=$a->checkSessionKeys(["notBefore","activeUntil"],false);
        if ( $ret===true ) $ret=$a->checkActiveUntil();
        if ( $ret!==true ) {
          // preAuth or postAuth > preAuth
          $a->demandReg($ret,"preAuth");
          //return(false);
          break;
        }
        $ret=$a->checkNotBefore();
        if ( $ret!==true ) {
          $hc::showAuthAlert($this->c,"Please, wait a few seconds and refresh the page");
          //$a->setStatus("noChange");
          //return(false);
          break;
        }

        // additional initializations
        //echo(" Trying to register ");
        $this->c->readInput($this->r);
        $this->c->readSession($this->session);
        $a->initFull();
        // pre-registration checks and registration processaing
        $ret=$a->beforeAuth();
        if ( $ret===true ) $ret=$a->processAuth();
        if ( $ret!==true ) {
          // preAuth or postAuth > preAuth
          sleep($pauseFail);
          $a->demandReg($ret,"preAuth");
          //return(false);
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
        // redirect ?
        if ( $a->optionalRedirect() ) { 
          // header has been sent
          //return (false);
          break;
        }
        // continue to main controller
        //return (true);
        $return=true;
        break;
      }
      throw new UsageException ("Wrong Command/State reg=".$this->c->g("reg")."/".$a->getStatus()."!");

    case "":
      if ( empty($this->session) || $a->statusEquals("preAuth") ) {
        $a->demandReg("","preAuth");
        //return(false);
        break;
      }
      if ( $a->statusEquals("active") ) {
        $ret = $a->checkActiveParams();
        if ( $ret===true ) $ret = $a->checkRealm();
        if ( $ret===true ) $ret = $a->checkAdmin();
        if ( $ret!==true ) {
          // active > preAuth
          $a->demandReg($ret,"preAuth");
          //return(false);
          break;
        }
        $ret = $a->checkNotBefore();
        if ( $ret!==true ) {
          $hc::showAuthAlert($this->c,"Please, wait a few seconds and refresh the page");
          //$a->setStatus("active");
          //return(false);
          break;
        }
        $ret = $a->checkActiveUntil();
        if ( $ret!==true ) {
          // active > postAuth
          $note=$a->getUnanswered();
          //session_regenerate_id(true);
          $a->markCookieTime();
          $a->demandReg($note,"postAuth");
          //return(false);
          break;
        }
        // happy end
        // continue to main controller
        $a->setTimeLimits( 0,$this->c->g("maxDelayPage") );
        //$a->setStatus("active");
        //return (true);
        $return=true;
        break;
      }
      if ( $a->statusEquals("postAuth") ) {
        $ret = $a->checkRealm();
        if ($ret===true) {
          // postAuth > postAuth
          $note=$a->getUnanswered();
          $a->keepCookie();// regenerate cookie and id if the session is aged (more than 1 day typically)
          $a->demandReg($note,"postAuth");
          //return(false);
          break;
        }
        else {
          // different realm : interpreted as request to a fresh new registration
          // postAuth > preAuth
          $a->setStatus("zero");
          $hc::regenerateId();
          $a->demandReg($ret,"preAuth");
          //return(false);
          break;
        }
      }
      throw new UsageException ("Wrong Command/State reg=".$this->c->g("reg")."/".$a->getStatus()."!");

    default:
      $hc::showAuthAlert($this->c,"Wrong command reg=".$this->c->g("reg")."!");
      // state noChange
      //return (false);
      break;
    }// end switch
    
    echo(" Re-reading session: ");
    if (isset($_SESSION)) print_r($_SESSION);// DEBUG

    $this->c->trace($return);
    return ($return);

  }// end go
}// end AccessController