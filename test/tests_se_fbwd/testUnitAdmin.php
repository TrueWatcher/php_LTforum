<?php
// Unit tests for LTadmin since version 1.4

$ep=[
  "mainPath"=>"../../LTforum/",/* relative to here */
  "templatePath"=>"../../LTforum/templates/",/* relative to here */
  "assetsPath"=>"../../assets/",/* relative to here */
  "forumsPath"=>"../../"/* relative to here */
];

$forum="test";

$mainPath=$ep["mainPath"];
require_once ($mainPath."AssocArrayWrapper.php");
require_once ($mainPath."Registries.php");
require_once ($mainPath."CardfileSqlt.php");
require_once ($mainPath."Act.php");
require_once ($mainPath."MyExceptions.php");
require_once ($mainPath."AdminAct.php");
require_once ($mainPath."AccessHelper.php");
require_once ($mainPath."AccessController.php");
require_once ($mainPath."Applicant.php");
require_once ($mainPath."UserManager.php");
require_once ($mainPath."Translator.php");

function page($input,$entryParams) {
  $verbose=1;
  $session=[];// it is not used by any of admin functions
  
  $adminDefaults=SessionRegistry::getDefaultsBackend();
  $asr=SessionRegistry::getInstance(1,$adminDefaults);
  $asr->overrideValuesBy($entryParams);
  $apr=PageRegistry::getInstance( 0,[] );
  $apr->initAdmBeforeAuth($input, $session, $asr, "Act", $asr->g("adminTitle"));
  $apr->initAdmAfterAuth($asr);

  $ret=AdminAct::go($apr,$asr);
  if ($ret===false) $ret=UserManager::go($apr,$asr);

  if ($ret===false ) {
    if ( $apr->g("act") ) {
      $ret=AdminAct::showAlert ("Unknown admin command:".$apr->g("act"));
    }
    else {
      $ret=ViewRegistry::getInstance(2, ViewRegistry::getAdminDefaults());
    }
  }

  if ( ! $ret instanceof ViewRegistry) {
    var_dump($ret);
    throw new UsageException ("Non-object result");  
  }
  if($verbose) {
    if($ret->checkNotEmpty("alert")) echo("Alert_vr : ".$ret->g("alert")."\n");
    if($apr->checkNotEmpty("alert")) echo("Alert_apr : ".$apr->g("alert")."\n");
  }
  
  $r=$ret->export();
  $ret->clearInstance();
  $asr->clearInstance();
  $apr->clearInstance();
  CardfileSqlt::destroy();
  return($r);
}

function makeUserEntry($userName,$realm,$password) {
  $ha=AccessHelper::makeHa1($userName,$realm,$password);
  $entry=$userName."=".$ha;//.$nl;
  return $entry;
}

AccessHelper::createEmptyGroupFile($ep["forumsPath"].$forum."/", $forum);

echo("\nUnit tests for LTadmin, AdminAct and UserManager\n\n");

class testUnitAdmin_basic extends PHPUnit_Framework_TestCase {

  public function testBasicFunctions() {
    global $ep;
    
    echo("\nTrying LIST USERS\n");
    $i=["forum"=>"test","act"=>"lu"];
    $r=page($i,$ep);
    //var_dump($r);
    $userList=$r["userList"];
    echo("Got : $userList\n");
    $this->assertContains("admin",$userList,"No ADMIN in the user list");

    echo("\nTrying LIST ADMINS\n");
    $i=["forum"=>"test","act"=>"la"];
    $r=page($i,$ep);
    //var_dump($r);
    $adminList=$r["adminList"];
    echo("Got : $adminList\n");
    $this->assertContains("admin",$adminList,"No ADMIN in the admin list");

    echo("\nTrying EDIT ANY MESSAGE\n");
    $n=1;
    $i=["forum"=>"test","act"=>"ua","current"=>$n,"txt"=>"This is the TEST message","author"=>"admin"];
    $r=page($i,$ep);
    $alert=$r["alert"];
    echo("Got : $alert\n");
    $this->assertContains("been updated",$alert,"Update failed");
    $this->assertContains("Message ".$n,$alert,"No message number=".$n." in the response");
    
    echo("\nTest of basic admin commands OK\n");
  }
  
  public function testAddPromoteDeleteUsers() {
    global $ep;
    global $forum;
    
    $name="UnitTestUser";
    $psw="UTU";
    //$forum="test";
    
    echo("\nTrying to generate hash\n");
    $entryUTU=makeUserEntry($name,$forum,$psw);
    echo("Got : $entryUTU\n");
    $this->assertContains($name."=",$entryUTU."Wrong entry");
    
    echo("\nTrying LIST USERS\n");
    $i=["forum"=>"test","act"=>"lu"];
    $r=page($i,$ep);
    $userList=$r["userList"];
    echo("Got : $userList\n");
    $this->assertNotContains($name,$userList,"Target user $name exists, remove him manually");    
    
    echo("\nTrying ADD USER\n");
    $i=["forum"=>$forum,"realm"=>$forum,"act"=>"uAdd","uEntry"=>$entryUTU];
    $r=page($i,$ep);
    //var_dump($r);
    $userList=$r["userList"];
    echo("Got : $userList\n");
    $this->assertContains($name,$userList,"No $name in the user list");

    echo("\nTrying LIST ADMINS\n");
    $i=["forum"=>"test","act"=>"la"];
    $r=page($i,$ep);
    //var_dump($r);
    $adminList=$r["adminList"];
    echo("Got : $adminList\n");
    $this->assertNotContains($name,$adminList,"Non-admin $name in the admin list");

    echo("\nTrying ADD TO ADMINS\n");
    $i=["forum"=>$forum,"act"=>"aAdd","aUser"=>$name];
    $r=page($i,$ep);
    $adminList=$r["adminList"];
    echo("Got : $adminList\n");
    $this->assertContains($name,$adminList,"No promoted $name in the admin list");
    
    echo("\nTrying REMOVE USER with wrong hash, expecting failure\n");
    $i=["forum"=>$forum,"realm"=>$forum,"act"=>"uDel","uEntry"=>$entryUTU."extra"];
    $r=page($i,$ep);
    //var_dump($r);
    $alert=$r["alert"];
    echo("Got : $alert\n");
    $this->assertContains("invalid entry",$alert,"Wrong or empty alert"); 
    
    echo("\nTrying REMOVE USER\n");
    $i=["forum"=>$forum,"realm"=>$forum,"act"=>"uDel","uEntry"=>$entryUTU];
    $r=page($i,$ep);
    //var_dump($r);
    $userList=$r["userList"];
    echo("Got : $userList\n");
    $this->assertNotContains($name,$userList,"Persistent $name in the user list");    
    
    echo("\nTrying LIST ADMINS\n");
    $i=["forum"=>"test","act"=>"la"];
    $r=page($i,$ep);
    //var_dump($r);
    $adminList=$r["adminList"];
    echo("Got : $adminList\n");
    $this->assertNotContains($name,$adminList,"Persistent $name in the admin list");
    
    echo("\nTrying to remove the only admin admin/admin, expecting failure\n");
    $entryAdmin=makeUserEntry("admin",$forum,"admin");
    $i=["forum"=>$forum,"realm"=>$forum,"act"=>"uDel","uEntry"=>$entryAdmin];
    $r=page($i,$ep);
    //var_dump($r);
    $alert=$r["alert"];
    echo("Got : $alert\n");
    $this->assertContains("not remove",$alert,"Wrong or empty alert");
    
    echo ("\nAdding UnitTestUser/UTU again\n");
    $i=["forum"=>$forum,"realm"=>$forum,"act"=>"uAdd","uEntry"=>$entryUTU];
    $r=page($i,$ep);
    $userList=$r["userList"];
    echo("Got : $userList\n");
    
    /*//Uncomment to see the admin/admin removed and next test failed
    echo("\nTrying ADD TO ADMINS\n");
    $i=["forum"=>$forum,"act"=>"aAdd","aUser"=>$name];
    $r=page($i,$ep);
    $adminList=$r["adminList"];
    echo("Got : $adminList\n");
    $this->assertContains($name,$adminList,"No promoted $name in the admin list");*/    
    
    echo("\nTrying again to remove the only admin admin/admin, expecting failure\n");
    $entryAdmin=makeUserEntry("admin",$forum,"admin");
    $i=["forum"=>$forum,"realm"=>$forum,"act"=>"uDel","uEntry"=>$entryAdmin];
    $r=page($i,$ep);
    //var_dump($r);
    $alert=$r["alert"];
    echo("Got : $alert\n");
    $this->assertContains("not remove",$alert,"Wrong or empty alert");    
    
    echo("\nTest of add-promote-delete users OK\n");
  }
}
?>