<?php
// Unit tests for LTforum AccessController
// All PHPUnit tests stop after first failure!
// (c) TrueWatcher Jan 2017

//use PHPUnit\Framework\TestCase;

namespace nosession;
use \;

//include '../mod_mail_form.php';
//include '../helper.php';
$mainPath="../../LTforum/";

require_once ($mainPath."CardfileSqlt.php");
require_once ($mainPath."AssocArrayWrapper.php");
require_once ($mainPath."Act.php");
require_once ($mainPath."MyExceptions.php");
require_once ($mainPath."AccessController.php");
require_once ($mainPath."Applicant.php");

class SessionRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
}

$input=[];
$session=[];

function page($input,$session) {
  $ar=AuthRegistry::getInstance(1, ["realm"=>"test", "targetPath"=>"../../", "templatePath"=>"../../LTforum/templates", "assetsPath"=>"../../assests", "isAdminArea"=>0, "authName"=>"", "serverNonce"=>"",  "serverCount"=>0, "clientCount"=>0, "secret"=>"", "authMode"=>1, "minDelay"=>5, "maxDelayAuth"=>5*60, "maxDelayPage"=>60*60, "maxTimeoutGcCookie"=>5*24*3600, "minRegenerateCookie"=>1*24*3600, "reg"=>"", "act"=>"", "user"=>"", "ps"=>"", "cn"=>"", "response"=>"", "plain"=>"", "pers"=>"", "alert"=>"", "controlsClass"=>"", "trace"=>"" ] );
  $ac=new AccessController($ar,$input,$session);
  $acRet=$ac->go();
  echo ("Trace:".$ar->g("trace")."\n");
  echo ("Alert:".$ar->g("alert")."\n");
  //if ( $alert=$ar->g("alert") ) echo($alert);
  //if($acRet!==true) exit($acRet);
}

function trace($mes) {
  if (!class_exists("AuthRegistry")) return;
  $ar=AuthRegistry::getInstance();
  $ar->trace($mes);
}

function redefine() {
  echo("Redefining some PHP functions.\n");
  override_function("session_start","","");
  override_function("session_destroy","","");
  override_function("session_write_close","","");
  override_function( "session_regenerate_id", "", "trace(\"reg_id\");" );
  override_function( "header", "", "trace(\"redirect\");" );
}


function session_start() {}
function session_destroy() {}
function session_write_close() {}
function session_regenerate_id() { trace("reg_id"); }
function header() { trace("redirect"); }

class \Test_AccessController_basic extends \PHPUnit_Framework_TestCase {

    public function test_getStaticHello() {
      $hello=\AccessController::hello();
      print ("Responce:".$hello."\n");
      $this->assertGreaterThan(1,strlen($hello),"No response");
    }
    
    public function test_page() {
      
      
      \page($input,$session);
      

    }    
    
}

?>