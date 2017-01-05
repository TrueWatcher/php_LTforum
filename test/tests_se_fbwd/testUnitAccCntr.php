<?php
// Unit tests for LTforum AccessController
// All PHPUnit tests stop after first failure!
// (c) TrueWatcher Jan 2017

//use PHPUnit\Framework\TestCase;

$mainPath="../../LTforum/";

require_once ($mainPath."CardfileSqlt.php");
require_once ($mainPath."AssocArrayWrapper.php");
require_once ($mainPath."Act.php");
require_once ($mainPath."MyExceptions.php");
require_once ($mainPath."AccessHelper.php");
require_once ($mainPath."AccessController.php");
require_once ($mainPath."Applicant.php");

function page($registryTemplate,$input,&$session,&$registry) {
  echo("-----\n");
  echo("Input:");
  if (!empty($input)) print_r($input);
  else print ("empty\n");
  $ar=AuthRegistry::getInstance( 1, $registryTemplate );
  $ac=new AccessController( $ar, $input, $session, "DetachedAccessHelper" );
  $acRet=$ac->go();
  $registry=$ar;
  //print_r($ac->session);
  echo ("Trace:".$ar->g("trace")."\n");
  echo ("Alert:".$ar->g("alert")."\n");
  echo ("Session:"); print_r($session);
  //$ar->destroy();
  return ($acRet);
}

class Test_AccessController_basic extends PHPUnit_Framework_TestCase {

    protected $authRegistryTemplate = [ "realm"=>"test", "targetPath"=>"../../", "templatePath"=>"../../LTforum/templates/", "assetsPath"=>"../../assests/", "isAdminArea"=>0, "authName"=>"", "serverNonce"=>"",  "serverCount"=>0, "clientCount"=>0, "secret"=>"", "authMode"=>1, "minDelay"=>3, "maxDelayAuth"=>10, "maxDelayPage"=>30, "maxTimeoutGcCookie"=>30, "minRegenerateCookie"=>20, "reg"=>"", "act"=>"", "user"=>"", "ps"=>"", "cn"=>"", "response"=>"", "plain"=>"", "pers"=>"", "alert"=>"", "controlsClass"=>"", "trace"=>"" ];

    public function _test_getStaticHello() {
      $hello=AccessController::hello();
      print ("Responce:".$hello."\n");
      $this->assertGreaterThan(1,strlen($hello),"No response");
    }
    
    public function test_page_emptyEmpty() {
      $art = $this->authRegistryTemplate;
    
      $input=[];
      $session=[];
      $ar=null;
      
      page($art,$input,$session,$ar);
      $t=$ar->g("trace");
      $this->assertEquals ( ">zero>preAuth>false", $t, "Wrong trace" );
      $this->assertNotEmpty ($session["serverNonce"], "Empty serverNonce" );
      $sn=$session["serverNonce"];
      $nb=$session["notBefore"];
      $au=$session["activeUntil"];
      
      $ar->destroy();
      
      sleep( 1 );
      $input=[];
      page($art,$input,$session,$ar);
      $t=$ar->g("trace");
      $this->assertEquals  ( ">preAuth>preAuth>false", $t, "Wrong trace" );
      $this->assertNotEquals ( $sn, $session["serverNonce"], "Unchanged serverNonce" );
      $this->assertGreaterThan ( $nb, $session["notBefore"], "Unchanged notBefore" );
      $this->assertGreaterThan ( $au, $session["activeUntil"], "Unchanged activeUntil" );
      
      $ar->destroy();
      
      echo("Test empty-empty OK\n");
    }
    
    public function test_page_plaintextAuth() {
      $art = $this->authRegistryTemplate;
    
      $input=[];
      $session=[];
      $ar=null;
      
      // get form
      page($art,$input,$session,$ar);
      $t=$ar->g("trace");
      $this->assertEquals ( ">zero>preAuth>false", $t, "Wrong trace" );
      $this->assertNotEmpty ($session["serverNonce"], "Empty serverNonce" );
      
      $ar->destroy();
      
      // send registration request after 1 second, expecting "Wait for a few seconds" alert
      sleep( 1 );
      $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
      page($art,$input,$session,$ar);
      $t=$ar->g("trace");
      $this->assertEquals  ( ">preAuth>false", $t, "Wrong trace" );
      $this->assertContains ("wait",$ar->g("alert"),"Alert does not contain WAIT");
      
      $ar->destroy();
      
      // send registration request after delay, expecting success
      sleep( $art["minDelay"]+1 );
      $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
      page($art,$input,$session,$ar);
      $t=$ar->g("trace");
      $this->assertEquals  ( ">preAuth>reg_id>active>true", $t, "Wrong trace" );
      $this->assertEquals ( "test", $session["realm"], "Missing realm in SESSION" );
      $this->assertEquals ( 1, $session["isAdmin"], "Missing isAdmin in SESSION" );
      $this->assertEquals ( "admin", $session["authName"], "Missing or wrong authName in SESSION" );
      
      $ar->destroy();
      
      // send request for a page, expecting successful authentication
      //$input=[];
      $input=["act"=>"search"];
      page($art,$input,$session,$ar);
      $t=$ar->g("trace");
      $this->assertEquals  ( ">active>true", $t, "Wrong trace" );
      
      $ar->destroy();
      
      // send Log out
      $input=["reg"=>"deact"];
      page($art,$input,$session,$ar);
      $t=$ar->g("trace");
      $this->assertEquals  ( ">active>reg_id>postAuth>false", $t, "Wrong trace" );
      $this->assertEquals ( "admin", $session["authName"], "Missing or wrong authName in SESSION" );
      $this->assertEquals ( "test", $session["realm"], "Missing realm in SESSION" );
      $this->assertFalse ( array_key_exists("isAdmin",$session), "Not cleared isAdmin from SESSION" );
      
      $ar->destroy();
      
      // test registration from postAuth state
      sleep( $art["minDelay"]+1 );
      $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
      page($art,$input,$session,$ar);
      $t=$ar->g("trace");
      $this->assertEquals  ( ">postAuth>active>redirect>false", $t, "Wrong trace" );
      $this->assertEquals ( "test", $session["realm"], "Missing realm in SESSION" );
      $this->assertEquals ( 1, $session["isAdmin"], "Missing isAdmin in SESSION" );
      $this->assertEquals ( "admin", $session["authName"], "Missing or wrong authName in SESSION" );
      
      $ar->destroy();
      
      // send request for registration in active state, expecting failure
      //$art["realm"]="demo";
      sleep( $art["minDelay"]+1 );
      $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
      page($art,$input,$session,$ar);
      $t=$ar->g("trace");      
      $this->assertEquals  ( ">active>reg_id>zero>preAuth>false", $t, "Wrong trace" );
      $this->assertFalse ( array_key_exists("authName",$session), "Not cleared authName from SESSION" );
      
      echo("Test plaintextAuth OK\n");
    }
    
}

?>