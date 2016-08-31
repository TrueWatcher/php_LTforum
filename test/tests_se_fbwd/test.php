<?php
// Web-Test suite for LTforum main functions (view-add-edit-delete)
// All PHPUnit tests stop after first failure!
// Uses PHPUnit + Selenium + FacebookWebDriver + HtmlUnit
// And optionally XAMPP
// (c) TrueWatcher August 2016

//require_once("/home/alexander/vendor/autoload.php" );// needed, but present in ./bootstrap.php

// important! (https://github.com/facebook/php-webdriver/blob/community/example.php)
//namespace Facebook\WebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCapabilityType;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverSelect;
//use PHPUnit\Framework\TestCase;

class Test_LTforumMain extends PHPUnit_Framework_TestCase {

  protected $browser="htmlunit";
  protected $emulate="FIREFOX_45";// needed for JQuery and/or Bootstrap
  // http://htmlunit.sourceforge.net/apidocs/index.html class 
  // com.gargoylesoftware.htmlunit   Class BrowserVersion
  protected $JSenabled=true;//true;//false;;
  protected $webDriver;
  protected $homeUri="http://fs..net/new_ltforum/test/";//"http://LTforum/test/";

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
  static private $storedForum="";
  
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
  
  public function parseViewTitle($t,&$begin,&$end,&$pageCurrent,&$pageEnd) {
    $begin=$end=$pageCurrent=$pageEnd="";
    $forumNumbers=explode(": ",$t);
    $forum=$forumNumbers[0];
    $numbers=$forumNumbers[1];
    if (strpos($numbers,"(")===false) {
      return($forum);
    }
    $beginMore=explode("..",$numbers);
    $begin=$beginMore[0];
    $more1=$beginMore[1];
    $endMore=explode(" (",$more1);
    $end=$endMore[0];
    $more2=$endMore[1];
    $currentMore=explode("/",$more2);
    $pageCurrent=$currentMore[0];
    $pageEnd=trim($currentMore[1],"() ");
    return($forum);
  }
  
  public function test_Add() {
    $this->webDriver->get($this->homeUri);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertNotEmpty($title,"Failed to connect to the site");
    $forum=self::parseViewTitle($title,$b,$lastMsg,$pc,$pe);
    self::$storedForum=$forum;
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
    $forum_back=self::parseViewTitle($title_back,$b,$lastMsg2,$pc,$pe);
    $forumSame=( strcmp($forum_back,self::$storedForum )===0);
    $this->assertTrue($forumSame,"Not came back to View");
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
    $addLink=$this->webDriver->findElement(WebDriverBy::xpath('//b[@title="Edit/Delete"]'));
    $this->assertNotEmpty($addLink,"No EDIT link after adding message: check URIs for user=User");
    print("Info: add message OK"); 
  }
  
  public function test_Edit() {
    $me=self::$storedUsername;
    $qs="?user=".$me;
    $this->webDriver->get( ($this->homeUri).$qs );
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertNotEmpty($title,"Failed to connect to the site");
    $forum=self::parseViewTitle($title,$b,$e,$pc,$pe);
    $total=self::$storedTotal;
    $this->assertEquals(self::$storedForum,$forum,"Wrong page: ".$title);
    $this->assertEquals(self::$storedTotal,$e,"Invalid or missing total number");
    $editLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("§"));
    $this->assertNotEmpty($editLink,"An EDIT link not found");
    $editLink->click();
    sleep(1);
    
    $title_edit=$this->webDriver->getTitle();
    if (strlen($title_edit)) print ("\r\ntitle found: $title_edit \r\n");
    $this->assertContains(self::$storedForum,$title_edit,"Not came to EDIT page");
    $this->assertContains("edit message ".$total,$title_edit,"Missed EDIT LAST page or wrong message number");
    
    $t=(string)time();
    //$msg="My_[u]second[/u],".$t."<br /> <i>second</i> <br>te<st <message> yes <s>yes";
    $msg="my additional te<st <message> yes ".$t;
    $inputText=$this->webDriver->findElement(WebDriverBy::name("txt"));
    $this->assertNotEmpty($inputText,"A MESSAGE field not found");
    $inputText->sendKeys($msg);
    //$msgC="My_second,".$t."<br /> <i>second</i> <br>comment";
    $msgC="My <p>good</p> co<mm>ent ".$t;
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
    $forum=self::parseViewTitle($title_back,$b,$lastMsg3,$pc,$pe);
    $this->assertEquals(self::$storedForum,$forum,"Not came back to View");    
    $this->assertEquals(self::$storedTotal,$lastMsg3,"Changed total number");  
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
    $this->assertContains($msg,$retr,"Message is altered");
    $coms=$this->webDriver->findElements(WebDriverBy::xpath('//p[@class="n"]'));
    $com=end($coms);
    $retC=$com->getText();
    $this->assertContains($t,$retC,"My new comment not found");
    $comSame=(strpos($msgC,$retC)===0);
    $this->assertTrue($comSame,"Comment is altered");
    $addLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("§"));// it's assertion
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
    $forum=self::parseViewTitle($title,$b,$e,$pc,$pe);
    $total=self::$storedTotal;
    $this->assertEquals(self::$storedForum,$forum,"Wrong page: ".$title);
    $this->assertEquals(self::$storedTotal,$e,"Invalid or missing total number");
    //$this->assertContains("..".$total." ",$title,"Invalid or missing total number");
    $editLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("§"));
    $this->assertNotEmpty($editLink,"An EDIT link not found");
    $editLink->click();
    sleep(1);
    
    $title_edit=$this->webDriver->getTitle();
    if (strlen($title_edit)) print ("\r\ntitle found: $title_edit \r\n");
    $this->assertContains(self::$storedForum,$title_edit,"Not came to EDIT LAST page");
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
    $forum=self::parseViewTitle($title_back,$b,$e,$pc,$pe);
    $this->assertEquals(self::$storedForum,$forum,"Not came back to View");    
    $this->assertEquals($total-1,$e,"Removal not counted");    
    $addr=$this->webDriver->findElement(WebDriverBy::xpath('//address[last()]'));
    //print($addr->getText());
    $authorSame=(strpos($addr->getText(),$me)===0);
    $this->assertFalse($authorSame,"My good name not deleted");
    $mess=$this->webDriver->findElements(WebDriverBy::xpath('//p[@class="m"]'));
    $mes=end($mess);
    $retr=$mes->getText();
    //print( $mes->getText() );    
    $this->assertNotContains(self::$storedMsg,$retr,"My edited message is not deketed");
    print("Info: delete OK");    
  }
  
  public function test_ViewAddAlertView() {
    $this->webDriver->get($this->homeUri);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertNotEmpty($title,"Failed to connect to the site");
    $forum=self::parseViewTitle($title,$b,$e,$pc,$pe);
    self::$storedForum=$forum;
    self::$storedTotal=$e;
    $addLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("Write"));
    //$this->assertNotEmpty($addLink,"A WRITE link not found");
    $addLink->click();
    
    // submit an empty form
    $titleNew1=$this->webDriver->getTitle();
    if (strlen($titleNew1)) print ("\r\ntitle found: $titleNew1 \r\n");
    $forumAdd=self::parseViewTitle($titleNew1,$b,$e,$pc,$pe);
    $this->assertEquals(self::$storedForum,$forumAdd,"Not came to WRITE NEW page");
    $this->assertContains("new message",$titleNew1,"Missed WRITE NEW page");    
    // submit an empty form
    $subm=$this->webDriver->findElement(WebDriverBy::xpath('//input[@type="submit"]'));
    $this->assertNotEmpty($subm,"A SUBMIT field not found");
    $subm->submit();
    print("Info: submit empty form OK");

    // check alert page
    $titleAlert=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $titleAlert \r\n");
    $this->assertContains(self::$storedForum,$titleAlert,"Not came to ALERT page");
    $this->assertContains(" alert",$titleAlert,"Not came to ALERT page");
    $tryAgainLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("Ok"));
    $tryAgainLink->click();
    print("Info: ALERT after empty form OK");
    
    // check View page
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $forum=self::parseViewTitle($title,$b,$e,$pc,$pe);
    $this->assertEquals(self::$storedForum,$forum,"Wrong page: ".$title);
    $this->assertEquals(self::$storedTotal,$e,"Invalid or missing total number");
    $addLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("Write"));
    $addLink->click();
    print("Info: VIEW after ALERT OK");

    // send a message with cleared snap
    $titleNew2=$this->webDriver->getTitle();    
    if (strlen($title)) print ("\r\ntitle found: $titleNew2 \r\n");
    $this->assertContains(self::$storedForum,$titleNew2,"Not came to WRITE NEW page after ALERT");
    $this->assertContains("new message",$titleNew2,"Missed WRITE NEW page");
    $me="Test Robot";
    self::$storedUsername=$me;    
    $msg="Test message 1";
    $checkSnap=$this->webDriver->findElement(WebDriverBy::name("snap"));
    $checkSnap->click();
    $inputAuthor=$this->webDriver->findElement(WebDriverBy::name("user"));
    //$this->assertNotEmpty($addLink,"A USER field not found");
    $inputAuthor->sendKeys($me);
    $inputText=$this->webDriver->findElement(WebDriverBy::name("txt"));
    //$this->assertNotEmpty($inputText,"A MESSAGE field not found");
    $inputText->sendKeys($msg);
    $subm=$this->webDriver->findElement(WebDriverBy::xpath('//input[@type="submit"]'));
    //print('Button '.$subm->getAttribute("value"));
    //$this->assertNotEmpty($subm,"A SUBMIT field not found");
    $subm->submit();
    print("Info: submit a message with cleared SNAP OK");
    
    // again Alert, go read
    $titleAlert=$this->webDriver->getTitle();
    if (strlen($titleAlert)) print ("\r\ntitle found: $titleAlert \r\n");
    $this->assertContains(self::$storedForum,$titleAlert,"Not came to ALERT page");
    $this->assertContains(" alert",$titleAlert,"Not came to ALERT page");
    $readLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("Ok"));
    $readLink->click();
    print("Info: ALERT after cleared SNAP OK");
    
    // happy return to View
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $forum=self::parseViewTitle($title,$b,$e,$pc,$pe);
    $this->assertEquals(self::$storedForum,$forum,"Not came back to View");    
    $this->assertEquals(self::$storedTotal+1,$e,"Added message not counted");
    $addLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("§"));// it's assertion   
    print("Info: View-Add-Alert-View sequence OK");
  }
  
  public function test_ViewEditAlertView() {
    $me=self::$storedUsername;
    $qs="?user=".$me;
    $this->webDriver->get( ($this->homeUri).$qs );  
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertNotEmpty($title,"Failed to connect to the site");
    $forum=self::parseViewTitle($title,$b,$e,$pc,$pe);
    self::$storedForum=$forum;
    self::$storedTotal=$e;
    $addLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("§"));
    //$this->assertNotEmpty($addLink,"A WRITE link not found");
    $addLink->click();
    
    // submit a form without message
    $titleNew1=$this->webDriver->getTitle();
    if (strlen($titleNew1)) print ("\r\ntitle found: $titleNew1 \r\n");
    $forumAdd=self::parseViewTitle($titleNew1,$b,$e,$pc,$pe);
    $this->assertEquals(self::$storedForum,$forumAdd,"Not came to EDIT page");
    $this->assertContains("edit",$titleNew1,"Missed EDIT page");
    $inputText=$this->webDriver->findElement(WebDriverBy::name("txt"));
    $inputText->clear();
    $subm=$this->webDriver->findElement(WebDriverBy::xpath('//input[@type="submit"]'));
    $this->assertNotEmpty($subm,"A SUBMIT field not found");
    $subm->submit();
    print("Info: submit EDIT form with empty TXT OK");    

    // check alert page
    $titleAlert=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $titleAlert \r\n");
    $this->assertContains(self::$storedForum,$titleAlert,"Not came to ALERT page");
    $this->assertContains(" alert",$titleAlert,"Not came to ALERT page");
    $tryAgainLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("Back"));
    $tryAgainLink->click();
    print("Info: ALERT after empty form OK");
    
    // submit a form with a message and cleared snap
    $titleNew1=$this->webDriver->getTitle();
    if (strlen($titleNew1)) print ("\r\ntitle found: $titleNew1 \r\n");
    $forumAdd=self::parseViewTitle($titleNew1,$b,$e,$pc,$pe);
    $this->assertEquals(self::$storedForum,$forumAdd,"Not came to EDIT page");
    $this->assertContains("edit",$titleNew1,"Missed EDIT page");
    $inputText=$this->webDriver->findElement(WebDriverBy::name("txt"));
    $msg2=" edited";    
    $inputText->sendKeys($msg2);
    $checkSnap=$this->webDriver->findElement(WebDriverBy::name("snap"));
    $checkSnap->click();
    $subm=$this->webDriver->findElement(WebDriverBy::xpath('//input[@type="submit"]'));
    $this->assertNotEmpty($subm,"A SUBMIT field not found");
    $subm->submit();    
    print("Info: return to EDIT and submit a form with message and cleared SNAP OK");
    
    // again Alert, get back to form
    $titleAlert=$this->webDriver->getTitle();
    if (strlen($titleAlert)) print ("\r\ntitle found: $titleAlert \r\n");
    $this->assertContains(self::$storedForum,$titleAlert,"Not came to ALERT page");
    $this->assertContains(" alert",$titleAlert,"Not came to ALERT page");
    $readLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("Back"));
    $readLink->click();
    print("Info: ALERT after cleared SNAP OK");
    
    // submit a form with a comment and cleared snap
    $titleNew1=$this->webDriver->getTitle();
    if (strlen($titleNew1)) print ("\r\ntitle found: $titleNew1 \r\n");
    $forumAdd=self::parseViewTitle($titleNew1,$b,$e,$pc,$pe);
    $this->assertEquals(self::$storedForum,$forumAdd,"Not came to EDIT page");
    $this->assertContains("edit",$titleNew1,"Missed EDIT page");
    $inputComm=$this->webDriver->findElement(WebDriverBy::name("comm"));
    $msg3="My commentary";    
    $inputComm->sendKeys($msg3);
    $checkSnap=$this->webDriver->findElement(WebDriverBy::name("snap"));
    $checkSnap->click();
    $subm=$this->webDriver->findElement(WebDriverBy::xpath('//input[@type="submit"]'));
    $subm->submit();    
    print("Info: return to EDIT and submit a form with cleared SNAP OK");
    
    // again Alert, go to View //get back to form
    $titleAlert=$this->webDriver->getTitle();
    if (strlen($titleAlert)) print ("\r\ntitle found: $titleAlert \r\n");
    $this->assertContains(self::$storedForum,$titleAlert,"Not came to ALERT page");
    $this->assertContains(" alert",$titleAlert,"Not came to ALERT page");
    $readLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("Ok"));
    $readLink->click();
    print("Info: again ALERT after cleared SNAP OK");
    
    // submit unchanged form with default snap=on
    /*$titleNew1=$this->webDriver->getTitle();
    if (strlen($titleNew1)) print ("\r\ntitle found: $titleNew1 \r\n");
    $forumAdd=self::parseViewTitle($titleNew1,$b,$e,$pc,$pe);
    $this->assertEquals(self::$storedForum,$forumAdd,"Not came to EDIT page");
    $this->assertContains("edit",$titleNew1,"Missed EDIT page");
    $subm=$this->webDriver->findElement(WebDriverBy::xpath('//input[@type="submit"]'));
    $subm->submit(); 
    print("Info: return to EDIT and submit a default form OK");*/
    
    // check View page
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $forum=self::parseViewTitle($title,$b,$e,$pc,$pe);
    $this->assertEquals(self::$storedForum,$forum,"Wrong page: ".$title);
    $this->assertEquals(self::$storedTotal,$e,"Invalid or missing total number");
    $addLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("§"));
    print("Info: View-Edit-Alert-View sequence OK");
  }
  
  public function addOneMsg($j=1) {
    //$this->webDriver->get($this->homeUri);// bad because sets length to default
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $addLink=$this->webDriver->findElement(WebDriverBy::partialLinkText("Write"));
    $addLink->click();
    $titleNew2=$this->webDriver->getTitle();    
    if (strlen($title)) print ("\r\ntitle found: $titleNew2 \r\n");
    $this->assertContains(self::$storedForum,$titleNew2,"Not came to WRITE NEW page after ALERT");
    $this->assertContains("new message",$titleNew2,"Missed WRITE NEW page");
    $me=self::$storedUsername;    
    $msg="Test message ".$j;
    $inputAuthor=$this->webDriver->findElement(WebDriverBy::name("user"));
    //$this->assertNotEmpty($addLink,"A USER field not found");
    $inputAuthor->clear();
    $inputAuthor->sendKeys($me);
    $inputText=$this->webDriver->findElement(WebDriverBy::name("txt"));
    $this->assertNotEmpty($inputText,"A MESSAGE field not found");
    $inputText->sendKeys($msg);
    $subm=$this->webDriver->findElement(WebDriverBy::xpath('//input[@type="submit"]'));
    //$this->assertNotEmpty($subm,"A SUBMIT field not found");
    $subm->submit();
  }
  
  public function test_pageCountAcross10() {
    $this->webDriver->get($this->homeUri);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");  
    $forum=self::parseViewTitle($title,$b,$e,$pc,$pe);
    $this->assertLessThan(8,(int)$e-$b,"Too many messages in test database. Delete it manually");
    self::$storedForum=$forum;    
    self::$storedUsername="TestPagination";
    $selectLength=$this->webDriver->findElement(WebDriverBy::id("perPage"))->findElement(WebDriverBy::name("length"));
    $selector=new WebDriverSelect($selectLength);
    $selector->selectByVisibleText("10");
    $selectLength->submit();
    print("Info: length have been set to 10");
    for ($n=$e+1;$n<=$e+8-$b;$n++) {
      self::addOneMsg($n);
    }
    print("Info: forum is filled with 9 messages");
    // testing 9 messages
    $title9=$this->webDriver->getTitle();
    if (strlen($title9)) print ("\r\ntitle found: $title9 \r\n");  
    $forum=self::parseViewTitle($title9,$b,$e,$pc,$pe);
    $this->assertTrue( ((int)$e-$b)==8 && $pc==1 && $pe==1,"Something is wrong with 9 messages counts" );
    $src9=$this->webDriver->getPageSource();
    $this->assertNotContains(">Previous page<",$src9,"Previous page link found on the 9 messages page");
    $this->assertNotContains(">Next page<",$src9,"Next page link found on the 9 messages page");
    print("The 9 messages page OK");
    //  testing 10 messages
    self::addOneMsg($e-$b+2);
    $title10=$this->webDriver->getTitle();
    print ("\r\ntitle found: $title10 \r\n");  
    $forum=self::parseViewTitle($title10,$b,$e,$pc,$pe);
    $this->assertTrue( ((int)$e-$b)==9 && $pc==1 && $pe==1,"Something is wrong with 10 messages counts" );
    $src10=$this->webDriver->getPageSource();
    $this->assertNotContains("Previous page",$src10,"Previous page link found on the 10 messages page");
    $this->assertNotContains("Next page",$src10,"Next page link found on the 10 messages page");
    print("The 10 messages page OK");
    // testing 11 messages
    self::addOneMsg($e-$b+2);
    $title11=$this->webDriver->getTitle();
    print ("\r\ntitle found: $title11 \r\n");  
    $forum=self::parseViewTitle($title11,$b,$e,$pc,$pe);
    $this->assertEquals (2,$pe,"No second page on 11 messages: check URIs for length=10");
    $this->assertTrue (  $b==2 && $e==11 && $pc==2,"Something is wrong with 11 messages counts" );
    $src11=$this->webDriver->getPageSource();
    $this->assertContains("Previous page",$src11,"Previous page link _not_ found on the 11 messages default page");
    $this->assertNotContains("Next page",$src11,"Next page link found on the 11 messages default page");
    print("The 11 messages default page OK");
    $firstLink=$this->webDriver->findElement(webDriverBy::partialLinkText("1"));
    $firstLink->click();
    $title11f=$this->webDriver->getTitle();
    print ("\r\ntitle found: $title11f \r\n");  
    $forum=self::parseViewTitle($title11f,$b,$e,$pc,$pe);
    $this->assertTrue (  $b==1 && $e==10 && $pc==1 && $pe==2,"Something is wrong with 11 messages first page counts" );
    $src11=$this->webDriver->getPageSource();
    $this->assertNotContains("Previous page",$src11,"Previous page link found on the 11 messages first page");
    $this->assertContains("Next page",$src11,"Next page link _not_ found on the 11 messages first page");
    print("The 11 messages first page OK");    
  }
}
?>