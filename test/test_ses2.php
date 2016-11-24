<?php
  require_once("../LTforum/AssocArrayWrapper.php");
  require_once ("../LTforum/MyExceptions.php");  
  
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
  
  class SessionRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
  }
  $sr=SessionRegistry::getInstance(0,["forum"=>"test"]);
  
  function makeHa1($userName,$realm,$password) {
    return ( md5($userName.$realm.$password) ); 
  }
  
  function makeResponse($sn,$ha1,$cn) {
    return ( md5($sn.$ha1.$cn) ); 
  }
  
  function iterateSecret($secret,$cNonce) {
    return ( md5($secret.$cNonce) );
  }
  
  function makeServerNonce() {
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
  
  function parseGroup($realm) {
    $groupFile=".ini";
    $parsed=parse_ini_file($groupFile,true);
    //print_r($parsed);
    if (!array_key_exists($realm,$parsed)) throw new AccessException ("Section ".$realm." not found in the file ".$groupFile);
    return($parsed[$realm]);
  }
  
  function isAdmin($user) {
    $admins=parseGroup("admins");
    if ( array_key_exists($user,$admins) ) return true;
    return false;
  }
  
  function addUser($userName,$realm,$password) {
    $name=@$_SESSION["authName"];
    if (!$name) throw new AccessException ("Cannot find name of the current user");
    /*$name=@$context->g("authName");
    if (!$name) $name=@$context->g("user");
    if (!$name) throw new AccessException ("Cannot find name of the current user");*/
    if ( !isAdmin($name) ) fail("You should be an Admin to do that");
    $nl="\n";
    $groupFile=".ini";
    $buf=file_get_contents($groupFile);
    $buf=str_replace("\r","",$buf);
    $beginSection=strpos($buf,"[".$realm."]");
    if ($beginSection===false) throw new AccessException ("Section ".$realm." not found in the file ".$groupFile);
    $ha=makeHa1($userName,$realm,$password);
    $entry=$userName."=".$ha.$nl;
    if (strpos($buf,$entry,$beginSection)!==false) fail("This entry already exists");
    $head=substr($buf,0,$beginSection+strlen($realm)+3);
    $tail=substr($buf,$beginSection+strlen($realm)+3);
    $buf=$head.$entry.$tail;
    file_put_contents($groupFile,$buf);
  }
  
  function delUser($userName,$realm,$password) {
    $name=@$_SESSION["authName"];
    if (!$name) throw new AccessException ("Cannot find name of the current user");
    if ( !isAdmin($name) ) fail("You should be an Admin to do that");
    if ( $userName==$name ) fail("You should not delete your own record"); 
    $nl="\n";
    $groupFile=".ini";
    $buf=file_get_contents($groupFile);
    $buf=str_replace("\r","",$buf);
    $section=strpos($buf,"[".$realm."]")-1;
    if ($section===false) throw new AccessException ("Section ".$realm." not found in the file ".$groupFile);
    $ha=makeHa1($userName,$realm,$password);
    $entry=$userName."=".$ha.$nl;
    $where=strpos($buf,$entry,$section);
    if($where===false) return ("Missing or invalid entry. Try manual editing");
    $after=@substr($buf,$where+strlen($entry),1);
    $before=@substr($buf,$where-2,1);
    echo(" before:".$before."; after:".$after."; ");
    if( ($before=="]" || $before===false) && ($after=="[" || $after===false ) ) return ("This seems to be the only record in that section. Create another one first");
    $head=substr($buf,0,$where);
    $tail=substr($buf,$where+strlen($entry));
    $buf=$head.$tail;
    file_put_contents($groupFile,$buf);
    return(true);
  }



  require ("Hopper.php");
  
  class SessionManager extends Hopper {
    function __construct() {
      $this->nextState="verifySession";
    }
    
    function verifySession(AuthRegistry $ar) {
      ini_set("session.serialize_handler","php_serialize");
      ini_set("session.use_only_cookies",1);
      ini_set("session.use_cookies",1);
      ini_set("session.use_strict_mode",1);
      session_start();
    
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
      if ( $t < $_SESSION["notBefore"] ) return ("Please, try again later");
      if ( $_SESSION["state"]=="preAuth" ) {
        $this->next("selectAuth");
        return;
      }
      if ( !array_key_exists("authName",$_SESSION) || $_SESSION["state"]!="auth" ) return ("Something is wrong");
      return (true);
    }
    
    function requestLogin(AuthRegistry $ar) { 
      // initialize authentication
      $sn=makeServerNonce();
      $ar->s("serverNonce",$sn);// needed by form
  
      //$_SESSION["registry"]=$ar->export();
      $_SESSION["serverNonce"]=$sn;
      $_SESSION["notBefore"]=time()+$ar->g("minDelay");
      $_SESSION["notAfter"]=time()+$ar->g("maxDelayAuth");
      $_SESSION["state"]="preAuth";
      session_write_close ();
      //$ar->s("alert",session_id());
      // show form
      require("AuthElements.php");
      require("SubAuthElements.php");
      $formSelect=[0=>"PlainAuthElements",1=>"OpportunisticAuthElements",2=>"StrictAuthElements"];
      $ar->s( "controlsClass", $formSelect[$ar->g("authMode")] );
      include("authForm.php");
      $this->setBreak();// overly safe
      return(false);
    }
    
    function selectAuth (AuthRegistry $ar) {
      $ar->readInput($_REQUEST);
      $ar->readSession();
      //$ar=AuthRegistry::getInstance(0,[]);
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
    
      echo("Trying auth plaintext ");
      $realm=$ar->g("realm");
      $applicantName=$ar->g("user");
      $applicantPsw=$ar->g("ps");
      $applicantHa=makeHa1($applicantName,$realm,$applicantPsw);
      //echo(">".makeHa1($_REQUEST["user"],$ar->g("realm"),$_REQUEST["ps"])."<");// DEBUG  
      $foundName="";
      $users=parseGroup($ar->g("realm"));
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
    
      echo(" Trying JS digest authentication ");
      $foundName="";
      $users=parseGroup($ar->g("realm"));
      //print_r($users);
      //echo("sn>".$sn);
      
      // hash -> proposed responce -> check -> user name
      // so no need to send name in open
      foreach ($users as $name=>$ha) {
        $tryResponse=makeResponse( $ar->g("serverNonce"), $ha, $ar->g("cn") );
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
      $ar->s("secret",iterateSecret($ha,$applicantNonce));
      $ar->s("clientCount",1);
      $ar->s("serverCount",1);  
      $ar->s("alert","Digest authentication OK as ".$foundName);
      $_SESSION["registry"]=$ar->exportToSession();// JS secrets
      
      $this->next("authSuccess");
    }
    
    function authSuccess(AuthRegistry $ar) {
      $_SESSION["authName"]=$ar->g("authName");
      $_SESSION["notAfter"]=time()+$ar->g("maxDelayPage");
      $_SESSION["state"]="auth";
      unset($_SESSION["serverNonce"]);
      session_regenerate_id();
      session_write_close ();
      return (true);
    }
  }
  

  //$authMode=1; // 0 - plain text, 1 - JS digest or plain text, 2 - only JS digest
  //$outcome="Started... ";
  //$realm="test";

  print_r($_REQUEST);
  print ("----");
  session_start();
  print_r($_SESSION);
  
  if ( array_key_exists("act",$_REQUEST) && $_REQUEST["act"]=="r" ) {
    unset($_SESSION);
    session_destroy();
    session_start();
  }
  
  $ar=AuthRegistry::getInstance(1, ["realm"=>"test", "authName"=>"", "serverNonce"=>"",  "serverCount"=>0, "clientCount"=>0, "secret"=>"", "authMode"=>1, "minDelay"=>3, "maxDelayAuth"=>300, "maxDelayPage"=>3600, "act"=>"", "user"=>"", "ps"=>"", "cn"=>"", "responce"=>"", "plain"=>"", "pers"=>"", "alert"=>"", "controlsClass"=>"" ] );
  $sm=new SessionManager;
  $ret=$sm->go($ar);
  echo("\r\nTrace: ".$sm->trace." ");

  //if ( $alert=$ar->g("alert") ) echo($alert);
  if($ret===false) exit;
  if($ret!==true) exit($ret);
  

   if (empty($_SESSION['count'])) {
     $_SESSION['count'] = 1;
   } else {
     $_SESSION['count']++;
   }
   
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="ru" xml:lang="ru">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>Test PHP sessions</title>
  <link rel="stylesheet" type="text/css" href="" media="all" />
</head>
<body>
<p>Outcome: <?php print($ar->g("alert")); ?>, Counter: <?php print($_SESSION['count']); ?>, ID: <?php print(session_id()); ?></p>
</body>
</html>
  