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
  protected $homeUri="http://LTforum/test/";//"http://fs..net/new_ltforum/test/";//
  protected $searchUri="http://LTforum/test/?act=search&query=";

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
  
  static private $storedUsername="Test";
  static private $storedTotal=0;
  static private $storedMsg="";
  static private $storedForum="test";
  static private $storedQuery="";
  
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
  
  public function parseSearchIds ($buf) {
    $idList=[];
    //$buf=file_get_contents($pathName.".html");
    $ret=preg_match_all('~">\s*#(\d+)\s*</a>\s*</b>~',$buf,$list);
    // htmlunit adds \n after and before tags
    //print_r($list);
    if( $ret ) $idList=$list[1];
    return($idList);  
  }
  
  public function addOneMsg($j=1,$myMsg="") {
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
    $msg="Test message ".$j." ".$myMsg;
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
  
  public function test_searchSequence1() {
    $mySearchCyr="ЙцуКЕнг";
    $myTextCyr="Фыва ".$mySearchCyr."ячсмить";
    $this->webDriver->get($this->homeUri);    
    $this->addOneMsg(0,$myTextCyr);
    
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");    
    //$this->webDriver->get($this->searchUri.urlencode($mySearchCyr));
    $searchLink=$this->webDriver->findElement( webDriverBy::partialLinkText("Search") );
    $searchLink->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    if ( strpos($title,"search")===false && strpos($title,"/")>0 ) {
      print (" Trying to go to new window ");
      $handles = $this->webDriver->getWindowHandles();
      //print_r($handles);
      $this->webDriver->switchTo()->window($handles[1]);
      $title=$this->webDriver->getTitle();      
    }
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");    
    $this->assertContains("search",$title,"No \"search\" in the title -- wrong page");
    $searchForm=$this->webDriver->findElement( webDriverBy::id("search") );
    $queryInput=$searchForm->findElement( webDriverBy::name("query") );
    $queryInput->sendKeys($mySearchCyr);
    $searchForm->submit();
    print(" Search request submitted ");
    
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertContains($mySearchCyr,$title,"No search string in the title, maybe wrong page");
    $this->assertContains("search",$title,"No \"search\" in the title -- wrong page");
    $src=$this->webDriver->getPageSource();
    //print ("!!!$src!!!");
    $ids=$this->parseSearchIds ($src);
    $this->assertNotEmpty($ids,"Empty search output for ".$mySearchCyr);
    print(" Found ".count($ids)." results ");
    $res=[];
    $highlighted=(preg_match("~>\s*".$mySearchCyr."\s+</span>~",$src,$res) >0 );
    $this->assertTrue($highlighted,"Highligthing goes wrong");
    print("\r\nHightlight brackets OK\r\n");
    
    $viewLink=$this->webDriver->findElement( webDriverBy::partialLinkText("#".$ids[0]) );
    $viewLink->click();
    $titleView=$this->webDriver->getTitle();
    if (strlen($titleView)) print ("\r\ntitle found: $titleView \r\n");
    $srcView=$this->webDriver->getPageSource();
    $this->assertContains($mySearchCyr,$srcView,"No needle found in view page");
        
    print("\r\nAdd-Search-View sequence OK\r\n");
  }  
    
  public function test_searchCaseInsensitive() {
    $mySearchCyrInv="йЦУкеНГ";
    $this->webDriver->get($this->searchUri.urlencode($mySearchCyrInv));    
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertContains($mySearchCyrInv,$title,"No search string in the title, maybe wrong page");
    $this->assertContains("search",$title,"No \"search\" in the title -- wrong page");
    $src=$this->webDriver->getPageSource();
    $ids=$this->parseSearchIds ($src);
    $this->assertNotEmpty($ids,"Empty search output for ".$mySearchCyrInv);
    print("\r\nFound ".count($ids)." results, search is proved case-insensitive");
    
    $mySearchCyr="ЙцуКЕнг";
    $sub1="ЙцуКЕн";
    $sub2="уК";
    $sub3="Енг";
    $q=$sub1."&".$sub2."&".$sub3;
    $this->webDriver->get($this->searchUri.urlencode($q));    
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $this->assertContains($q,$title,"No search string in the title, maybe wrong page");
    $this->assertContains("search",$title,"No \"search\" in the title -- wrong page");
    $src=$this->webDriver->getPageSource();
    $ids=$this->parseSearchIds ($src);
    $this->assertNotEmpty($ids,"Empty search output for ".$q);
    print(" Found ".count($ids)." results ");
    $res=[];
    $highlighted=(preg_match("~>\s*".$mySearchCyr."\s+</span>~",$src,$res) >0 );
    $this->assertTrue($highlighted,"Highligthing goes wrong");
    print("\r\nHightlight brackets for overlapping needles OK\r\n");   
    
  }
}
?>