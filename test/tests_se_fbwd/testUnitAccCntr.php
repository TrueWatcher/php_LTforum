<?php
// Unit tests for LTforum AccessController
// All PHPUnit tests stop after first failure!
// (c) TrueWatcher Jan 2017

//use PHPUnit\Framework\TestCase;

//include '../mod_mail_form.php';
//include '../helper.php';
$mainPath="../../LTforum/";

require_once ($mainPath."CardfileSqlt.php");
require_once ($mainPath."AssocArrayWrapper.php");
require_once ($mainPath."Act.php");
require_once ($mainPath."MyExceptions.php");
require_once ($mainPath."AccessHelper.php");
require_once ($mainPath."AccessController.php");
require_once ($mainPath."Applicant.php");

class DetachedAceessHelper extends AccessHelper {
  // ----- Interface methods for AccessController  -----
  // --- interface to session control functions ---

  static function startSession (AuthRegistry $ar) {}
  
  static function nullifySession() {}
    
  static function regenerateId() {
    $ar=AuthRegistry::getInstance();
    $ar->trace("reg_id");
  }
  
  // --- interface to Header ---
  
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
    echo ( "\n".$cc::titleSuffix($ar)."\n" );
    //include($ar->g("templatePath")."authForm.php");
  }

  static function showAuthAlert (AuthRegistry $ar, $authMessage="") {
    $ar->s("alert",$authMessage);
    require_once($ar->g("templatePath")."AuthElements.php");
    require_once($ar->g("templatePath")."SubAuthElements.php");
    echo ( "\n".AlertAuthElements::titleSuffix($ar)."\n" );
    //$ar->s( "controlsClass", "AlertAuthElements" );
    //include($ar->g("templatePath")."authForm.php");
  }
}

class SessionRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
}

function page($input,&$session,&$registry) {
  $ar=AuthRegistry::getInstance(1, ["realm"=>"test", "targetPath"=>"../../", "templatePath"=>"../../LTforum/templates/", "assetsPath"=>"../../assests/", "isAdminArea"=>0, "authName"=>"", "serverNonce"=>"",  "serverCount"=>0, "clientCount"=>0, "secret"=>"", "authMode"=>1, "minDelay"=>5, "maxDelayAuth"=>5*60, "maxDelayPage"=>60*60, "maxTimeoutGcCookie"=>5*24*3600, "minRegenerateCookie"=>1*24*3600, "reg"=>"", "act"=>"", "user"=>"", "ps"=>"", "cn"=>"", "response"=>"", "plain"=>"", "pers"=>"", "alert"=>"", "controlsClass"=>"", "trace"=>"" ] );
  $ac=new AccessController($ar,$input,$session,"DetachedAceessHelper");
  $acRet=$ac->go();
  $registry=$ar;
  //print_r($ac->session);
  echo ("Trace:".$ar->g("trace")."\n");
  echo ("Alert:".$ar->g("alert")."\n");
  return ($acRet);
}

class Test_AccessController_basic extends PHPUnit_Framework_TestCase {

    public function test_getStaticHello() {
      $hello=AccessController::hello();
      print ("Responce:".$hello."\n");
      $this->assertGreaterThan(1,strlen($hello),"No response");
    }
    
    public function test_page_emptyReset() {
      $input=[];
      $session=[];
      $ar=null;
      
      page($input,$session,$ar);
      //if ( $session===null || !is_array($session) ) $session=[];
      echo ("Session:");
      print_r($session);
      $t=$ar->g("trace");
      $this->assertEquals ( ">zero>preAuth>false", $t, "Wrong trace" );
      $this->assertNotEmpty ($session["serverNonce"], "Empty serverNonce" );
      $sn=$session["serverNonce"];
      $nb=$session["notBefore"];
      $au=$session["activeUntil"];
      
      $ar->s("trace","");
      
      sleep(1);
      $input=["reg"=>"reset"];
      page($input,$session,$ar);
      //if ( $session===null || !is_array($session) ) $session=[];
      echo ("Session:");
      print_r($session);
      $t=$ar->g("trace");
      $this->assertEquals  ( ">preAuth>preAuth>false", $t, "Wrong trace" );
      $this->assertNotEquals ( $sn, $session["serverNonce"], "Unchanged serverNonce" );
      $this->assertGreaterThan ( $nb, $session["notBefore"], "Unchanged notBefore" );
      $this->assertGreaterThan ( $au, $session["activeUntil"], "Unchanged activeUntil" );
      
      $ar->s("trace","");
      
      echo("Test empty-reset OK\n");

    }    
    
}

?>