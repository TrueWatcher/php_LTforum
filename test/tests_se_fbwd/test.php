<?php
// Web-Test suite for Mail Inventory Module
// All PHPUnit tests stop after first failure!
// Uses PHPUnit + Selenium + FacebookWebDriver + HtmlUnit
// And optionally XAMPP
// (c) TrueWatcher August 2016
// succesfully tested mod_mail_inventory v.1.2 August 2016

//require_once("/home/alexander/vendor/autoload.php" );// needed, but present in ./bootstrap.php

// important! (https://github.com/facebook/php-webdriver/blob/community/example.php)
//namespace Facebook\WebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
use Facebook\WebDriver\WebDriverBy;
//use PHPUnit\Framework\TestCase;

class Test_modMailInventoryHelper extends PHPUnit_Framework_TestCase {

  protected $browser="htmlunit";
  protected $emulate="FIREFOX_45";// needed for JQuery and/or Bootstrap
  // http://htmlunit.sourceforge.net/apidocs/index.html class 
  // com.gargoylesoftware.htmlunit   Class BrowserVersion
  protected $JSenabled=true;//true;//false;;
  protected $webDriver;
  protected $homeUri="http://LTforum/CHAT/test/";
  //protected $formPath="";
  //protected $logPath="modules/mod_mail_inventory/mail_inventory_log.csv";
  //protected $delay=6;
      
  //protected $inputs_full=array("Name"=>"Иванов Пётр Сидорович","BirthDate"=>"01.02.1979","BirthPlace"=>"Washington DC","PlaceofResidence"=>"Москва сразу направо","ContactPhone"=>"999-888-77-00","Email"=>"ivanov@ivanov.me","ContactName"=>"Сидорова Мария Ивановна тёща 123-456-78-90","TextHealth"=>"не дождётесь","TextEquipment"=>"только котелок","TextWishes"=>"хлеба и зрелищ","AboutUs"=>"сказали по радио");  
 
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
  
  static private $storedUsername="";
  static private $storedTotal=0;
  static private $storedMsg="";
  
  public function test_mainPage() {
    print ("\r\n! Browser: {$this->browser} as {$this->emulate}, JavaScript is ");
    if ($this->JSenabled) print ("ON !");
    else print ("OFF !");
    print ("\r\nSending request for ".$this->homeUri."..."); 
    $this->webDriver->get($this->homeUri);
    print ("processing page..."); 
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    else print ("title not found!\r\n");
    $this->assertNotEmpty($title,"Failed to connect to the site");
    print("Info: first page OK");
  }
  
  public function test_Add() {
    $this->webDriver->get($this->homeUri);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertNotEmpty($title,"Failed to connect to the site");
    $forum=explode(":",$title)[0];
    $nn=explode("..",$title)[1];
    $lastMsg=explode(" ",$nn)[0];
    //print("\r\nForum:$forum Total:$lastMsg\r\n");
    
    $me="Robot_".time();
    $msg="My first test message_".time();
    $addLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("Write"));
    $this->assertNotEmpty($addLink,"A WRITE link not found");
    $addLink->click();
    
    $title_new=$this->webDriver->getTitle();
    if (strlen($title_new)) print ("\r\ntitle found: $title_new \r\n");
    $this->assertContains("new message",$title_new,"Missed WRITE NEW page");
    $inputAuthor=$this->webDriver->findElement(WebDriverBy::name("user"));
    $this->assertNotEmpty($addLink,"A USER field not found");
    $inputAuthor->sendKeys($me);
    $inputText=$this->webDriver->findElement(WebDriverBy::name("txt"));
    $this->assertNotEmpty($inputText,"A MESSAGE field not found");
    $inputText->sendKeys($msg);
    $subm=$this->webDriver->findElement(WebDriverBy::xpath('//input[@type="submit"]'));
    //print('Button '.$subm->getAttribute("value"));
    $this->assertNotEmpty($subm,"A SUBMIT field not found");
    print("Info: submit new message OK");
    $subm->submit();
    sleep(1);
    
    $title_back=$this->webDriver->getTitle();
    if (strlen($title_back)) print ("\r\ntitle found: $title_back \r\n");
    $forumSame=(strpos($title_back,$forum)===0);
    $this->assertTrue($forumSame,"Not came back to View");
    $nn=explode("..",$title_back)[1];
    $lastMsg2=explode(" ",$nn)[0];
    $this->assertEquals($lastMsg+1,$lastMsg2,"Not came back to View or new message not counted");
    $addr=$this->webDriver->findElement(WebDriverBy::xpath('//address[last()]'));
    //print($addr->getText());
    $authorSame=(strpos($addr->getText(),$me)===0);
    $this->assertTrue($authorSame,"My good name not found");
    $mess=$this->webDriver->findElements(WebDriverBy::xpath('//p[@class="m"]'));
    $mes=end($mess);
    //print( $mes->getText() );    
    $msgSame=(strpos($mes->getText(),$msg)===0);
    $this->assertTrue($msgSame,"My good message not found");
    self::$storedUsername=$me;
    self::$storedTotal=$lastMsg2;
    print("Info: add message OK"); 
  }
  
  public function test_Edit() {
    $me=self::$storedUsername;
    $qs="?user=".$me;
    $this->webDriver->get( ($this->homeUri).$qs );
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertNotEmpty($title,"Failed to connect to the site");
    $total=self::$storedTotal;
    $this->assertContains("..".$total." ",$title,"Invalid or missing total number");
    $editLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("§"));
    $this->assertNotEmpty($editLink,"An EDIT link not found");
    $editLink->click();
    sleep(1);
    
    $title_edit=$this->webDriver->getTitle();
    if (strlen($title_edit)) print ("\r\ntitle found: $title_edit \r\n");
    $this->assertContains("edit message ".$total,$title_edit,"Missed EDIT LAST page or wrong message number");
    
    $t=(string)time();
    //$msg="My_[u]second[/u],".$t."<br /> <i>second</i> <br>te<st <message> yes <s>yes";
    $msg="my additional te<st <message> yes ".$t;
    $inputText=$this->webDriver->findElement(WebDriverBy::name("txt"));
    $this->assertNotEmpty($inputText,"A MESSAGE field not found");
    $inputText->sendKeys($msg);
    //$msgC="My_second,".$t."<br /> <i>second</i> <br>comment";
    $msgC="My good <co>mment ".$t;
    $inputComm=$this->webDriver->findElement(WebDriverBy::name("comm"));
    $this->assertNotEmpty($inputComm,"A COMMENT field not found");
    $inputComm->sendKeys($msgC);
    $subm=$this->webDriver->findElement(WebDriverBy::xpath('//input[@type="submit"]'));    
    $this->assertNotEmpty($subm,"A SUBMIT field not found");
    print("Info: submit edited message OK");
    $subm->submit();
    sleep(1);    
    
    $title_back=$this->webDriver->getTitle();
    if (strlen($title_back)) print ("\r\ntitle found: $title_back \r\n");
    $nn=explode("..",$title_back)[1];
    $lastMsg3=explode(" ",$nn)[0];
    $this->assertContains("..".$total." ",$title,"Not came back to View or changed total number");
    $addr=$this->webDriver->findElement(WebDriverBy::xpath('//address[last()]'));
    //print($addr->getText());
    $authorSame=(strpos($addr->getText(),$me)===0);
    $this->assertTrue($authorSame,"My good name missed");
    $mess=$this->webDriver->findElements(WebDriverBy::xpath('//p[@class="m"]'));
    $mes=end($mess);
    //print( $mes->getText() );
    $retr=$mes->getText();
    $this->assertContains($t,$retr,"My new message not found");
    /*$this->assertContains("<u>",$retr,"BBCode error");
    $this->assertContains("<br>",$retr,"br tag error");
    $this->assertContains("<i>",$retr,"i tag error");
    $this->assertContains("te&lt;st",$retr,"lone LT error");
    $this->assertContains("&lt;message>",$retr,"custom tag error");
    $this->assertContains("</s>",$retr,"auto close tag error");*/
    //$comSame=(strpos($retr,$msg)>0);
    //$this->assertTrue($comSame,"Message is altered");
    $this->assertContains($msg,$retr,"Message is altered");
    $coms=$this->webDriver->findElements(WebDriverBy::xpath('//p[@class="n"]'));
    $com=end($coms);
    $retC=$com->getText();
    $this->assertContains($t,$retC,"My new comment not found");
    $comSame=(strpos($msgC,$retC)===0);
    $this->assertTrue($comSame,"Comment is altered");
    print("Info: tag filter works, edit OK");
    self::$storedMsg=$msg;
  }
    
  public function test_Delete() {
    // same as previous
    $me=self::$storedUsername;
    $qs="?user=".$me;
    $this->webDriver->get( ($this->homeUri).$qs );
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertNotEmpty($title,"Failed to connect to the site");
    $total=self::$storedTotal;
    $this->assertContains("..".$total." ",$title,"Invalid or missing total number");
    $editLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("§"));
    $this->assertNotEmpty($editLink,"An EDIT link not found");
    $editLink->click();
    sleep(1);
    
    $title_edit=$this->webDriver->getTitle();
    if (strlen($title_edit)) print ("\r\ntitle found: $title_edit \r\n");
    $this->assertContains("edit message ".$total,$title_edit,"Missed EDIT LAST page or wrong message number");
    // come to work
    $delBox=$this->webDriver->findElement(WebDriverBy::name("del"));
    $this->assertNotEmpty($delBox,"A DELETE field not found");
    $delBox->click();
    $subm=$this->webDriver->findElement(WebDriverBy::xpath('//input[@type="submit"]'));    
    $this->assertNotEmpty($subm,"A SUBMIT field not found");
    $subm->submit();
    sleep(1);
    print("Info: submit delete request OK");
    
    $title_back=$this->webDriver->getTitle();
    if (strlen($title_back)) print ("\r\ntitle found: $title_back \r\n");
    /*$forumSame=(strpos($title_back,$forum)===0);
    $this->assertTrue($forumSame,"Not came back to View");*/
    $nn=explode("..",$title_back)[1];
    $lastMsg3=explode(" ",$nn)[0];
    $this->assertEquals($total-1,$lastMsg3,"Not came back to View or removal not counted");
    $addr=$this->webDriver->findElement(WebDriverBy::xpath('//address[last()]'));
    //print($addr->getText());
    $authorSame=(strpos($addr->getText(),$me)===0);
    $this->assertFalse($authorSame,"My good name not deleted");
    $mess=$this->webDriver->findElements(WebDriverBy::xpath('//p[@class="m"]'));
    $mes=end($mess);
    $retr=$mes->getText();
    //print( $mes->getText() );    
    //$msgSame=(strpos($mes->getText(),$msg)===0);
    //$this->assertFalse($authorSame,"My good message not deleted");
    $this->assertNotContains(self::$storedMsg,$retr,"My edited message is not deketed");
    print("Info: delete OK");    
  }
  
}
?>