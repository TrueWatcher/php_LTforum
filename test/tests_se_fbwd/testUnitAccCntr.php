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
require_once ($mainPath."UserManager.php");
require_once ($mainPath."Translator.php");

/**
 * Imitates page request.
 * @param array $registryTemplate initialization values for AuthRegistry
 * @param array $input array serving as $_REQUEST (e.g. [] or ["reg"=>"reset"]), readonly
 * @param array $session input-output! array serving as $_SESSION
 * @param {object AuthRegistry} $registry output! AuthRegistry instance after page processing
 * @param string|int $verbose 1 to print out $input and $session
 * @return boolean|string the AccessController return code: true to go on, false or message to exit
 */
function page ($registryTemplate, $input, &$session, &$registry, $verbose=0) {
  if ($verbose) {
    echo("Input:");
    if (!empty($input)) print_r($input);
    else print ("empty\n");
  }
  AuthRegistry::clearInstance();
  $ar=AuthRegistry::getInstance( 1, $registryTemplate );
  $ac=new AccessController( $ar, $input, $session, "DetachedAccessHelper" );
  $acRet=$ac->go();
  $registry=$ar;
  //print_r($ac->session);
  echo ("Trace:".$ar->g("trace")."\n");
  echo ("Alert:".$ar->g("alert")."\n");
  if ($verbose) { 
    echo ("Session:");
    print_r($session);
  }
  //$ar->destroy();
  return ($acRet);
}

echo("\nUnit tests for AccessController\n\n");

class Test_AccessController_basic extends PHPUnit_Framework_TestCase {

  protected $authRegistryTemplate = [ "realm"=>"test", "targetPath"=>"../", "templatePath"=>"../../LTforum/templates/", "assetsPath"=>"../../assests/", "isAdminArea"=>0, "authName"=>"", "serverNonce"=>"",  "serverCount"=>0, "clientCount"=>0, "secret"=>"", "authMode"=>1, "minDelay"=>2, "maxDelayAuth"=>4, "maxDelayPage"=>6, "maxTimeoutGcCookie"=>10, "minRegenerateCookie"=>8, "guestsAllowed"=>false, "masterRealms"=>"",  "reg"=>"", "act"=>"", "user"=>"", "ps"=>"", "cn"=>"", "response"=>"", "plain"=>"", "pers"=>"", "alert"=>"", "controlsClass"=>"", "trace"=>"" ];

  public function _test_getStaticHello() {
    $hello=AccessController::hello();
    print ("Responce:".$hello."\n");
    $this->assertGreaterThan(1,strlen($hello),"No response");
  }
  
  public function test_page_emptyEmpty() {
    $verbose=0;
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
    
    sleep( 1 );
    $input=[];
    page($art,$input,$session,$ar);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">preAuth>preAuth>false", $t, "Wrong trace" );
    $this->assertNotEquals ( $sn, $session["serverNonce"], "Unchanged serverNonce" );
    $this->assertGreaterThan ( $nb, $session["notBefore"], "Unchanged notBefore" );
    $this->assertGreaterThan ( $au, $session["activeUntil"], "Unchanged activeUntil" );
    
    echo("\nTest empty-empty OK\n");
  }
  
  public function test_plaintextAuth() {
    $verbose=0;
    $art = $this->authRegistryTemplate;
  
    $input=[];
    $session=[];
    $ar=null;
    
    echo("\n trying to register from zero state, expecting failure\n");
    $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">zero>preAuth>false", $t, "Wrong trace" );
    
    $session=[];
    
    echo("\ngetting the form\n");
    $input=[];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">zero>preAuth>false", $t, "Wrong trace" );
    $this->assertNotEmpty ($session["serverNonce"], "Empty serverNonce" );

    
    echo("\nsending registration request after 1 second, expecting alert\n");
    sleep( 1 );
    $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">preAuth>false", $t, "Wrong trace" );
    $this->assertContains ("wait",$ar->g("alert"),"Alert does not contain WAIT");
    
    echo("\nsending registration request after delay, expecting success\n");
    sleep( $art["minDelay"]+1 );
    $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">preAuth>reg_id>active>true", $t, "Wrong trace" );
    $this->assertEquals ( "test", $session["realm"], "Missing realm in SESSION" );
    $this->assertEquals ( 1, $session["isAdmin"], "Missing isAdmin in SESSION" );
    $this->assertEquals ( "admin", $session["authName"], "Missing or wrong authName in SESSION" );
    
    echo("\nsending request for a page, expecting successful pass\n");
    //$input=[];
    $input=["act"=>"search"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">active>true", $t, "Wrong trace" );
    
    echo("\nsending Log out\n");
    $input=["reg"=>"deact"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">active>reg_id>postAuth>false", $t, "Wrong trace" );
    $this->assertEquals ( "admin", $session["authName"], "Missing or wrong authName in SESSION" );
    $this->assertEquals ( "test", $session["realm"], "Missing realm in SESSION" );
    $this->assertFalse ( array_key_exists("isAdmin",$session), "Not cleared isAdmin from SESSION" );
    
    echo("\ntrying registration from postAuth state, expecting success\n");
    sleep( $art["minDelay"]+1 );
    $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">postAuth>active>redirect>false", $t, "Wrong trace" );
    $this->assertEquals ( "test", $session["realm"], "Missing realm in SESSION" );
    $this->assertEquals ( 1, $session["isAdmin"], "Missing isAdmin in SESSION" );
    $this->assertEquals ( "admin", $session["authName"], "Missing or wrong authName in SESSION" );
    
    echo("\nsending request for registration in active state, expecting alert\n");
    //$art["realm"]="demo";
    sleep( $art["minDelay"]+1 );
    $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");      
    //$this->assertEquals  ( ">active>reg_id>zero>preAuth>false", $t, "Wrong trace" );
    $this->assertEquals  ( ">active>false", $t, "Wrong trace" );
    //$this->assertFalse ( array_key_exists("authName",$session), "Not cleared authName from SESSION" );
    
    echo("\nTest plaintext auth OK\n");
  }
  
  public function test_digestAuth() {
    $verbose=0;
    $ha1_admin=AccessHelper::makeHa1("admin","test","admin");
    $art = $this->authRegistryTemplate;
  
    $input=[];
    $session=[];
    $ar=null;
    
    echo("\ntrying to register from zero state, expecting failure\n");
    $clientNonce=AccessHelper::makeServerNonce();
    $serverNonce=AccessHelper::makeServerNonce();
    $response=AccessHelper::makeResponse($serverNonce,$ha1_admin,$clientNonce);
    $input=["reg"=>"authOpp","plain"=>0,"cn"=>$clientNonce,"response"=>$response];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">zero>preAuth>false", $t, "Wrong trace" );

    $session=[];
    
    echo("\ngetting the form\n");
    $input=[];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">zero>preAuth>false", $t, "Wrong trace" );
    $this->assertNotEmpty ($session["serverNonce"], "Empty serverNonce" );
    
    echo("\nsending registration request after 1 second, expecting an alert\n");
    sleep( 1 );
    $clientNonce=AccessHelper::makeServerNonce();
    $response=AccessHelper::makeResponse($session["serverNonce"],$ha1_admin,$clientNonce);
    $input=["reg"=>"authOpp","plain"=>0,"cn"=>$clientNonce,"response"=>$response];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">preAuth>false", $t, "Wrong trace" );
    $this->assertContains ("wait",$ar->g("alert"),"Alert does not contain WAIT");
    
    echo("\nsending registration request after delay, expecting success\n");
    sleep( $art["minDelay"]+1 );
    $clientNonce=AccessHelper::makeServerNonce();
    $response=AccessHelper::makeResponse($session["serverNonce"],$ha1_admin,$clientNonce);
    $input=["reg"=>"authOpp","plain"=>0,"cn"=>$clientNonce,"response"=>$response];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">preAuth>reg_id>active>true", $t, "Wrong trace" );
    $this->assertEquals ( "test", $session["realm"], "Missing realm in SESSION" );
    $this->assertEquals ( 1, $session["isAdmin"], "Missing isAdmin in SESSION" );
    $this->assertEquals ( "admin", $session["authName"], "Missing or wrong authName in SESSION" );
    
    echo("\nsend request for a page, expecting successful pass\n");
    //$input=[];
    $input=["act"=>"search"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">active>true", $t, "Wrong trace" );
    
    echo("\nsending Log out\n");
    $input=["reg"=>"deact"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">active>reg_id>postAuth>false", $t, "Wrong trace" );
    $this->assertEquals ( "admin", $session["authName"], "Missing or wrong authName in SESSION" );
    $this->assertEquals ( "test", $session["realm"], "Missing realm in SESSION" );
    $this->assertFalse ( array_key_exists("isAdmin",$session), "Not cleared isAdmin from SESSION" );
    
    echo("\ntrying registration from postAuth state, expecting success\n");
    sleep( $art["minDelay"]+1 );
    $clientNonce=AccessHelper::makeServerNonce();
    $response=AccessHelper::makeResponse($session["serverNonce"],$ha1_admin,$clientNonce);
    $input=["reg"=>"authOpp","plain"=>0,"cn"=>$clientNonce,"response"=>$response];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">postAuth>active>redirect>false", $t, "Wrong trace" );
    $this->assertEquals ( "test", $session["realm"], "Missing realm in SESSION" );
    $this->assertEquals ( 1, $session["isAdmin"], "Missing isAdmin in SESSION" );
    $this->assertEquals ( "admin", $session["authName"], "Missing or wrong authName in SESSION" );
    
    echo("\nsending request for registration in active state, expecting alert\n");
    //$art["realm"]="demo";
    sleep( $art["minDelay"]+1 );
    $clientNonce=AccessHelper::makeServerNonce();
    $response=AccessHelper::makeResponse($serverNonce,$ha1_admin,$clientNonce);
    $input=["reg"=>"authOpp","plain"=>0,"cn"=>$clientNonce,"response"=>$response];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");      
    //$this->assertEquals  ( ">active>reg_id>zero>preAuth>false", $t, "Wrong trace" );
    $this->assertEquals  ( ">active>false", $t, "Wrong trace" );
    //$this->assertFalse ( array_key_exists("authName",$session), "Not cleared authName from SESSION" );

    echo("\nTest digest auth OK\n");
  }
  
  public function test_delays() {
    $verbose=0;
    $art = $this->authRegistryTemplate;
  
    $input=[];
    $session=[];
    $ar=null;
          
    echo("\ngetting the form\n");
    $input=[];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">zero>preAuth>false", $t, "Wrong trace" );
    $this->assertNotEmpty ($session["serverNonce"], "Empty serverNonce" );
    
    echo("\nsending registration request after max delay, expecting failure\n");
    sleep( $art["maxDelayAuth"] + 1 );
    $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">preAuth>preAuth>false", $t, "Wrong trace" );
    $this->assertFalse (array_key_exists("authName",$session),"authName in SESSION");
    
    echo("\nregistering normally, expecting success\n");
    sleep( $art["minDelay"] + 1 );
    $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">preAuth>reg_id>active>redirect>false", $t, "Wrong trace" );
    
    echo("\nwaiting for fallout to postAuth\n");
    $nb=$session["notBefore"];
    sleep( $art["maxDelayPage"] + 1 );
    $input=["act"=>"new"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">active>postAuth>false", $t, "Wrong trace" );
    $this->assertFalse (array_key_exists("isAdmin",$session),"Remaining isAdmin in postAuth");
    $this->assertTrue (array_key_exists("targetUri",$session),"Missing redirectURI in SESSION");
    $this->assertContains("act=new",$session["targetUri"],"Wrong redirectURI");
    $a=$ar->g("alert");
    $rightAlert=( strpos($a,"nanswered")!==false || strpos($a,"this user")!==false );
    $this->assertTrue($rightAlert,"Missing or wrong info in postAuth");
    $this->assertGreaterThan($nb,$session["notBefore"],"Stalled notBefore");

    CardfileSqlt::destroy();
    
    echo("\ntesting not regenerate cookie in postAuth state before minRegenerateCookie\n");
    $ct=$session["cookieTime"];
    $nb=$session["notBefore"];
    sleep( 1 );
    $input=[];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");    
    $this->assertEquals  ( ">postAuth>postAuth>false", $t, "Wrong trace" );
    $delta=$session["cookieTime"]-$ct;
    $this->assertEquals(0,$delta,"Regeneration was called after short delay");
    $this->assertFalse (array_key_exists("targetUri",$session),"targetUri after empty input");
    $a=$ar->g("alert");
    $rightAlert=( strpos($a,"nanswered")!==false || strpos($a,"this user")!==false );
    $this->assertTrue($rightAlert,"Missing or wrong info in postAuth");
    $this->assertGreaterThan($nb,$session["notBefore"],"Stalled notBefore");
    
    CardfileSqlt::destroy();
    
    echo("\ntesting regenerate cookie in postAuth state\n");
    $ct=$session["cookieTime"];
    $nb=$session["notBefore"];
    sleep( $art["minRegenerateCookie"] + 1 );
    $input=[];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");    
    $this->assertEquals  ( ">postAuth>reg_id>postAuth>false", $t, "Wrong trace" );
    $delta=$session["cookieTime"]-$ct;
    $this->assertGreaterThan(1,$delta,"No regeneration was called");
    $this->assertFalse (array_key_exists("targetUri",$session),"targetUri after empty input");
    $a=$ar->g("alert");
    $rightAlert=( strpos($a,"nanswered")!==false || strpos($a,"this user")!==false );
    $this->assertTrue($rightAlert,"Missing or wrong info in postAuth");
    $this->assertGreaterThan($nb,$session["notBefore"],"Stalled notBefore");
    
    CardfileSqlt::destroy();
    
    echo("\ntesting cross-realm registration in post-auth, expecting success\n");
    $art["realm"]="demo";
    $art["targetPath"]="../../demo/";
    sleep( $art["minDelay"] + 1 );
    $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">postAuth>active>true", $t, "Wrong trace" );

    CardfileSqlt::destroy();
    
    echo("\nsending deactivate command\n");
    $input=["reg"=>"deact"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">active>reg_id>postAuth>false", $t, "Wrong trace" );    

    CardfileSqlt::destroy();
    
    echo("\ntest repeated deact request, expecting redirect\n");
    $input=["reg"=>"deact"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals  ( ">postAuth>redirect>false", $t, "Wrong trace" );    
    
    CardfileSqlt::destroy();
    
    echo("\ntesting cross-realm page request in postAuth, expecting transit to preAuth\n");
    $art["realm"]="test";
    $art["targetPath"]="../";
    $nb=$session["notBefore"];
    sleep(1);
    $input=[];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">postAuth>zero>reg_id>preAuth>false", $t, "Wrong trace" );
    $this->assertContains ("new thread",$ar->g("alert"),"Wrong message" );
    $this->assertGreaterThan($nb,$session["notBefore"],"Stalled notBefore");

    CardfileSqlt::destroy();
    
    echo("\nTest delays OK\n");
  }
  
  public function test_nonadmin() {
    $verbose=0;
    $art = $this->authRegistryTemplate;
    
    echo("\ncreating non-admin user user/abc\n");
    $userName="user";
    $userPsw="abc";
    UserManager::init($art["targetPath"],"test");
    $ret=UserManager::manageUser("add","",$userName,"test",$userPsw);
    $ok=( $ret==="" || $ret==="This user already exists" );
    $this->assertTrue($ok,"Failed to create/find non-admin user");
    
    $input=[];
    $session=[];
    $ar=null;
          
    echo("\ngetting the form\n");
    $input=[];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">zero>preAuth>false", $t, "Wrong trace" );
    $this->assertNotEmpty ($session["serverNonce"], "Empty serverNonce" );
    
    echo("\nregistering as user/abc, expecting success\n");
    sleep( $art["minDelay"] + 1 );
    $input=["reg"=>"authOpp","plain"=>1,"user"=>$userName,"ps"=>$userPsw];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">preAuth>reg_id>active>true", $t, "Wrong trace" );
    $this->assertEquals ( $userName, $session["authName"], "Wrong authName" );
    $notAdmin=( !isset($session["isAdmin"]) || !$session["isAdmin"] );
    $this->assertTrue ($notAdmin,"Unappropriate isAdmin flag");
    
    echo("\ngetting a page, expecting success\n");
    $input=[];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">active>true", $t, "Wrong trace" );
      
    echo("\ntrying to get an admin page, expecting failure\n");
    $art["isAdminArea"]=1;
    $input=[];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">active>preAuth>false", $t, "Wrong trace" );
    
    echo("\nlogging in as admin/admin, expecting success\n");
    sleep( $art["minDelay"] + 1 );
    $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">preAuth>reg_id>active>true", $t, "Wrong trace" );
    $isAdmin=( isset($session["isAdmin"]) && $session["isAdmin"] );
    $this->assertTrue ($isAdmin,"Missing isAdmin flag");
        
    echo("\nTest non-admin login OK\n");
  }
  
  public function test_blockPlaintext() {
    $verbose=0;
    $art = $this->authRegistryTemplate;

    $input=[];
    $session=[];
    $ar=null;
          
    echo("\ngetting the form\n");
    $input=[];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">zero>preAuth>false", $t, "Wrong trace" );
    $this->assertNotEmpty ($session["serverNonce"], "Empty serverNonce" );
    
    echo("\ntrying plaintext auth in Opportunistic mode without the flag, expecting failure\n");
    $art["authMode"]=1;
    sleep( $art["minDelay"] + 1 );
    $input=["reg"=>"authOpp","plain"=>0,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">preAuth>preAuth>false", $t, "Wrong trace" );
    
    echo("\ntry plaintext auth in digest-only mode, expecting failure\n");
    $art["authMode"]=2;
    sleep( $art["minDelay"] + 1 );
    $input=["reg"=>"authOpp","plain"=>1,"user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">preAuth>preAuth>false", $t, "Wrong trace" );
    
    echo("\ntry plaintext auth in digest-only mode, expecting failure\n");
    $art["authMode"]=2;
    sleep( $art["minDelay"] + 1 );
    $input=["reg"=>"authPlain","user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">preAuth>preAuth>false", $t, "Wrong trace" );    
    
    echo("\ntry plaintext auth in plaintext-only mode, expecting success\n");
    $art["authMode"]=0;
    sleep( $art["minDelay"] + 1 );
    $input=["reg"=>"authPlain","user"=>"admin","ps"=>"admin"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">preAuth>reg_id>active>redirect>false", $t, "Wrong trace" );
    $this->assertEquals ( "admin", $session["authName"], "Wrong authName" );
    
    echo("\ntry cross-realm page request in active state, expecting fallout to preAuth\n");
    $art["realm"]="demo";
    $art["targetPath"]="../../demo/";
    sleep( 1 );
    $input=["act"=>"new"];
    page($art,$input,$session,$ar,$verbose);
    $t=$ar->g("trace");
    $this->assertEquals ( ">active>preAuth>false", $t, "Wrong trace" );
    $this->assertFalse ( isset($session["authName"]), "Remaining authName in SESSION" );
    
    echo ("\nTest blocking plaintext auth OK\n");      
  }
}    
?>