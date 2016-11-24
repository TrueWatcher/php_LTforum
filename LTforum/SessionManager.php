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
      $what=["act","user","ps","cn","responce","plain","pers"];
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

  class SessionManager extends Hopper {
    function __construct() {
      $this->nextState="verifySession";
    }
    
    static function makeHa1($userName,$realm,$password) {
      return ( md5($userName.$realm.$password) ); 
    }
    
    static function makeResponse($sn,$ha1,$cn) {
      return ( md5($sn.$ha1.$cn) ); 
    }
    
    static function iterateSecret($secret,$cNonce) {
      return ( md5($secret.$cNonce) );
    }
    
    static function parseGroup($realm,$context) {
      $targetPath="";
      if($context) $targetPath=$context->g("targetPath");
      $groupFile=$targetPath.".ini";
      $parsed=parse_ini_file($groupFile,true);
      //print_r($parsed);
      if (!array_key_exists($realm,$parsed)) throw new AccessException ("Section ".$realm." not found in the file ".$groupFile);
      return($parsed[$realm]);
    }
    
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
    
    function isAdmin($user,$context) {
      $admins=self::parseGroup("admins",$context);
      //print_r($admins);
      if ( array_key_exists($user,$admins) ) return (1);
      return (0);
    }
    
    function verifySession(AuthRegistry $ar) {
      ini_set("session.serialize_handler","php_serialize");
      ini_set("session.use_only_cookies",1);
      ini_set("session.use_cookies",1);
      ini_set("session.use_strict_mode",1);
      session_start();
      
      if ( array_key_exists("act",$_REQUEST) && $_REQUEST["act"]=="reset" ) {
        unset($_SESSION);
        session_destroy();
        session_start();
        $ar->s("alert"," Session aborted by user ");
        //$this->next("requestLogin");
        //return;      
      }
      if( empty($_SESSION) || !array_key_exists("state",$_SESSION) ) {
        $this->next("requestLogin");
        return;
      }
      $t=time();
      if ( empty($_SESSION["notBefore"]) || empty($_SESSION["notAfter"]) || $t > $_SESSION["notAfter"] ) {
        $_SESSION["state"]="junk";
        $this->next("requestLogin");
        return;      
      }
      if ( array_key_exists("realm",$_SESSION) && $_SESSION["realm"]!==$ar->g("realm") ) {
        $_SESSION["state"]="trip";
        $this->next("requestLogin");
        return;      
      }
      if ( $t < $_SESSION["notBefore"] ) return ("Please, try again later");
      if ( $_SESSION["state"]=="preAuth" ) {
        $this->next("selectAuth");
        return;
      }
      if ( !array_key_exists("authName",$_SESSION) || $_SESSION["state"]!="auth" ) return ("Something is wrong");
      $user=$_SESSION["authName"];
      //echo ($user."====".(self::isAdmin($user)) );
      if ( $ar->g("admin") && !(self::isAdmin($user,$ar)) ) return ("This area is for admins only");
      return (true);
    }
    
    function requestLogin(AuthRegistry $ar) { 
      // initialize authentication
      $sn=self::makeServerNonce();
      $ar->s("serverNonce",$sn);// needed by form
  
      //$_SESSION["registry"]=$ar->export();
      $_SESSION["serverNonce"]=$sn;
      $_SESSION["notBefore"]=time()+$ar->g("minDelay");
      $_SESSION["notAfter"]=time()+$ar->g("maxDelayAuth");
      $_SESSION["state"]="preAuth";
      session_write_close ();
      //$ar->s("alert",session_id());
      // show form
      require($ar->g("templatePath")."AuthElements.php");
      require($ar->g("templatePath")."SubAuthElements.php");
      $formSelect=[0=>"PlainAuthElements",1=>"OpportunisticAuthElements",2=>"StrictAuthElements"];
      $ar->s( "controlsClass", $formSelect[$ar->g("authMode")] );
      include($ar->g("templatePath")."authForm.php");
      $this->setBreak();// overly safe
      return(false);
    }
    
    function selectAuth (AuthRegistry $ar) {
      $ar->readInput($_REQUEST);
      $ar->readSession();
      if ( !($ar->g("act")=="authPlain" || $ar->g("act")=="authOpp" || $ar->g("act")=="authJs") ) {
        // only authentication should happen in preAuth state, so reset session
        $ar->s("alert"," Out-of-order request was discarded ");
        $this->next("requestLogin");
        return;      
      }
      $tryPlainText=( $ar->g("act")=="authPlain" || ( $ar->g("act")=="authOpp" && $ar->g("plain") ) );
      $tryDigest=( $ar->g("act")=="authJs" || ($ar->g("act")=="authOpp" && !$ar->g("plain") ) );
      if ( !$tryPlainText && !$tryDigest ) {
        // something strange
        $ar->s("alert"," Out-of-order request was discarded ");
        $this->next("requestLogin");
        return;      
      }
      // init authentication
      if ($tryPlainText) {
        if ( $ar->g("authMode") == 2 ) {
          $this->setBreak();// overly safe      
          return("Plaintext auth is turned off on the server");
        }
        $this->next("authPlain");
        return;       
      }
      else { // tryDigest
        if ( $ar->g("authMode") == 0 ) {
          $this->setBreak();// overly safe      
          return(" Digest auth is turned off on the server");
        }
        if ( $ar->g("user") || $ar->g("ps") ) {
          $this->next("requestLogin");
          $ar->s("alert"," This mode takes no credentials ");
        return;        
      }
        $this->next("authDigest");
        return;       
      }
    }
    
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
    
    function authDigest(AuthRegistry $ar) {
    
      //echo(" Trying JS digest authentication ");
      $foundName="";
      $users=self::parseGroup($ar->g("realm"),$ar);
      //print_r($users);
      //echo("sn>".$sn);
      
      // hash -> proposed responce -> check -> user name
      // so no need to send name in open
      foreach ($users as $name=>$ha) {
        $tryResponse=self::makeResponse( $ar->g("serverNonce"), $ha, $ar->g("cn") );
        if ( $tryResponse == $ar->g("responce") ) {
          $foundName=$name;
          break;
        }
      }
      if ( !$foundName ) {
        $this->next("requestLogin");
        sleep(5);
        $ar->s("alert","Wrong login or/and password");
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
    
    function authSuccess(AuthRegistry $ar) {
      if ( $ar->g("admin") && !self::isAdmin($ar->g("authName"),$ar ) ) {
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
      session_write_close ();
      return (true);
    }
  }// end SessionManager
?>