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

  class AccessController extends Hopper {

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
      file_put_contents( $path.self::$groupFileName, $s);
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
     * Checks if the given user has admin rights.
     * @uses parseGroup
     * @param string $user user name
     * @param {object AuthRegistry} $context
     * @returns int 0 or 1
     */
    function isAdmin($user,$context) {
      $admins=self::parseGroup( $context->g("realm")."Admins", $context );
      if ( array_key_exists($user,$admins) ) return (1);
      return (0);
    }

    /**
     * If requested page has some parameters, saves them to SESSION.
     * On successfull registration there'll be redirect (the PRG pattern).
     * Commands for authentication itself (e.g. reg=reset) are stripped off.
     * @returns string full absolute Uri or empty if no redirect requred
     */
    function makeRedirectUri() {
      $ru=$_SERVER["REQUEST_URI"];
      $matches=[];
      $r1=preg_match("~\&(reg=[^&]*)~",$ru,$matches);
      if ( !$r1 ) $r2=preg_match("~\?(reg=[^&]*)~",$ru,$matches);
      if ( !empty($matches) ) {
        $ru=str_replace($matches[1],"",$ru);
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
     * @param {object AuthRegistry} $context
     * @returns void
     */
    function iniSet(AuthRegistry $ar) {
      ini_set("session.serialize_handler","php_serialize");
      ini_set("session.use_only_cookies",1);
      ini_set("session.use_cookies",1);
      ini_set("session.use_strict_mode",1);
      $sessionsDir=realpath( __DIR__. '/../sessions');// absolute path required
      //echo($sessionsDir);
      if ( !file_exists($sessionsDir) ) throw new AccessException ("Sessions directory ".$sessionsDir." not found");
      if ( !is_writable($sessionsDir) ) throw new AccessException ("Sessions directory ".$sessionsDir." is not writable, check the permissions");
      ini_set('session.save_path',$sessionsDir);
      ini_set('session.gc_probability',25);
      ini_set("session.gc_maxlifetime", $ar->g("maxDelayPage"));
    }

    // ----- Logical units to be called by Hopper class -----

    /**
     * Sets the first step for the Hopper.
     */
    function __construct() {
      $this->nextState="verifySession";
    }

    /**
     * Initial session checks.
     * If no authentication required, finishes the job;
     * otherwise passes to auth initialisation (requestLogin)
     * @param {object AuthRegistry} $context
     * @returns void
     */
    function verifySession (AuthRegistry $ar) {
      self::iniSet($ar);
      session_start();

      // check for a RESET command
      if ( array_key_exists("reg",$_REQUEST) && $_REQUEST["reg"]=="reset" ) {
        //unset($_SESSION);
        //session_destroy();
        //session_start();
        $_SESSION["state"]="aborted";
        $ar->s("alert"," Session aborted by user ");
        $this->next("requestLogin");
        return;
      }
      // check for an empty session
      if( empty($_SESSION) || !array_key_exists("state",$_SESSION) ) {
        $this->next("requestLogin");
        return;
      }
      // check for an outtimed session
      $t=time();
      if ( empty($_SESSION["notBefore"]) || empty($_SESSION["notAfter"]) || $t > $_SESSION["notAfter"] ) {
        $_SESSION["state"]="junk";
        $this->next("requestLogin");
        return;
      }
      // check for a forum/thread mismatch
      if ( array_key_exists("realm",$_SESSION) && $_SESSION["realm"]!==$ar->g("realm") ) {
        $_SESSION["state"]="trip";
        $ar->s("alert","You need to register for a new thread");
        $this->next("requestLogin");
        return;
      }
      // check for too fast responce (probably an attack)
      if ( $t < $_SESSION["notBefore"] ) {
        $ar->s("alert","Please, wait a few seconds and click \"Refresh\"");
        $this->next("showAuthAlert");
        return;
      }
      // check for the pre-Auth state
      if ( $_SESSION["state"]=="preAuth" ) {
        $this->next("selectAuth");
        return;
      }
      // check for other invalid sutuations
      if ( !array_key_exists("authName",$_SESSION) || $_SESSION["state"]!="auth" ) {
        $ar->s("alert","Something is wrong");
        $this->next("showAuthAlert");
        return;
      }
      $user=$_SESSION["authName"];
      //echo ($user."====".(self::isAdmin($user)) );
      // additional check for admin areas
      if ( $ar->g("isAdminArea") && !(self::isAdmin($user,$ar)) ) {
        $ar->s("alert","This area is for admins only");
        $this->next("requestLogin");
        return;
      }
      // happy end
      return (true);
    }

    /**
     * Initializes authentication params, stores them to SESSION, and presents auth form according to the requred mode.
     * Plaintext; opportunistic Digest; strict Digest
     * @param {object AuthRegistry} $context
     * @returns void
     */
    function requestLogin(AuthRegistry $ar) {
      if ($_SESSION) {
        unset($_SESSION);
        session_destroy();
        session_start();
      }
      // initialize authentication
      $sn=self::makeServerNonce();
      $ar->s("serverNonce",$sn);// needed by form

      //$_SESSION["registry"]=$ar->export();
      $_SESSION["serverNonce"]=$sn;
      $_SESSION["notBefore"]=time()+$ar->g("minDelay");
      $_SESSION["notAfter"]=time()+$ar->g("maxDelayAuth");
      $_SESSION["state"]="preAuth";
      if ( !empty($_SERVER["QUERY_STRING"]) ) $_SESSION["origUri"]=self::makeRedirectUri();
      //if ( $r=self::makeRedirectUri() ) $_SESSION["origUri"]=$r;
      //echo(">".$r);
      //exit();

      //session_write_close ();
      //$ar->s("alert",session_id());
      // show form
      require($ar->g("templatePath")."AuthElements.php");
      require($ar->g("templatePath")."SubAuthElements.php");
      $formSelect= [ 0=>"PlainAuthElements",
                     1=>"OpportunisticAuthElements",
                     2=>"StrictAuthElements" ];
      $ar->s( "controlsClass", $formSelect[$ar->g("authMode")] );
      include($ar->g("templatePath")."authForm.php");
      $this->setBreak();// overly safe
      return(false);
    }

    /**
     * Checks user form and selects the processing mode (PLaintext or Digest).
     * @param {object AuthRegistry} $context
     * @returns void
     */
    function selectAuth (AuthRegistry $ar) {
      $ar->readInput($_REQUEST);
      $ar->readSession();
      if ( !($ar->g("reg")=="authPlain" || $ar->g("reg")=="authOpp" || $ar->g("reg")=="authJs") ) {
        // only authentication should happen in preAuth state, so reset session
        $ar->s("alert"," Out-of-order request was discarded ");
        $this->next("requestLogin");
        return;
      }
      $tryPlainText=( $ar->g("reg")=="authPlain" || ( $ar->g("reg")=="authOpp" && $ar->g("plain") ) );
      $tryDigest=( $ar->g("reg")=="authJs" || ($ar->g("reg")=="authOpp" && !$ar->g("plain") ) );
      if ( !$tryPlainText && !$tryDigest ) {
        // something strange
        $ar->s("alert"," Out-of-order request was discarded ");
        $this->next("requestLogin");
        return;
      }
      // pre-authentication checks
      if ($tryPlainText) {
        if ( $ar->g("authMode") == 2 ) {
          $ar->s("alert"," Plaintext auth is turned off on the server ");
          $this->next("requestLogin");
          return;
        }
        if ( empty($ar->g("user")) || empty($ar->g("ps")) ) {
          $ar->s("alert"," Empty username or password ");
          $this->next("requestLogin");
          return;
        }
        $this->next("authPlain");
        return;
      }
      else { // tryDigest
        if ( $ar->g("authMode") == 0 ) {
          $ar->s("alert"," Digest auth is turned off on the server ");
          $this->next("requestLogin");
          return;
        }
        if ( $ar->g("user") || $ar->g("ps") ) {
          $ar->s("alert"," This mode takes no credentials ");
          $this->next("requestLogin");
          return;
        }
        if ( empty($ar->g("response")) || empty($ar->g("cn")) ) {
          $ar->s("alert"," Missing login data ");
          $this->next("requestLogin");
          return;
        }
        $this->next("authDigest");
        return;
      }
    }

    /**
     * Checks user form and performs PLaintext auth.
     * Passes to authSuccess on success or back to requestLogin on failure
     * @uses user name
     * @uses user password
     * @uses self::parseGroup
     * @uses self::makeHa1
     * @param {object AuthRegistry} $context
     * @returns void
     */
    function authPlain(AuthRegistry $ar) {

      //echo("Trying auth plaintext ");
      $realm=$ar->g("realm");
      $applicantName=$ar->g("user");
      $applicantPsw=$ar->g("ps");
      $applicantHa=self::makeHa1($applicantName,$realm,$applicantPsw);
      //echo(">".makeHa1($_REQUEST["user"],$ar->g("realm"),$_REQUEST["ps"])."<");// DEBUG
      $foundName="";
      $users=self::parseGroup($ar->g("realm"),$ar);
      // simply use array as dictionary
      if ( array_key_exists($applicantName,$users) && $users[$applicantName]===$applicantHa ) $foundName=$applicantName;
      if ( !$foundName ) {// fail
        $this->next("requestLogin");
        sleep(rand(5,10));
        $ar->s("alert","Wrong login or/and password");
        return;
      }
      // success
      $_SESSION["authName"]=$foundName;
      $ar->s("authName",$foundName);
      $ar->s("alert","Plaintext authentication OK as ".$foundName);
      $this->next("authSuccess");
    }

    /**
     * Checks user form and performs Digest auth.
     * Passes to authSuccess on success or back to requestLogin on failure
     * @uses client nonce
     * @uses client response
     * @uses self::parseGroup
     * @uses self::makeResponse
     * @param {object AuthRegistry} $context
     * @returns void
     */
    function authDigest(AuthRegistry $ar) {

      //echo(" Trying JS digest authentication ");
      $foundName="";
      $users=self::parseGroup($ar->g("realm"),$ar);
      //print_r($users);
      //echo("sn>".$sn);

      // Cycle: hash -> proposed response -> check -> user name
      // so no need to send name in open
      foreach ($users as $name=>$ha) {
        $tryResponse=self::makeResponse( $ar->g("serverNonce"), $ha, $ar->g("cn") );
        if ( $tryResponse == $ar->g("response") ) {
          $foundName=$name;
          break;
        }
      }
      if ( !$foundName ) {
        $this->next("requestLogin");
        sleep(5);
        $ar->s("alert","Wrong login or/and password "/*." response=".$ar->g("response")*/);
        return;
      }
      // registered OK
      $ar->s("authName",$foundName);
      $ar->s( "secret",self::iterateSecret( $ha, $ar->g("cn") ) );
      $ar->s("clientCount",1);
      $ar->s("serverCount",1);
      $ar->s("alert","Digest authentication OK as ".$foundName);
      $_SESSION["registry"]=$ar->exportToSession();// JS secrets

      $this->next("authSuccess");
    }

    /**
     * Proceeds after-authentication affairs.
     * If adminArea flag is set, checks additionally for admin rights.
     * Fills SESSION data for verifySession
     * If redirect is preset, makes it.
     * @param {object AuthRegistry} $context
     * @returns mixed void on adminAuth fail, some message on redirect, true on no-redirect finish (fall-through to main Controller)
     */
    function authSuccess(AuthRegistry $ar) {
      if ( $ar->g("isAdminArea") && !self::isAdmin($ar->g("authName"),$ar ) ) {
        $this->next("requestLogin");
        $ar->s("alert","This is a restricted area");
        return;
      }
      $_SESSION["authName"]=$ar->g("authName");
      $_SESSION["realm"]=$ar->g("realm");
      $_SESSION["notAfter"]=time()+$ar->g("maxDelayPage");
      $_SESSION["state"]="auth";
      unset($_SESSION["serverNonce"]);
      session_regenerate_id();
      //session_write_close ();
      if ( /*false &&*/ array_key_exists("origUri",$_SESSION) ) {
        $r=$_SESSION["origUri"];
        unset($_SESSION["origUri"]);
        header( "Location: ".$r );
        //echo ( "redirected to ".$r );
        $this->setBreak();
        return ( "redirected to ".$r );
      }
      return (true);
    }

    /**
     * Displays page with one message and passes to finished state.
     * @param {object AuthRegistry} $context
     * @returns false
     */
    function showAuthAlert(AuthRegistry $ar) {
      // show form
      require($ar->g("templatePath")."AuthElements.php");
      require($ar->g("templatePath")."SubAuthElements.php");
      $ar->s( "controlsClass", "AlertAuthElements" );
      include($ar->g("templatePath")."authForm.php");
      $this->setBreak();// overly safe
      return (false);
    }

  }// end SessionManager
?>