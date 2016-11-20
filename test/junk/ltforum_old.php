<?php
/**
 * All php code that has not yet found its own file ;)
 */
  class assocArrayWrapper {
    protected $arr;
    protected $strict;

    public function s($key,$value) {
      $this->arr[$key]=$value;
    }
    public function g(string $key) {
      if ( !array_key_exists($key,$this->arr) ) {
        if (!strict) return ("");
        else throw new Exception ("Attept to read by non-existent key");
      }
      return( $this->arr[$key] );
    }
    public function r($key) {
      if ( array_key_exists($key,$this->arr) ) unset ($this->arr[$key]);
    }
    public function __construct( $strict=true,$arr=array() ) {
      if ( isset($strict) ) $this->strict=$strict;
      if ( isset($arr) ) $this->arr=$arr;
    }
  }

  class singletAssocArrayWrapper {
    protected $arr;
    protected $strict;
    //protected static $me=null;// this should be in child classes

    public static function getInstance($strict=true,$arr=array()) {
      $sc=get_called_class();
      if ( empty( self::$me ) ) {
        //echo("Attaching instance to its \"$sc\" class\r\n");
        $sc::$me = new $sc($strict,$arr);
        // just =new singletAssocArrayWrapper($strict,$arr); is not good for childs
      }
      return $sc::$me;
    }

    public function s($key,$value) {
      $this->arr[$key]=$value;
    }
    public function g($key) {
      if ( !array_key_exists($key,$this->arr) ) {
        if (!$this->strict) return ("");
        else throw new Exception ("Attept to read by non-existent key");
      }
      return( $this->arr[$key] );
    }
    public function r($key) {
      if ( array_key_exists($key,$this->arr) ) unset ($this->arr[$key]);
    }
    private function __construct( $strict=true,$arr=array() ) {
      if ( isset($strict) ) $this->strict=$strict;
      if ( isset($arr) ) $this->arr=$arr;
    }
    public function dump() {// DEBUG
      print_r($this->arr);
    }
  }

  class inputVars extends singletAssocArrayWrapper {
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

  class settings extends singletAssocArrayWrapper {
    protected static $me=null;
  }

  function makeMsg($a,$t,$c="") {
    return ( array("author"=>$a,"message"=>$t,"comment"=>$c) );
  }

  require_once ("helperSqlite.php");

  // main -----------------------------------------------

  print ("\r\nHello, I'm LTforum's main program!\r\n");
  $ins=inputVars::getInstance( true,array("there"=>"4321") );

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

  $sets=settings::getInstance( true,array("near"=>"4-3-2-1") );
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