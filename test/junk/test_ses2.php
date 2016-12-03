<?php
  require_once("../LTforum/AssocArrayWrapper.php");
  require_once ("../LTforum/MyExceptions.php");  

  class SessionRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
  }
  $sr=SessionRegistry::getInstance(0,["forum"=>"test"]);

  require ("../LTforum/Hopper.php");
  require ("../LTforum/SessionManager.php");
  
  //$authMode=1; // 0 - plain text, 1 - JS digest or plain text, 2 - only JS digest
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
  
  $ar=AuthRegistry::getInstance(1, ["realm"=>"test","targetPath"=>"","templatePath"=>"../LTforum/templates/", "assetsPath"=>"../assests/", "admin"=>0, "authName"=>"", "serverNonce"=>"",  "serverCount"=>0, "clientCount"=>0, "secret"=>"", "authMode"=>1, "minDelay"=>3, "maxDelayAuth"=>300, "maxDelayPage"=>3600, "act"=>"", "user"=>"", "ps"=>"", "cn"=>"", "response"=>"", "plain"=>"", "pers"=>"", "alert"=>"", "controlsClass"=>"" ] );
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
  