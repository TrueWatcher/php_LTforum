<?php
  require_once("../LTforum/AssocArrayWrapper.php");
  require_once ("../LTforum/MyExceptions.php");  
  
  class AuthRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
  }

  print_r($_POST);
ini_set("session.serialize_handler","php_serialize");
session_start();
print_r($_SESSION);

if ( array_key_exists("act",$_POST) && $_POST["act"]=="r" ) {
  session_destroy();
}

if ( array_key_exists("act",$_POST) && $_POST["act"]=="authPlain" ) {

  echo("Trying authPlain ");
  if ( empty($_SESSION["notBefore"]) || empty($_SESSION["notAfter"]) ) exit ("Error! Authentication without active session");
  $t=time();
  if ( $t < $_SESSION["notBefore"] || $t > $_SESSION["notAfter"] ) exit ("Error! Too fast or too slow submission");
  $ar=AuthRegistry::getInstance(0, $_SESSION["registry"]);
  $realm=$ar->g("realm");
  $applicantName=$_POST["user"];
  $applicantPsw=$_POST["ps"];
  $applicantHa=md5($applicantName.$realm.$applicantPsw);
  
  $realm="test";
  $u1="Me";
  $p1="1234";
  $ha1_1=md5($u1.$realm.$p1);
  $u2="test";
  $p2="q";
  $ha1_2=md5($u2.$realm.$p2);
  $users=[ $ha1_1=>$u1, $ha1_2=>$u2 ];
  
  $foundName="";
  if ( array_key_exists($applicantHa,$users) ) $foundName=$users[$applicantHa];
  if ( $foundName!= $_POST["user"] ) {
    session_destroy();
    exit ("Sorry, access denied ");
  }
  
  $_SESSION["authName"]=$applicantName;
  $ar->s("authName",$applicantName);
  $ar->s("secret",$applicantHa);
  $_SESSION["registry"]=$ar->export();
  $_SESSION["notAfter"]=time()+$ar->g("maxDelayPage");
  session_write_close ();
  
}

if ( empty($_SESSION["authName"]) ) {
  // initialize authentication
  $ar=AuthRegistry::getInstance(0, ["realm"=>"test", "authName"=>"", "serverNonce"=>"", "clientNonce"=>"", "serverCount"=>0, "clientCount"=>0, "secret"=>"", "authMode"=>0, "minDelay"=>3, "maxDelayAuth"=>300, "maxDelayPage"=>3600, "permStorDetected"=>0, "alert"=>"Ok"] );
  
  if ( is_callable("openssl_random_pseudo_bytes") ) {
    $openSslOutcome=false;
    $sn=openssl_random_pseudo_bytes(16,$openSslOutcome);
    $ar->s("alert",$ar->g("alert")." tried secure:".$openSslOutcome." " );
  }
  else {
    $sn0=(string)random_int(0,PHP_INT_MAX);
    $sn=md5( $sn0.time(),true );
  }
  $sn=base64_encode($sn);
  $ar->s("serverNonce",$sn);
  
  $_SESSION["registry"]=$ar->export();
  $_SESSION["notBefore"]=time()+$ar->g("minDelay");
  $_SESSION["notAfter"]=time()+$ar->g("maxDelayAuth");
  session_write_close ();

  require("AuthElements.php");
  class PlainAuthElements extends AuthElements {
    static function hiddenFields($context) {
     $h=parent::genericInput ("hidden","act","authPlain");
     return ($h);
    }
  }
  class OpportunisticAuthElements extends AuthElements {
    static function hiddenFields($context) {
     $h=parent::genericInput ("hidden","act","authOpp");
     $h.=parent::genericInput ( "hidden","sn",$context->g("serverNonce") );
     return ($h);
    }  
    static function scriptHelper() {
      return ( parent::scriptHelper("protectHelper.js") );
    }
    static function scriptOnready() {
      return ( parent::scriptOnready('alert(" Hi! ");') );
    }
  }
  //$ar->s("controlsClass","PlainAuthElements");
  $ar->s("controlsClass","OpportunisticAuthElements");
  include("authForm.php");
  exit(0);
}

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
<p>Counter: <?php print($_SESSION['count']); ?>, ID: <?php print(session_id()); ?></p>
</body>
</html>
