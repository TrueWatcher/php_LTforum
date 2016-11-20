<?php
// Web-Test suite for LTforum admin functions (import-export-delRange-editAny)
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

class Test_LTforumMsgManager extends PHPUnit_Framework_TestCase {

  protected $browser="htmlunit";
  protected $emulate="FIREFOX_45";// needed for JQuery and/or Bootstrap
  // http://htmlunit.sourceforge.net/apidocs/index.html class
  // com.gargoylesoftware.htmlunit   Class BrowserVersion
  protected $JSenabled=true;//true;//false;;
  protected $webDriver;
  protected $homeUri="http://LTforum/rulez.php?forum=test&pin=1";//"http://fs..net/new_ltforum/rulez.php?forum=test&pin=1";//
  //protected $filesystemPath="/home/alexander/www/LTforum/test/";
  protected $testDirUri="http://LTforum/test/";//"http://fs..net/new_ltforum/test/";//

  public function setUp() {
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

  private $storedTitle;

  public function _test_mainPage() {
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
    self::$storedTitle=$title;
  }

  private function parseHtmlIds($pathName) {
    $idList=[];
    $buf=file_get_contents($pathName.".html");
    $ret=preg_match_all('~"(\d+)">#</b>~',$buf,$list);
    //print_r($list);
    if( $ret ) $idList=$list[1];
    return($idList);
  }

  private function export($begin,$end,$file,$newBegin,$kb="") {
    $this->webDriver->get($this->homeUri);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $form=$this->webDriver->findElement(webDriverBy::id("export"));
    $form->findElement(webDriverBy::name("begin"))->sendKeys($begin);
    $form->findElement(webDriverBy::name("end"))->sendKeys($end);
    $form->findElement(webDriverBy::name("obj"))->sendKeys($file);
    if($newBegin) $form->findElement(webDriverBy::name("newBegin"))->sendKeys($newBegin);
    if($kb) $form->findElement(webDriverBy::name("kb"))->sendKeys($kb);
    $form->submit();
  }

  private function checkExportResponce($begin,$end) {
    $expected1="Exported messages ".$begin."..".$end." to ";
    $expected2=".html , total ".($end-$begin+1);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $src=$this->webDriver->getPageSource();
    $this->assertContains($expected1,$src,"Messages numbers mismatch");
    $this->assertContains($expected2,$src,"Given total mismatches");
  }

  private function getExportResponce(&$begin,&$end) {
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $src=$this->webDriver->getPageSource();
    $ret=preg_match("~\s+(\d+)\.\.(\d+)\s+~",$src,$res);
    //print_r($res);
    $this->assertTrue($ret>0,"Something is wrong with parsing export responce");
    $begin=$res[1];
    $end=$res[2];
  }

  private function checkExport($begin,$end,$file2,$newBegin) {
    //$path=$this->filesystemPath;
    $path=$this->testDirUri;
    $idList=self::parseHtmlIds($path.$file2);
    print( " Begin:".$idList[0]." , end:".end($idList).", total:".count($idList)." " );
    $this->assertEquals(count($idList),(end($idList)-$idList[0]+1),"Some ids are missing in the exported file");
    $this->assertEquals($newBegin,$idList[0],"Begin mismatches in ".$file2);
    $this->assertEquals($newBegin+($end-$begin),end($idList),"End mismatches in ".$file2);
    print(" File check OK ");
    $size=strlen( file_get_contents($path.$file2.".html") );
    return ($size);
  }

  public function test_simpleExport() {
    $file="e_1_11";
    $begin=1;
    $end=11;
    $newBegin="*";
    self::export($begin,$end,$file,$newBegin);
    print(" Export demanded to ".$file);
    self::checkExportResponce($begin,$end);
    print(" Responce OK ");
    self::checkExport($begin,$end,$file,$begin);


    $path=$this->testDirUri;
    $buf=file_get_contents($path.$file.".html");
    preg_match("~text/css\"\s+href=\"(.+?)\"~",$buf,$matches);
    //print_r($matches);
    $cssLink=$matches[1];
    $this->assertContains(".css",$cssLink,"Missing the CSS link in exported file");
    print("\r\nCSS link found : ".$cssLink);
    $css=file_get_contents($path.$cssLink);
    $this->assertNotEmpty($css,"CSS link in exported file is incorrect");
    print ("\r\nCSS link OK\r\n");
  }

  public function test_ImportExport() {
    $file1="e_1_11";
    $begin=12;
    $end=22;
    $this->webDriver->get($this->homeUri);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $form=$this->webDriver->findElement(webDriverBy::id("import"));
    $form->findElement(webDriverBy::name("obj"))->sendKeys($file1);
    $form->submit();
    print(" Import demanded ");
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $ok=$this->webDriver->findElement(webDriverBy::partialLinkText("Ok"));
    $ok->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $file2="ee_1_11";
    $newBegin=1;
    self::export($begin,$end,$file2,$newBegin);
    print(" Export demanded to ".$file2);
    self::checkExportResponce($begin,$end);
    print(" Responce OK ");
    self::checkExport(1,11,$file2,$newBegin);
    //$path=$this->filesystemPath;
    $path=$this->testDirUri;
    $buf1=file_get_contents($path.$file1.".html");
    $buf2=file_get_contents($path.$file2.".html");
    $this->assertEquals($buf1,$buf2,"Files are different");
    //$this->assertFileEquals($path.$file1.".html",$path.$file2.".html","Files are different");
    print("\r\nExported files are equal, congratulations! \r\n");

    $file3="e_3kb";
    $newBegin="*";
    $begin=2;
    $kb=3;
    self::export($begin,"",$file3,$newBegin,$kb);
    self::getExportResponce($recBegin,$recEnd);
    print(" Responce : ".$recBegin."..".$recEnd);
    $this->assertEquals($begin,$recBegin,"Begin numbers mismatch");
    $this->assertLessThan(22,$recEnd,"End number too big, maybe kb limit not applied");
    $s=self::checkExport($begin,$recEnd,$file3,$begin);
    print(" Export limited by ".$kb."KB : exported ".$s."KB ");
  }

  public function test_DeleteEditExport() {
    $this->webDriver->get($this->homeUri);
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $form=$this->webDriver->findElement(webDriverBy::id("delRange"));
    $form->findElement(webDriverBy::name("begin"))->sendKeys(12);
    $form->findElement(webDriverBy::name("end"))->sendKeys(22);
    $form->submit();
    print(" Deleting 12..22 demanded ");
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $ok=$this->webDriver->findElement(webDriverBy::partialLinkText("Ok"));
    $ok->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $form=$this->webDriver->findElement(webDriverBy::id("delRange"));
    $form->findElement(webDriverBy::name("begin"))->sendKeys(1);
    $form->findElement(webDriverBy::name("end"))->sendKeys(2);
    $form->submit();
    print(" Deleting 1..2 demanded ");
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $ok=$this->webDriver->findElement(webDriverBy::partialLinkText("Ok"));
    $ok->click();

    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $form=$this->webDriver->findElement(webDriverBy::id("editAny"));
    $toEdit=5;
    $form->findElement(webDriverBy::name("current"))->sendKeys($toEdit);
    $form->submit();
    print(" Edit ".$toEdit." demanded ");
    $title_edit=$this->webDriver->getTitle();
    if (strlen($title_edit)) print ("\r\ntitle found: $title_edit \r\n");
    $this->assertContains("edit message ".$toEdit,$title_edit,"Not came to editAny page");
    $t=time();
    $me="Editor ".$t;
    $add=" added _".$t;
    $myComm="My comment _".$t;
    $author=$this->webDriver->findElement(webDriverBy::name("author"));
    $author->clear();
    $author->sendKeys($me);
    $txt=$this->webDriver->findElement(webDriverBy::name("txt"));
    $txt->sendKeys($add);
    $comm=$this->webDriver->findElement(webDriverBy::name("comm"));
    $comm->sendKeys($myComm);
    $txt->submit();
    print(" Edit ".$toEdit." submited ");

    $file="e_3_11";
    $begin=3;
    $end=11;
    $newBegin="*";
    self::export(1,22,$file,$newBegin);
    print(" Export demanded to ".$file);
    self::getExportResponce($recBegin,$recEnd);
    print(" Responce : ".$recBegin."..".$recEnd);
    $this->assertEquals($begin,$recBegin,"Begin numbers mismatch, maybe deletion error");
    $this->assertEquals($end,$recEnd,"End number too big, maybe deletion error");
    print ("\r\nDeletions OK\r\n");
    self::checkExport($begin,$end,$file,$begin);
    print (" Checking file ".$file." OK ");

    //$path=$this->filesystemPath;
    $path=$this->testDirUri;
    $src=file_get_contents($path.$file.".html");
    $this->assertContains(">".$me,$src,"My dear authorName is missing");
    $this->assertContains($add,$src,"My remark is missing");
    //$this->assertContains($myComm,$src,"My comment is missing");
    print ("\r\nEdit OK\r\n");
  }

  public function test_exportPartial() {
    $file="e_4_9";
    $begin=4;
    $end=9;
    $newBegin="*";
    self::export($begin,$end,$file,$newBegin);
    print(" Export demanded to ".$file);
    self::checkExportResponce($begin,$end);
    print(" Responce OK ");
    self::checkExport($begin,$end,$file,$begin);
    print (" Checking file".$file."OK ");

    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $ok=$this->webDriver->findElement(webDriverBy::partialLinkText("Ok"));
    $ok->click();
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $form=$this->webDriver->findElement(webDriverBy::id("import"));
    $form->findElement(webDriverBy::name("obj"))->sendKeys($file);
    $form->submit();
    print(" Import demanded ");
    $title=$this->webDriver->getTitle();
    if (strlen($title)) print ("\r\ntitle found: $title \r\n");
    $file2="ee_4_9";
    $begin=12;
    $end=22;
    $newBegin="4";
    self::export($begin,$end,$file2,$newBegin);
    print(" Export demanded to ".$file2);
    self::getExportResponce($recBegin,$recEnd);
    print(" Responce : ".$recBegin."..".$recEnd);
    self::checkExport($begin,$recEnd,$file2,$newBegin);
    print (" Checking file ".$file2."OK ");
    //$path=$this->filesystemPath;
    $path=$this->testDirUri;
    $buf1=file_get_contents($path.$file.".html");
    $buf2=file_get_contents($path.$file2.".html");
    $this->assertEquals($buf1,$buf2,"Files are different");
    //$this->assertFileEquals($path.$file.".html",$path.$file2.".html","Files are different");
    print("\r\nExported files are equal, congratulations!\r\n");

  }

}

?>