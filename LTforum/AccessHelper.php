<?php
/**
 * @pakage LTforum
 * @version 1.2 added SessionManager
 */

/**
 * A library of utilities/code fragments for AccessController, Applicant and UserManager.
 */
class AccessHelper {

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
    if ( isset($_SERVER["SERVER_NAME"]) ) $serverName=$_SERVER["SERVER_NAME"];
    else $serverName="ltforum";// as set in test environment, normally "localhost";
    $realm=strtolower($serverName).$realm;
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
    $s.="[".$forum."Visitors]".$nl;
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
    if ($context) $targetPath=$context->g("targetPath");
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
  
  static function removeExpiredVisitors($realm,$context) {
    $targetPath="";
    if ($context) $targetPath=$context->g("targetPath");
    $groupFile=$targetPath.self::$groupFileName;
    if ( ! file_exists($groupFile) ) {
      return;
    }
    $buf=file_get_contents($groupFile);
    $parsed=parse_ini_string($buf,true);
    $rv=$realm."Visitors";
    if( ! array_key_exists($rv,$parsed)) return;
    $visitorsList=$parsed[$rv];
    //print_r($visitorsList);
    $expiredList=[];
    $t=time();
    foreach($visitorsList as $visitor=>$deadline) {
      if($t > $deadline) $expiredList[]=$visitor;
    }
    if (empty($expiredList)) return;
    
    $nl="\n";
    $buf=explode($nl,$buf);
    $i=0;
    $l=count($buf);
    for ($i=0; $i<$l; $i++) {
      foreach($expiredList as $name) {
        if(strpos($buf[$i], $name."=")===0) {
          unset($buf[$i]);
          break;
        }
      }
    }
    
    $buf=implode($nl,$buf);
    $parsed=parse_ini_string($buf,true);
    $userList=$parsed[$realm];
    if (count($userList)==0) throw new UsageException ("Cannot remove all users");
    $adminsList=$parsed[$realm."Admins"];
    if (count($adminsList)==0) throw new UsageException ("Cannot remove all admins");
    file_put_contents($groupFile,$buf);    
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
   * Checks if there are new messages since the latest current user's message.
   * Main feature of the postAuth state.
   * @return string|Array notification
   */
  public static function getUnanswered($name, AuthRegistry $context) {
    if(empty($name)) return("-");
    if ( ! class_exists("CardfileSqlt") ) throw new UsageException ("Some dependency is missing");
    $dbFile = $context->g("targetPath").$context->g("realm"); 
    $dbm=new CardfileSqlt($dbFile,false);

    $found=$dbm->getLastMsgByAuthor($name);
    if ( ! $found) { // no messages by this user in this forum
      return("No messages by this user");
    }
    // check if the latest message is authored by this user
    $last=$dbm->getLastMsg();
    $unanswered = $last["id"] - $found["id"];
    if ( $unanswered ) {
      $note=[ "Unanswered: %s, latest: %s at %s", $unanswered, $last["date"], $last["time"] ];  
    }
    else $note="Unanswered: 0"; 
    //$dbm->destroy();
    return ($note);
  }

  // ----- Interface methods for AccessController  -----
  
  // --- interface to session control functions ---
  
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
   * Sets $_SESSION to empty.
   * @param array $session output! used by overriding methods
   * @return nothing
   */
  static function nullifySession (&$session) {
    session_destroy();
    session_start();
  }
  
  /**
   * Wraps native php function.
   */
  static function regenerateId() {
    session_regenerate_id(true);
  }
  
  /**
   * Writes the array argument to SESSION without changing SESSION pointer. Currently unused.
   */
  static function writeSession($arr) {
    // $var=$_SESSION; $var[$key]="value" sets value only in $var, not in $_SESSION
    // no session_write_close() anywhere before this !!!
    echo(" Copying session ");
    $sk=array_keys($_SESSION);
    foreach ($sk as $k) { unset($_SESSION[$k]); } 
    $as=$a->session;
    foreach ( $as as $k=>$v ) {
      $_SESSION[$k]=$v;
    }
  }
  
  // --- interface to Header ---
  /**
    * If requested page has some parameters, saves them to SESSION.
    * On successfull registration there'll be redirect (the PRG pattern).
    * Commands for authentication itself (e.g. reg=reset) are stripped off.
    * @returns string full absolute Uri or empty if no redirect requred
    */
  static function makeRedirectUri(AuthRegistry $context) {
    $ru=$_SERVER["REQUEST_URI"];
    $matches=[];
    // look for ?reg=command or &reg=command
    $r1=preg_match("~\&(reg=[^&]*)~",$ru,$matches);
    if ( !$r1 ) $r2=preg_match("~\?(reg=[^&]*)~",$ru,$matches);
    //print("Matches:"); print_r($matches);
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
   * Sends Location header.
   * @param string $targetUri
   * @return nothing
   */
  static function sendRedirect($targetUri) {
    header( "Location: ".$targetUri );
  }
  
  // --- interface to View ---
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
    require_once($ar->g("templatePath")."AuthElements.php");
    require_once($ar->g("templatePath")."SubAuthElements.php");
    $formSelect= [
      0=>"PlainAuthElements",
      1=>"OpportunisticAuthElements",
      2=>"StrictAuthElements"
    ];
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
    require_once($ar->g("templatePath")."AuthElements.php");
    require_once($ar->g("templatePath")."SubAuthElements.php");
    $ar->s( "controlsClass", "AlertAuthElements" );
    include($ar->g("templatePath")."authForm.php");
  }
  
  /**
   * Checks forum command (including default empty command, meaning "view") against guest-allowed commands.
   * @param {object AuthRegistry} $ar
   * @return true if allowed, false if mot allowed
   */
  static function tryPassAsGuest(AuthRegistry $ar) {
    $allowedCommands=explode(",",$ar->g("guestsAllowed"));
    if (in_array("view",$allowedCommands)) $allowedCommands[]="";
    //echo("act={$ar->g("act")} ");
    if (in_array($ar->g("act"),$allowedCommands)) return true;
    return false;    
  }
  
}

/**
 * A class for unit testing the AccessController class.
 * Overrides its interface methods.
 */
class DetachedAccessHelper extends AccessHelper {
  // ----- Interface methods for AccessController  -----
  // --- interface to session control functions ---

  static function startSession (AuthRegistry $ar) {}
  
  static function nullifySession (&$session) {
    //echo(" Clearing my session array ");
    $session=[];
  }
    
  static function regenerateId() {
    $ar=AuthRegistry::getInstance();
    $ar->trace("reg_id");
  }
  
  // --- interface to Header ---
  
  static function makeRedirectUri (AuthRegistry $context) {
    $reg=$context->g("reg");
    $act=$context->g("act");
    $target="?";
    if ( $act ) $target.="act=".$act;
    return($target);
  }
  
  static function sendRedirect($targetUri) {
    $ar=AuthRegistry::getInstance();
    $ar->trace("redirect");
  }
  
  // --- interface to View ---
  static function showAuthForm (AuthRegistry $ar, $authMessage="") {
    $ar->s("alert",$authMessage);
    //$ar->s("serverNonce",$sn);// needed by form
    require_once($ar->g("templatePath")."AuthElements.php");
    require_once($ar->g("templatePath")."SubAuthElements.php");
    $formSelect= [ 0=>"PlainAuthElements",
                    1=>"OpportunisticAuthElements",
                    2=>"StrictAuthElements" ];
    //$ar->s( "controlsClass", $formSelect[$ar->g("authMode")] );
    $cc = $formSelect[$ar->g("authMode")];
    echo ( "Page:".$cc::titleSuffix($ar)."\n" );
    //include($ar->g("templatePath")."authForm.php");
  }

  static function showAuthAlert (AuthRegistry $ar, $authMessage="") {
    $ar->s("alert",$authMessage);
    require_once($ar->g("templatePath")."AuthElements.php");
    require_once($ar->g("templatePath")."SubAuthElements.php");
    echo ( "Page:".AlertAuthElements::titleSuffix($ar)."\n" );
    //$ar->s( "controlsClass", "AlertAuthElements" );
    //include($ar->g("templatePath")."authForm.php");
  }
}
