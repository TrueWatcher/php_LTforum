<?php
  require_once("../LTforum/AssocArrayWrapper.php");
  require_once ("../LTforum/MyExceptions.php");  
  
  class AuthRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
  }
  
  /*class SessionRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
  }
  $sr=SessionRegistry::getInstance(0,["forum"=>"myForum"]);*/
  
  function checkTime() {
    if ( empty($_SESSION["notBefore"]) || empty($_SESSION["notAfter"]) ) return("Authentication without active session");
    $t=time();
    if ( $t < $_SESSION["notBefore"] || $t > $_SESSION["notAfter"] ) return("Too fast or too slow submission");
    return(true);
  }
  
  function fail($message) {
    session_destroy();
    exit("Authentication failed: ".$message);
  }
  
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
    $groupFile=".group";
    $parsed=parse_ini_file($groupFile,true);
    //print_r($parsed);
    if (!array_key_exists($realm,$parsed)) throw new AccessException ("Section ".$realm." not found in the file ".$groupFile);
    return($parsed[$realm]);
  }
  
  function addUser($userName,$realm,$password) {
    $nl="\n";
    $groupFile=".group";
    $buf=file_get_contents($groupFile);
    $buf=str_replace("\r","",$buf);
    $beginSection=strpos($buf,"[".$realm."]");
    if ($beginSection===false) throw new AccessException ("Section ".$realm." not found in the file ".$groupFile);
    $ha=makeHa1($userName,$realm,$password);
    $entry=$ha."=".$userName.$nl;
    $head=substr($buf,0,$beginSection+strlen($realm)+3);
    $tail=substr($buf,$beginSection+strlen($realm)+3);
    $buf=$head.$entry.$tail;
    file_put_contents($groupFile,$buf);
  }
  
  function delUser($userName,$realm,$password) {
    $nl="\n";
    $groupFile=".group";
    $buf=file_get_contents($groupFile);
    $buf=str_replace("\r","",$buf);
    $section=strpos($buf,"[".$realm."]")-1;
    if ($section===false) throw new AccessException ("Section ".$realm." not found in the file ".$groupFile);
    $ha=makeHa1($userName,$realm,$password);
    $entry=$ha."=".$userName.$nl;
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

$authMode=1; // 0 - plain text, 1 - JS digest or plain text, 2 - only JS digest
$outcome="Started... ";
  
$realm="test";
/*$u1="Me";
$p1="1234";
$ha1_1=md5($u1.$realm.$p1);
$u2="test";
$p2="q";
$ha1_2=md5($u2.$realm.$p2);
$users=[ $ha1_1=>$u1, $ha1_2=>$u2 ];*/

print_r($_REQUEST);
ini_set("session.serialize_handler","php_serialize");
session_start();

if ( array_key_exists("act",$_REQUEST) && $_REQUEST["act"]=="r" ) {
  unset($_SESSION);
  session_destroy();
  session_start();
}

print_r($_SESSION);

if ( array_key_exists("act",$_REQUEST) && ($_REQUEST["act"]=="au" || $_REQUEST["act"]=="du" ) ) {
  $user=$_REQUEST["user"];
  $realm=$_REQUEST["realm"];
  $ps=$_REQUEST["ps"];
  if ($_REQUEST["act"]=="au") addUser($user,$realm,$ps);
  else {
    $r=delUser($user,$realm,$ps);
    if ($r!==true) exit($r);
  }
  print_r(parse_ini_file(".group",true));
}

if ( !array_key_exists("act",$_POST) && empty($_SESSION["authName"]) ) {
  // initialize authentication
  $ar=AuthRegistry::getInstance(0, ["realm"=>"test", "authName"=>"", "serverNonce"=>"", "clientNonce"=>"", "serverCount"=>0, "clientCount"=>0, "secret"=>"", "authMode"=>$authMode, "minDelay"=>3, "maxDelayAuth"=>300, "maxDelayPage"=>3600, "persStorage"=>0, "alert"=>"Ok"] );

  $sn=makeServerNonce();
  $ar->s("serverNonce",$sn);
  
  $_SESSION["registry"]=$ar->export();
  $_SESSION["notBefore"]=time()+$ar->g("minDelay");
  $_SESSION["notAfter"]=time()+$ar->g("maxDelayAuth");
  session_write_close ();

  // show form
  require("AuthElements.php");
  require("SubAuthElements.php");
  $formSelect=[0=>"PlainAuthElements",1=>"OpportunisticAuthElements",2=>"StrictAuthElements"];
  $ar->s( "controlsClass", $formSelect[$ar->g("authMode")] );
  include("authForm.php");
  exit(0);
}

$tryPlainText=0;
$tryDigest=0;
if ( array_key_exists("act",$_POST) ) {
  $tryPlainText=( $_POST["act"]=="authPlain" || ( $_POST["act"]=="authOpp" && array_key_exists("plain",$_POST) ) );
  $tryDigest=( $_POST["act"]=="authJs" || ($_POST["act"]=="authOpp" && !array_key_exists("plain",$_POST) ) );
}

if ($tryPlainText) {
  
  echo("Trying auth plaintext ");
  if ( ($ct=checkTime()) !== true ) fail($ct); 

  $ar=AuthRegistry::getInstance(1, $_SESSION["registry"]);
  if ( $ar->g("authMode") == 2 ) fail("Plaintext auth is turned off on the server");
  $realm=$ar->g("realm");
  $applicantName=$_POST["user"];
  $applicantPsw=$_POST["ps"];
  $applicantHa=makeHa1($applicantName,$realm,$applicantPsw);
  //echo(">".makeHa1($_REQUEST["user"],$ar->g("realm"),$_REQUEST["ps"])."<");// DEBUG  
  $foundName="";
  $users=parseGroup($ar->g("realm"));
  // simply use array as dictionary
  if ( array_key_exists($applicantHa,$users) ) $foundName=$users[$applicantHa];
  if ( $foundName!= $_POST["user"] ) {
    fail("Access denied, sorry");
  }
  
  $_SESSION["authName"]=$foundName;
  $ar->s("authName",$foundName);
  $ar->s("secret",$applicantHa);
  $_SESSION["registry"]=$ar->export();
  $_SESSION["notAfter"]=time()+$ar->g("maxDelayPage");
  session_write_close ();
  // registered OK, fall-through
  $outcome="Plaintext authentication OK";
}

if ($tryDigest) {

  echo(" Trying JS digest authentication ");
  if ( ($ct=checkTime()) !== true ) fail($ct); 
  if ($_POST["user"] || $_POST["ps"]) fail("This mode takes no credentials ");
  $ar=AuthRegistry::getInstance(1, $_SESSION["registry"]);
  if ( $ar->g("authMode") == 0 ) fail("Digest auth is turned off on the server");
  
  $realm=$ar->g("realm");
  if ( class_exists("SessionRegistry") ) {
    $sr_=SessionRegistry::getInstance(); 
    $realm_=$sr_->g("forum");
    if ( $realm_ && $realm!=$realm_ ) throw new UsageException("Realm mismatch: ".$realm." and ".$realm_ );
  }
  $applicantNonce=$_POST["cn"];
  $applicantResp=$_POST["responce"];
  
  $foundName="";
  $users=parseGroup($ar->g("realm"));  
  // hash -> proposed responce -> check -> user name
  // so no need to send name in open
  foreach ($users as $ha=>$name) {
    $tryResponse=makeResponse($ar->g("serverNonce"),$ha,$applicantNonce);
    if ( $tryResponse===$applicantResp ) {
      $foundName=$name;
      break;
    }
  }
  
  if ( !$foundName ) {
    fail("Access denied, sorry");
  }
  
  $_SESSION["authName"]=$foundName;
  $ar->s("authName",$foundName);
  $ar->s("secret",iterateSecret($ha,$applicantNonce));
  //$ar->s("clientNonce",$applicantNonce);
  $ar->s("clientCount",1);
  $ar->s("serverCount",1);
  if ( array_key_exists("pers",$_POST) ) $ar->s("persStorage",1);
  $_SESSION["registry"]=$ar->export();
  $_SESSION["notAfter"]=time()+$ar->g("maxDelayPage");
  session_write_close ();
  // registered OK
  $outcome="Digest authentication OK as ".$foundName;
}

// over-safe
if ( empty($_SESSION['authName']) ) fail("Something is out of order");

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
<p>Outcome: <?php print($outcome); ?>, Counter: <?php print($_SESSION['count']); ?>, ID: <?php print(session_id()); ?></p>
</body>
</html>
