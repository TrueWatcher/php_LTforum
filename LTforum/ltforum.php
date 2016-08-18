<?php
/**
 * All php code that has not yet found its own file ;)
 */

require_once ("CardfileSqlt.php");
require_once ("AssocArrayWrapper.php");
 
/**
 * My exception for file access errors.
 */
class AccessException extends Exception {}
/**
 * My exception for unsupported/forbidden client operations.
 */
class UsageException extends Exception {} 
 
  class PageRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;// private causes access error
    
    public function load() {
      $inputKeys=array("act","page","rec","len");
      foreach ($inputKeys as $k) {
        if ( array_key_exists($k,$_REQUEST) ) $this->s($k,$_REQUEST[$k]);
        else $this->s($k,"");
      }
      if (array_key_exists('PHP_AUTH_USER',$_SERVER) ) $this->s("user",$_SERVER['PHP_AUTH_USER']);
      else $this->s("user","UFO");
      $forum=( parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) );
      $this->s("forum",$forum);
      
      $this->s("forum","testDb");// DEBUG!!!
    }
  }
 
  class SessionRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
  }
  
  function makeMsg($a,$t,$c="") {
    return ( array("author"=>$a,"message"=>$t,"comment"=>$c) );
  }
    
  // main -----------------------------------------------
  
  print ("\r\nHello, I'm LTforum's main program!\r\n");
  $ins=PageRegistry::getInstance( false,array("there"=>"4321") );
  
  //$ins=inputVars::getInstance(true,array("there"=>"4321"));
  //print($ins->g("there"));
  //$ins->s(("here","a1234");
  //print($ins->g("here"));
  //print($ins->g("up_here"));// causes exception
  //$ins->dump();
  //$inssss=inputVars::getInstance(true,array("down_there"=>"4321000"));
  //$inssss->dump();
  
  $ins->load();

  $ins->dump();
  print("====");
  
  $sets=SessionRegistry::getInstance( true,array("near"=>"4-3-2-1") );
  $sets->dump();
  
  
  

  
  //$dbo=singletDbo::getInstance( $ins->g("forum") );  
  //$firstMsg=helperSqlite::getOneMsg($dbo,1);
  
  $c=new CardfileSqlt( $ins->g("forum"), true);
  //$d=new CardfileSqlt( $ins->g("forum"), true);// must cause exception
  //$c=new CardfileSqlt( $ins->g("forum")."oh_no", false);// must cause exception
  $firstMsg=$c->getOneMsg(1);
  //print_r ($firstMsg);
  
  $msg2=makeMsg("Super","Bla bla bla bla bla bla bla bla!!!");
  //helperSqlite::addMsg($dbo,$msg2);
  $c->addMsg($msg2);
  
  //$msg=helperSqlite::getOneMsg($dbo,2);
  //print_r ($msg);
  
  $msg3=makeMsg("SuperPuper","Blablablablablablablabla!!!Blablablablablablablabla!!!");
  $msg3["date"]="12.34.2022";
  $msg3["time"]="12-34";
  $c->addMsg($msg3);
  //helperSqlite::addMsg($dbo,$msg3);
  
  $msg1=$c->getOneMsg(1);
  $msg1["time"]="99-99";
  $msg1["comment"]="UAHAHAHA!!!";
  $c->addMsg($msg1,true);
  //$c->deletePackMsg(1,2);
  //$all=helperSqlite::getPackMsg($dbo,100,999);
  //print_r ($all);
  $ms=$c->yieldPackMsg(1,999);
  foreach ($ms as $i=>$m) {
    print_r ($m);
  }
  
  $t=$c->getLimits($l,$h,true);
  print("\r\nLow:".$l.", high:".$h.", total:".$t."\r\n");
  
  //try {
  
  //} catch ()
  

  
 
 
 
 
 

?>