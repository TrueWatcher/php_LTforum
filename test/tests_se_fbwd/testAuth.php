<?php
// Web-Test suite for LTforum Autentication (since v.1.2)
// All PHPUnit tests stop after first failure!
// Uses PHPUnit + Selenium + FacebookWebDriver + HtmlUnit
// And optionally XAMPP
// by TrueWatcher August 2016

//require_once("/home/alexander/vendor/autoload.php" );// needed, but present in ./bootstrap.php

// important! (https://github.com/facebook/php-webdriver/blob/community/example.php)
//namespace Facebook\WebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
//use PHPUnit\Framework\TestCase;

class Test_LTforumAuthentication extends PHPUnit_Framework_TestCase {

  protected $browser="htmlunit";
  protected $emulate="FIREFOX_45";// needed for JQuery and/or Bootstrap
  // http://htmlunit.sourceforge.net/apidocs/index.html class
  // com.gargoylesoftware.htmlunit   Class BrowserVersion
  protected $JSenabled=true;//true;//false;;
  protected $webDriver;


  public function setUp() {
    //$capabilities = array(\WebDriverCapabilityType::BROWSER_NAME => 'firefox');
    //$this->webDriver = RemoteWebDriver::create('http://localhost:4444/wd/hub', $capabilities);
    $host = 'http://localhost:4444/wd/hub'; // this is the default
    $this->webDriver = RemoteWebDriver::create(
        $host,
        array(
            WebDriverCapabilityType::BROWSER_NAME => $this->browser,
            WebDriverCapabilityType::JAVASCRIPT_ENABLED => $this->JSenabled,
            WebDriverCapabilityType::VERSION => $this->emulate
        )
     );
  }

  public function tearDown() {
    $this->webDriver->quit();
  }

  static private $adminUri="http://LTforum/rulez.php?forum=test";
  //"http://fs..net/new_ltforum/test/";//
  static private $homeUri="http://LTforum/test";
  static private $resetUri="http://LTforum/rulez.php?forum=test&reg=reset";
  static private $storedUserName="Me";
  static private $storedUserPassword="1234";
  static private $storedAdminName="admin";
  static private $storedAdminPassword="admin";
  static private $storedForum="test";
  static private $storedQuery="";

  private function loginAs($user,$password) {
    print ("\r\nLogging in as ".$user."/".$password." " );
    $inputUser=$this->webDriver->findElement(WebDriverBy::name("user"));
    $inputUser->sendKeys($user);
    $inputPs=$this->webDriver->findElement(WebDriverBy::name("ps"));
    $inputPs->sendKeys($password);
    $inputPs->submit();
  }

  public function test_loginPage() {
    print ("\r\n! Browser: {$this->browser} as {$this->emulate}, JavaScript is ");
    if ($this->JSenabled) print ("ON !");
    else print ("OFF !");
    //$this->webDriver->manage()->clearAppCache();
    print ("\r\nSending request for ".self::$resetUri."...");
    $this->webDriver->get(self::$resetUri);
    print ("processing page...");
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    else print (" title not found!\r\n");
    $this->assertNotEmpty($title,"Failed to connect to the site");
    $this->assertContains("Login",$title,"no <Login> in the title");
    $this->assertContains(self::$storedForum,$title,"no forumName in the title");
    print("\r\nInfo: login page found");

    $inputUser=$this->webDriver->findElement(WebDriverBy::name("user"));
    $this->assertNotEmpty($inputUser,"No <user> input -- wrong page");
    $inputPlain=$this->webDriver->findElement(WebDriverBy::name("plain"));
    $this->assertNotEmpty($inputPlain,"No <plain> checkbox -- please set authForm to 1");
    print("\r\nInfo: assuming Digest mode");
    //$inputPlain->click();
    //print("\r\nInfo: trying Plaintext mode");
    sleep(10);
    $this->loginAs(self::$storedUserName,self::$storedUserPassword);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    else print (" Error! failed to connect ");
    $this->assertContains("Login",$title,"Wrong response to non-admin login");
    $inputUser=$this->webDriver->findElement(WebDriverBy::name("user"));
    $this->assertNotEmpty($inputUser,"Wrong response to non-admin login");
    print("\r\nInfo: non-admin login test Ok");

    sleep(1);
    $this->loginAs(self::$storedAdminName,self::$storedAdminPassword);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertContains("alert",$title,"Wrong response to fast login");
    print("\r\nInfo: fast admin login test Ok");

    $this->webDriver->get(self::$resetUri);
    sleep(10);
    //$this->webDriver->findElement(WebDriverBy::name("plain"))->click();
    $this->loginAs(self::$storedAdminName,self::$storedAdminPassword);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    //$source=$this->webDriver->getPageSource();
    //print ($source);
    $this->assertNotContains("Login",$title,"Failed admin login");
    print("\r\nInfo: admin login Ok");

    $logOutLink=$this->webDriver->findElement( webDriverBy::partialLinkText("Log out") );
    $logOutLink->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertContains("Login",$title,"no <Login> in the title");
    print("\r\nInfo: admin panel logout Ok");
  }

  public function test_passCommands() {
    // pass RESET and LIST_USERS on registration to admin panel
    $resetUriPlusLu=self::$resetUri."&act=lu";
    $this->webDriver->get($resetUriPlusLu);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    else print (" title not found!\r\n");
    $this->assertContains("Login",$title," no login page");
    sleep(10);
    $this->loginAs(self::$storedAdminName,self::$storedAdminPassword);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    else print (" title not found!\r\n");
    $this->assertNotContains("Login",$title," failed login to adminPanel");
    // check admin's name in uaerList
    $userList=$this->webDriver->findElement(WebDriverBy::id("userList"))->getAttribute("value");
    $this->assertContains(self::$storedAdminName,$userList," No my name in userList !");
    print("\r\nInfo: passing command to admin panel Ok");

    // reset
    $this->webDriver->get(self::$homeUri."?reg=reset");
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    else print (" title not found!\r\n");
    sleep(10);

    // pass NEW on registration to forum
    $this->webDriver->get(self::$homeUri."?act=new");
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    else print (" title not found!\r\n");
    sleep(10);
    $this->loginAs(self::$storedAdminName,self::$storedAdminPassword);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    else print (" title not found!\r\n");
    $this->assertContains("new message",$title," The NEW command was lost ");
    print("\r\nInfo: passing command to forum Ok");

  }

  public function test_userManager() {
    // get login page
    $this->webDriver->get(self::$adminUri);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    //$this->assertNotEmpty($title,"Failed to connect to the site");
    $this->assertContains("Login",$title,"no <Login> in the title");
    $this->assertContains(self::$storedForum,$title,"no forumName in the title");
    print("\r\nInfo: login page found");

    // set Plaintext mode and log in as admin/admin
    $inputPlain=$this->webDriver->findElement(WebDriverBy::name("plain"));
    $this->assertNotEmpty($inputPlain,"No <plain> checkbox -- please set authForm to 1");
    $inputPlain->click();
    sleep(10);
    $this->loginAs(self::$storedAdminName,self::$storedAdminPassword);
    print("\r\nInfo: registering in Plaintext mode");

    // try generating user entry for Me/1234
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $formManUser=$this->webDriver->findElement(WebDriverBy::id("manUser"));
    $inputUser=$this->webDriver->findElement(WebDriverBy::name("user"));
    $inputUser->sendKeys(self::$storedUserName);
    $inputPs=$this->webDriver->findElement(WebDriverBy::name("ps"));
    $inputPs->sendKeys(self::$storedUserPassword);
    //$formManUser->submit(); // NO submit! just click on buttons
    $buttonGen=$this->webDriver->findElement(WebDriverBy::id("genEntry"));
    $buttonGen->click();
    $areaUEntry=$this->webDriver->findElement(WebDriverBy::id("uEntry"));
    $this->assertContains(self::$storedUserName."=",$areaUEntry->getAttribute("value"),"failed to generate user's entry");

    // add new user Me/1234
    $buttonAdd=$this->webDriver->findElement(WebDriverBy::id("uAdd"));
    $buttonAdd->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertNotContains("alert",$title,"test user ".self::$storedUserName." probably already exists, remove that entry manually");
    $userList=$this->webDriver->findElement(WebDriverBy::id("userList"))->getAttribute("value");
    $this->assertContains(self::$storedUserName,$userList,"no userName ".self::$storedUserName." in the users list");
    print("\r\nInfo: adding user Ok");

    // try add Me/1234 once again
    $inputUser=$this->webDriver->findElement(WebDriverBy::name("user"));
    $inputUser->sendKeys(self::$storedUserName);
    $inputPs=$this->webDriver->findElement(WebDriverBy::name("ps"));
    $inputPs->sendKeys(self::$storedUserPassword);
    $buttonAdd=$this->webDriver->findElement(WebDriverBy::id("uAdd"));
    $buttonAdd->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertContains("alert",$title,"wrong response to repeated user add");
    print("\r\nInfo: repeated user test Ok");

    // this must be an alert page
    $okLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("Ok"));
    $okLink->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");

    // promote Me/1234 to admin
    $formManAdmin=$this->webDriver->findElement(WebDriverBy::id("manAdmin"));
    $inputAUser=$this->webDriver->findElement(WebDriverBy::name("aUser"));
    $inputAUser->sendKeys(self::$storedUserName);
    $buttonAAdd=$this->webDriver->findElement(WebDriverBy::id("aAdd"));
    $buttonAAdd->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $adminList=$this->webDriver->findElement(WebDriverBy::id("adminList"))->getAttribute("value");
    $this->assertContains(self::$storedUserName,$userList,"no userName ".self::$storedUserName." in the admins list");
    print("\r\nInfo: promoting to admin Ok");

    // log out admin/admin
    $logOutLink=$this->webDriver->findElement( webDriverBy::partialLinkText("Log out") );
    $logOutLink->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
        
    sleep(10);
    // try logging in as Me, the new-born admin
    $this->loginAs(self::$storedUserName,self::$storedUserPassword);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertNotContains("Login",$title,"failed login");
    $this->assertNotContains("alert",$title,"failed login");

    // decouple from reg=reset, shoud not be needed
    $this->webDriver->get(self::$adminUri);
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");

    // remove Me from admins
    $formManAdmin=$this->webDriver->findElement(WebDriverBy::id("manAdmin"));
    $inputAUser=$this->webDriver->findElement(WebDriverBy::name("aUser"));
    $inputAUser->sendKeys(self::$storedUserName);
    $buttonADel=$this->webDriver->findElement(WebDriverBy::id("aDel"));
    $buttonADel->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    //$this->assertContains("Login",$title,"wrong response to current admin deletion");
    $adminList=$this->webDriver->findElement(WebDriverBy::id("adminList"))->getAttribute("value");
    $this->assertNotContains(self::$storedUserName,$adminList,"userName ".self::$storedUserName." remained in the admins list");
    print("\r\nInfo: resign from admin Ok");

    // log out Me/1234
    $logOutLink=$this->webDriver->findElement( webDriverBy::partialLinkText("Log out") );
    $logOutLink->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertContains("Login",$title,"failed logout");
    //$this->webDriver->get(self::$adminUri);
    //$title=$this->webDriver->getTitle();
    //if (strlen($title)) print ("\r\ntitle found: $title \r\n");

    // log in again as admin/admin
    sleep(10);
    $this->loginAs(self::$storedAdminName,self::$storedAdminPassword);

    // check that Me/1234 has been removed and admin is the only administrator
    $buttonAdminsList=$this->webDriver->findElement(WebDriverBy::partialLinkText("admins list"));
    $buttonAdminsList->click();
    $adminList=$this->webDriver->findElement(WebDriverBy::id("adminList"))->getAttribute("value");
    $this->assertEquals(self::$storedAdminName,$adminList,"there are some extra admins, delete the group file and repeat this test");

    // try to remove admin/admin from admins
    $formManAdmin=$this->webDriver->findElement(WebDriverBy::id("manAdmin"));
    $inputAUser=$this->webDriver->findElement(WebDriverBy::name("aUser"));
    $inputAUser->sendKeys(self::$storedAdminName);
    $buttonADel=$this->webDriver->findElement(WebDriverBy::id("aDel"));
    $buttonADel->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertContains("alert",$title,"wrong response to the-only-admin downgrade");
    print("\r\nInfo: the-only-admin downgrade test Ok");

    // this must be an alert page
    $okLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("Ok"));
    $okLink->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");

    // remove Me/1234 from users
    $inputUser=$this->webDriver->findElement(WebDriverBy::name("user"));
    $inputUser->sendKeys(self::$storedUserName);
    $inputPs=$this->webDriver->findElement(WebDriverBy::name("ps"));
    $inputPs->sendKeys(self::$storedUserPassword);
    $buttonUDel=$this->webDriver->findElement(WebDriverBy::id("uDel"));
    $buttonUDel->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $userList=$this->webDriver->findElement(WebDriverBy::id("userList"))->getAttribute("value");
    $this->assertNotContains(self::$storedUserName,$userList," userName ".self::$storedUserName." remained in the users list");
    print("\r\nInfo: removing user ".self::$storedUserName." Ok");

    // add more users for tests to follow
    $this->addUser("test","q");
    $this->addUser("Test Robot","qq");
    $this->addUser("Test Pagination","tp");
    $this->addUser("Editor","eee");
    $this->webDriver->findElement(WebDriverBy::name("aUser"))->sendKeys("Editor");
    $this->webDriver->findElement(WebDriverBy::id("aAdd"))->click();

  }

  private function addUser($login,$password) {
    $inputUser=$this->webDriver->findElement(WebDriverBy::name("user"));
    $inputUser->sendKeys($login);
    $inputPs=$this->webDriver->findElement(WebDriverBy::name("ps"));
    $inputPs->sendKeys($password);
    $buttonUAdd=$this->webDriver->findElement(WebDriverBy::id("uAdd"));
    $buttonUAdd->click();
  }

  public function test_crossThread() {
    $anotherThread="demo";
    $anotherAdminUri=str_replace( "=".self::$storedForum, "=".$anotherThread, self::$adminUri );
    $anotherUri=str_replace( "rulez.php?forum=".self::$storedForum, $anotherThread, self::$adminUri );
    $this->webDriver->get($anotherUri);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    else print (" title not found!\r\n");
    $this->assertNotEmpty($title,"Failed to connect to the thread ".$anotherThread);
    $this->assertContains("Login",$title,"no <Login> in the title");
    $this->assertContains($anotherThread,$title,"no forumName in the title");
    print("\r\nInfo: another login page found ");

    sleep(10);
    $this->loginAs(self::$storedAdminName,self::$storedAdminPassword);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertNotContains("Login",$title," failed login to ".$anotherThread);
    print("\r\nInfo: logged in to another thread ");

    $this->webDriver->get($anotherAdminUri);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    else print (" title not found!\r\n");
    $this->assertNotContains("Login",$title," failed transit to adminPanel ");
    print("\r\nInfo: transit to another thread's adminPanel ");

    $this->webDriver->get(self::$adminUri);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    else print (" title not found!\r\n");
    $this->assertContains("Login",$title," wrong response to cross-realm request");
    print("\r\nInfo: cross-realm request test Ok ");

    sleep(10);
    $this->loginAs(self::$storedAdminName,self::$storedAdminPassword);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertNotContains("Login",$title," failed login to ".$anotherThread);
    print("\r\nInfo: logged in to the ".self::$storedForum." adminPanel");

    $this->webDriver->get(self::$resetUri);
  }
  
  public function test_postAuth() {
    // get login page
    $this->webDriver->get(self::$homeUri);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    //$this->assertNotEmpty($title,"Failed to connect to the site");
    $this->assertContains("Login",$title,"no <Login> in the title");
    $this->assertContains(self::$storedForum,$title,"no forumName in the title");
    print("\r\nInfo: login page found");
    
    // log in normally
    sleep(10);
    //$this->webDriver->findElement(WebDriverBy::name("plain"))->click();
    $this->loginAs(self::$storedAdminName,self::$storedAdminPassword);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertNotContains("Login",$title,"Failed admin login");
    print("\r\nInfo: admin login Ok");
    
    // click Log out
    $logOutLink=$this->webDriver->findElement( webDriverBy::partialLinkText("Log out") );
    $logOutLink->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertContains("Login",$title,"Failed logout");
    print("\r\nInfo: logout to postAuth Ok");

    // request a forum page again
    $this->webDriver->get(self::$homeUri);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $source=$this->webDriver->getPageSource();
    $this->assertContains("Unanswered: 0",$source,"No info found");
    print("\r\nInfo: verify postAuth info Ok");
    
    // log in as another user
    sleep(10);
    $this->webDriver->findElement(WebDriverBy::name("plain"))->click();
    $this->loginAs("test","q");
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertNotContains("Login",$title,"Failed user login");
    print("\r\nInfo: another user login Ok");
    
  }

}

?>
