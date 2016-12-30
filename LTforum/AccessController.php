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
    
    function readInput($superGlobal) {
      $what=["reg","user","ps","cn","response","plain","pers"];
      $exp=[];
      foreach ($what as $k) {
        if ( array_key_exists($k,$superGlobal) ) $this->s($k,$superGlobal[$k]);
      }
    }
    
    function readSession() {
      $what=["serverNonce"];
      $this->s("serverNonce",$_SESSION["serverNonce"]);
    }
  }

  class AccessController {
        
    // ----- Common resourses and utilities -----
    
    /**
     * Name of a file in forum's folder, that contains users' names and password hashes.
     * Used also by UserManager
     */
    static $groupFileName=".group";
    
    /**
     * Creates a password hash.
     * Has a parallel function on the client side.
     * Uses also host name.
     * @see assets/authHelper.js makeHa
     * @param string $userName
     * @param string $realm
     * @param string $password
     * @returns string
     */
    static function makeHa1($userName,$realm,$password) {
      $realm=strtolower($_SERVER["SERVER_NAME"]).$realm;
      //echo(">>".$userName.$realm.$password);
      return ( md5($userName.$realm.$password) ); 
    }
    
    /**
     * Creates the response for the Digest authentication.
     * Has a parallel function on the client side. 
     * @see assets/authHelper.js makeResponse
     * @param string $sn server nonce
     * @param string $ha1 password hash
     * @param string $cn client nonce
     * @returns string
     */
    static function makeResponse($sn,$ha1,$cn) {
      return ( md5($sn.$ha1.$cn) ); 
    }
    
    static function iterateSecret($secret,$cNonce) {
      return ( md5($secret.$cNonce) );
    }
    
    /**
     * Initializes the new thread with admin/admin.
     * Writes users data file in .ini format.
     * @uses $groupFileName
     * @param string $path path to the users file (formed on page initialization)
     * @param string $forum forum name like "demo"
     * @returns void
     */
    static function createEmptyGroupFile($path,$forum) {
      $nl="\n";
      $s="";
      $s.="; must not contain empty lines and must end with NL !".$nl;
      $s.="[".$forum."]".$nl;
      $s.="admin=".self::makeHa1("admin",$forum,"admin").$nl;
      $s.="[".$forum."Admins]".$nl;
      $s.="admin=".$nl;
      file_put_contents( $path.self::$groupFileName, $s );
    }
    
    /**
     * Reads users data file and creates an array of pairs "userName"=>"passwordHash"
     * @uses $groupFileName
     * @param string $realm forum name
     * @param {object AuthRegistry} $context
     * @returns array
     */
    static function parseGroup($realm,$context) {
      $targetPath="";
      if($context) $targetPath=$context->g("targetPath");
      $groupFile=$targetPath.self::$groupFileName;
      if ( !file_exists($groupFile) ) {
        //throw new AccessException ("No such file:".$groupFile."!");
        self::createEmptyGroupFile($targetPath,$context->g("realm"));
        sleep(1);// important!
      }
      $parsed=parse_ini_file($groupFile,true);
      //print_r($parsed);
      if (!array_key_exists($realm,$parsed)) throw new AccessException ("Section ".$realm." not found in the file ".$groupFile);
      return($parsed[$realm]);
    }
    
    /**
     * Generates random server nonce for the Digest authentication.
     * @returns string
     */
    static function makeServerNonce() {
      if ( is_callable("openssl_random_pseudo_bytes") ) {
        $openSslOutcome=false;
        $sn=openssl_random_pseudo_bytes(16,$openSslOutcome);
        //$ar->s("alert",$ar->g("alert")." tried secure:".$openSslOutcome." " );
      }
      else {
        $sn0=(string)random_int(0,PHP_INT_MAX);
        $sn=md5( $sn0.time(),true );// true for binary output
      }
      $sn=base64_encode($sn);
      return($sn);
    }
    
    /**
     * If requested page has some parameters, saves them to SESSION.
     * On successfull registration there'll be redirect (the PRG pattern).
     * Commands for authentication itself (e.g. reg=reset) are stripped off.
     * @returns string full absolute Uri or empty if no redirect requred
     */
    static function makeRedirectUri() {
      $ru=$_SERVER["REQUEST_URI"];
      $matches=[];
      // look for ?reg=command or &reg=command
      $r1=preg_match("~\&(reg=[^&]*)~",$ru,$matches);
      if ( !$r1 ) $r2=preg_match("~\?(reg=[^&]*)~",$ru,$matches);
      if ( !empty($matches) ) {
        // remove reg=command
        $ru=str_replace($matches[1],"",$ru);
        // make sure uri is still ok
        if ( (strpos($ru,"?&"))!==false ) $ru=str_replace("?&","?",$ru);
        if ( ($p=strpos($ru,"&&"))!==false ) $ru=str_replace("&&","&",$ru);
        $ru=rtrim($ru,"&? ");
      }      
      $url = 'http://';
      if ( (array_key_exists("HTTPS",$_SERVER)) && $_SERVER['HTTPS'] ) $url = 'https://';
      $url .= $_SERVER['HTTP_HOST'];// Get the server
      $target=$url.$ru;
      return($target);
    }
    
    /**
     * Sets php.ini parametrs for session.
     * The only place for all those settings.
     * @param {object AuthRegistry} $ar
     * @returns void
     */
    static function startSession (AuthRegistry $ar) {
      ini_set("session.serialize_handler","php_serialize");
      ini_set("session.use_only_cookies",1);
      ini_set("session.use_cookies",1);
      ini_set("session.use_strict_mode",1);
      // my directory to store sessions
      $sessionsDir=realpath( __DIR__. '/../sessions');// absolute path required
      //echo($sessionsDir);
      if ( !file_exists($sessionsDir) ) throw new AccessException ("Sessions directory ".$sessionsDir." not found");
      if ( !is_writable($sessionsDir) ) throw new AccessException ("Sessions directory ".$sessionsDir." is not writable, check the permissions");
      ini_set('session.save_path',$sessionsDir);
      // probability of GarbageCollector check
      ini_set('session.gc_probability',100);
      // max interval between sessions
      ini_set("session.gc_maxlifetime", $ar->g("maxTimeoutGcCookie"));
      // cookie lifetime, if not prolonged by session_regenerate_id
      ini_set("session.cookie_lifetime", $ar->g("maxTimeoutGcCookie"));
      session_start();
    }

    /**
     * Displays the registration form.
     * Also stores original request uri to SESSION and generates serverNonce.
     * @uses templates/AuthElements
     * @uses templates/SubAuthElements
     * @uses templates/AuthForm.php
     * @param {object AuthRegistry} $ar
     * @param string $authMessage message to display in the form
     * @return nothing
     */
    static function showAuthForm (AuthRegistry $ar, $authMessage="") {
      $ar->s("alert",$authMessage);
      //$ar->s("serverNonce",$sn);// needed by form
      require($ar->g("templatePath")."AuthElements.php");
      require($ar->g("templatePath")."SubAuthElements.php");
      $formSelect= [ 0=>"PlainAuthElements",
                     1=>"OpportunisticAuthElements",
                     2=>"StrictAuthElements" ];
      $ar->s( "controlsClass", $formSelect[$ar->g("authMode")] );
      include($ar->g("templatePath")."authForm.php");     
    }
    
    /**
     * Displays simple alert message, formatted as the registration form.
     * @uses templates/AuthElements
     * @uses templates/SubAuthElements
     * @uses templates/AuthForm.php
     * @param {object AuthRegistry} $ar
     * @param string $authMessage message to display in the form
     * @return nothing
     */    
    static function showAuthAlert (AuthRegistry $ar, $authMessage="") {
      $ar->s("alert",$authMessage);
      require($ar->g("templatePath")."AuthElements.php");
      require($ar->g("templatePath")."SubAuthElements.php");
      $ar->s( "controlsClass", "AlertAuthElements" );
      include($ar->g("templatePath")."authForm.php");
    }
    
    
    /**
     * Controller top level. Initializations and command/state logic.
     * @uses Applicant
     * @see LTforum/AccessController_table.rtf for command/state table
     * @param {object AuthRegistry} $ar
     * @return true on success, false or message on success/redirect, false or error message on failure
     */
    public function go (AuthRegistry $ar) {
      $note="";
      $pauseFail=rand(10,20);
      $pauseAllow=3;//rand(2,5);
      
      self::startSession($ar);
      if ( array_key_exists("reg",$_REQUEST) ) { $ar->s("reg",$_REQUEST["reg"]); }
      $a=new Applicant($ar);
      $a->initMin($ar);
      
      switch ($ar->g("reg")) {
      
      case "reset":
        if ( $a->statusEquals("active") || $a->statusEquals("postAuth") ) {
          $a->setStatus("zero");
          $note="Session reset by user";
          session_regenerate_id(true);
        }
        // any state > preAuth
        $a->demandReg($note,"preAuth");
        return(false);
        
      case "deact":
        if ( $a->statusEquals("active") || $a->statusEquals("postAuth") ) {
          $note="Session reset by user";
          session_regenerate_id(true);// important!: "true" to destroy old session
          // postAuth state is bound to realm
          $ret=$a->checkRealm($ar);
          if ($ret!==true) {
            // Error: reg=deact with wrong realm 
            $note=$ret;
            $a->setStatus("zero");
          }
          if ( $a->statusEquals("active") || $a->statusEquals("postAuth") ) { 
            $nextStatus="postAuth";
          }
          else { $nextStatus="preAuth"; }
          // active, postAuth > postAuth   
          $a->demandReg($note,$nextStatus);
          return(false);
        }
        if ( empty($_SESSION) || $a->statusEquals("zero") || $a->statusEquals("preAuth") ) {
          // same as reg=reset
          $a->demandReg("","preAuth");
          return(false);          
        }
        throw new UsageException ("Wrong Command/State reg=".$ar->g("reg")."/".$a->getStatus()."!");

      case "authPlain":
      case "authOpp":
      case "authDigest":
        if ( $a->statusEquals("active") ) {
          // error, possibly an attack
          // active > zero > preAuth
          session_regenerate_id(true);
          $a->setStatus("zero");
          // fall-through
        }
        if ( empty($_SESSION) || $a->statusEquals("zero") ) {
          // this also should not happen normally
          // zero > preAuth
          $a->demandReg("A wrong-timed request","preAuth");
          return(false);          
        }
        if ( $a->statusEquals("preAuth") || $a->statusEquals("postAuth") ) {
          $ret=$a->checkSessionKeys(["notBefore","activeUntil"],false);
          if ( $ret===true ) $ret=$a->checkActiveUntil();
          if ( $ret!==true ) {
            // preAuth or postAuth > preAuth
            $a->demandReg($ret,"preAuth");
            return(false);
          } 
          $ret=$a->checkNotBefore();
          if ( $ret!==true ) {
            self::showAuthAlert($ar,"Please, wait a few seconds and refresh the page");
            //$a->setStatus("noChange");
            return(false);
          }

          // additional initializations
          $ar->readInput($_REQUEST);
          $ar->readSession();
          $a->initFull($ar);
          // pre-registration checks and registration processaing
          $ret=$a->beforeAuth($ar);
          if ( $ret===true ) $ret=$a->processAuth($ar);
          if ( $ret!==true ) {
            // preAuth or postAuth > preAuth
            sleep($pauseFail);
            $a->demandReg($ret,"preAuth");
            return(false);
          }
          // successful registration
          // preAuth or postAuth > active
          if ( $a->statusEquals("preAuth") ) { 
            session_regenerate_id(true);
            $a->markCookieTime();
          }
          // on postAuth > active regeneration is skipped as there's keepCookie in postAuth checks
          sleep($pauseAllow);
          $a->setTimeLimits( 0,$ar->g("maxDelayPage") );
          $a->setStatus("active");
          // redirect ?
          if ( $a->optionalRedirect() ) { return (false); }// header has been sent
          // continue to main controller
          return (true);
        }
        throw new UsageException ("Wrong Command/State reg=".$ar->g("reg")."/".$a->getStatus()."!");

      case "":
        if ( empty($_SESSION) || $a->statusEquals("preAuth") ) {
          $a->demandReg("","preAuth");
          return(false);
        }
        if ( $a->statusEquals("active") ) {
          $ret = $a->checkActiveParams();
          if ( $ret===true ) $ret = $a->checkRealm($ar);
          if ( $ret===true ) $ret = $a->checkAdmin($ar);
          if ( $ret!==true ) {
            // active > preAuth
            $a->demandReg($ret,"preAuth");
            return(false);
          }         
          $ret = $a->checkNotBefore();
          if ( $ret!==true ) {
            self::showAuthAlert($ar,"Please, wait a few seconds and refresh the page");
            //$a->setStatus("active");
            return(false);
          }
          $ret = $a->checkActiveUntil();
          if ( $ret!==true ) {
            // active > postAuth
            $note=$a->getUnanswered();
            session_regenerate_id();
            $a->markCookieTime();
            $a->demandReg($note,"postAuth");
            return(false);
          }
          // happy end
          // continue to main controller
          $a->setTimeLimits( 0,$ar->g("maxDelayPage") );
          //$a->setStatus("active");
          return (true);
        }
        if ( $a->statusEquals("postAuth") ) {
          $ret = $a->checkRealm($ar);
          if ($ret===true) {
            // postAuth > postAuth
            $note=$a->getUnanswered();
            $a->keepCookie();// regenerate cookie and id if session is aged (more than 1 day typically)
            $a->demandReg($note,"postAuth");
            return(false);
          }
          else {
            // different realm : interpreted as request to a fresh new registration
            // postAuth > preAuth
            $a->setStatus("zero");
            session_regenerate_id();
            $a->demandReg($ret,"preAuth");
            return(false);
          }                   
        }
        throw new UsageException ("Wrong Command/State reg=".$ar->g("reg")."/".$a->getStatus()."!");

      default:
        self::showAuthAlert($ar,"Wrong command reg=".$ar->g("reg")."!");
        return (false);
      }// end switch
    
    }// end go
  }// end AccessController  