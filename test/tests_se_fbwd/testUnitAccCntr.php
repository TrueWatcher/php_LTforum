<?php
// Unit tests for LTforum AccessController (since v.1.2)
// Uses PHPUnit
// All PHPUnit tests stop after first failure!
// by TrueWatcher, Jan 2017

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

  protected $authRegistryTemplate = [ "realm"=>"test", "targetPath"=>"../", "templatePath"=>"../../LTforum/templates/", "assetsPath"=>"../../assests/", "isAdminArea"=>0, "authName"=>"", "serverNonce"=>"",  "serverCount"=>0, "clientCount"=>0, "secret"=>"", "authMode"=>1, "minDelay"=>2, "maxDelayAuth"=>4, "maxDelayPage"=>6, "maxTimeoutGcCookie"=>10, "minRegenerateCookie"=>8, "reg"=>"", "act"=>"", "user"=>"", "ps"=>"", "cn"=>"", "response"=>"", "plain"=>"", "pers"=>"", "alert"=>"", "controlsClass"=>"", "trace"=>"" ];

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
  
  public function test_plaintextAuth() {
    $art = $this->authRegistryTemplate;
  
    $input=[];
    $session=[];
    $ar=null;
    
    // try to register from zero state, expecting failure
    $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">zero>preAuth>false", $t, "Wrong trace" );
    
    $ar->destroy();
    $session=[];
    
    // get form
    $input=[];
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
    
    $ar->destroy();
    
    echo("Test plaintextAuth OK\n");
  }
  
  public function test_digestAuth() {
    $ha1_admin=AccessHelper::makeHa1("admin","test","admin");
    $art = $this->authRegistryTemplate;
  
    $input=[];
    $session=[];
    $ar=null;
    
    // try to register from zero state, expecting failure
    $clientNonce=AccessHelper::makeServerNonce();
    $serverNonce=AccessHelper::makeServerNonce();
    $response=AccessHelper::makeResponse($serverNonce,$ha1_admin,$clientNonce);
    $input=["reg"=>"authOpp","plain"=>0,"cn"=>$clientNonce,"response"=>$response];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">zero>preAuth>false", $t, "Wrong trace" );
    
    $ar->destroy();
    $session=[];
    
    // get form
    $input=[];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");
    $this->assertEquals ( ">zero>preAuth>false", $t, "Wrong trace" );
    $this->assertNotEmpty ($session["serverNonce"], "Empty serverNonce" );
    
    $ar->destroy();
    
    // send registration request after 1 second, expecting "Wait for a few seconds" alert
    sleep( 1 );
    $clientNonce=AccessHelper::makeServerNonce();
    $response=AccessHelper::makeResponse($session["serverNonce"],$ha1_admin,$clientNonce);
    $input=["reg"=>"authOpp","plain"=>0,"cn"=>$clientNonce,"response"=>$response];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">preAuth>false", $t, "Wrong trace" );
    $this->assertContains ("wait",$ar->g("alert"),"Alert does not contain WAIT");
    
    $ar->destroy();
    
    // send registration request after delay, expecting success
    sleep( $art["minDelay"]+1 );
    $clientNonce=AccessHelper::makeServerNonce();
    $response=AccessHelper::makeResponse($session["serverNonce"],$ha1_admin,$clientNonce);
    $input=["reg"=>"authOpp","plain"=>0,"cn"=>$clientNonce,"response"=>$response];
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
    $clientNonce=AccessHelper::makeServerNonce();
    $response=AccessHelper::makeResponse($session["serverNonce"],$ha1_admin,$clientNonce);
    $input=["reg"=>"authOpp","plain"=>0,"cn"=>$clientNonce,"response"=>$response];
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
    $clientNonce=AccessHelper::makeServerNonce();
    $response=AccessHelper::makeResponse($serverNonce,$ha1_admin,$clientNonce);
    $input=["reg"=>"authOpp","plain"=>0,"cn"=>$clientNonce,"response"=>$response];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");      
    $this->assertEquals  ( ">active>reg_id>zero>preAuth>false", $t, "Wrong trace" );
    $this->assertFalse ( array_key_exists("authName",$session), "Not cleared authName from SESSION" );

    $ar->destroy();
    echo("Test digestAuth OK\n");
  }
  
  public function test_delays() {
    $art = $this->authRegistryTemplate;
  
    $input=[];
    $session=[];
    $ar=null;
          
    // get form
    $input=[];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");
    $this->assertEquals ( ">zero>preAuth>false", $t, "Wrong trace" );
    $this->assertNotEmpty ($session["serverNonce"], "Empty serverNonce" );
    
    $ar->destroy();
    
    // send registration request after max delay, expecting failure
    sleep( $art["maxDelayAuth"] + 1 );
    $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">preAuth>preAuth>false", $t, "Wrong trace" );
    $this->assertFalse (array_key_exists("authName",$session),"authName in SESSION");
    
    $ar->destroy();
    
    // register normally, expecting success
    sleep( $art["minDelay"] + 1 );
    $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">preAuth>reg_id>active>redirect>false", $t, "Wrong trace" );
    
    $ar->destroy();
    
    // test fallout to postAuth
    $nb=$session["notBefore"];
    sleep( $art["maxDelayPage"] + 1 );
    $input=["act"=>"new"];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">active>postAuth>false", $t, "Wrong trace" );
    $this->assertFalse (array_key_exists("isAdmin",$session),"Remaining isAdmin in postAuth");
    $this->assertTrue (array_key_exists("targetUri",$session),"Missing redirectURI in SESSION");
    $this->assertContains("act=new",$session["targetUri"],"Wrong redirectURI");
    $a=$ar->g("alert");
    $rightAlert=( strpos($a,"nanswered")!==false || strpos($a,"this user")!==false );
    $this->assertTrue($rightAlert,"Missing or wrong info in postAuth");
    $this->assertGreaterThan($nb,$session["notBefore"],"Stalled notBefore");
    
    $ar->destroy();
    CardfileSqlt::destroy();
    
    // test not regenerate cookie in postAuth state before minRegenerateCookie
    $ct=$session["cookieTime"];
    $nb=$session["notBefore"];
    sleep( 1 );
    $input=[];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");    
    $this->assertEquals  ( ">postAuth>postAuth>false", $t, "Wrong trace" );
    $delta=$session["cookieTime"]-$ct;
    $this->assertEquals(0,$delta,"Regeneration was called after short delay");
    $this->assertFalse (array_key_exists("targetUri",$session),"targetUri after empty input");
    $a=$ar->g("alert");
    $rightAlert=( strpos($a,"nanswered")!==false || strpos($a,"this user")!==false );
    $this->assertTrue($rightAlert,"Missing or wrong info in postAuth");
    $this->assertGreaterThan($nb,$session["notBefore"],"Stalled notBefore");
    
    $ar->destroy();
    CardfileSqlt::destroy();
    
    // test regenerate cookie in postAuth state
    $ct=$session["cookieTime"];
    $nb=$session["notBefore"];
    sleep( $art["minRegenerateCookie"] + 1 );
    $input=[];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");    
    $this->assertEquals  ( ">postAuth>reg_id>postAuth>false", $t, "Wrong trace" );
    $delta=$session["cookieTime"]-$ct;
    $this->assertGreaterThan(1,$delta,"No regeneration was called");
    $this->assertFalse (array_key_exists("targetUri",$session),"targetUri after empty input");
    $a=$ar->g("alert");
    $rightAlert=( strpos($a,"nanswered")!==false || strpos($a,"this user")!==false );
    $this->assertTrue($rightAlert,"Missing or wrong info in postAuth");
    $this->assertGreaterThan($nb,$session["notBefore"],"Stalled notBefore");
    
    $ar->destroy();
    CardfileSqlt::destroy();
    
    // test cross-realm registration in post-auth, expecting success
    $art["realm"]="demo";
    $art["targetPath"]="../../demo/";
    sleep( $art["minDelay"] + 1 );
    $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");
    $this->assertEquals ( ">postAuth>active>true", $t, "Wrong trace" );

    $ar->destroy();
    CardfileSqlt::destroy();
    
    // deactivate
    $input=["reg"=>"deact"];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">active>reg_id>postAuth>false", $t, "Wrong trace" );    

    $ar->destroy();
    CardfileSqlt::destroy();
    
    // test repeated deact request, expecting redirect
    $input=["reg"=>"deact"];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">postAuth>redirect>false", $t, "Wrong trace" );    
    
    $ar->destroy();
    CardfileSqlt::destroy();
    
    // test cross-realm page request in postAuth, expecting fall to preAuth
    $art["realm"]="test";
    $art["targetPath"]="../";
    $nb=$session["notBefore"];
    sleep(1);
    $input=[];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");
    $this->assertEquals ( ">postAuth>zero>reg_id>preAuth>false", $t, "Wrong trace" );
    $this->assertContains ("new thread",$ar->g("alert"),"Wrong message" );
    $this->assertGreaterThan($nb,$session["notBefore"],"Stalled notBefore");

    $ar->destroy();
    CardfileSqlt::destroy();
    
    echo("Test delays OK\n");

  }
}    
?>