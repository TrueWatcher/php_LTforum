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
    private static $me=null;
    
    public static function getInstance($strict=true,$arr=array()) {
      if ( empty( self::$me ) ) {
        $sc=get_called_class();
        //echo($sc);
        self::$me = new $sc($strict,$arr);
        // just =new singletAssocArrayWrapper($strict,$arr); is not good for childs
      }
      return self::$me;
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
 
  class settings extends singletAssocArrayWrapper {}
  
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
  $dbo=singletDbo::getInstance( $ins->g("forum") );  
  $ins->dump();
  

  
  $firstMsg=helperSqlite::getOneMsg($dbo,1);
  //print_r ($firstMsg);
  
  $msg2=makeMsg("Super","Bla bla bla bla bla bla bla bla!!!");
  helperSqlite::addMsg($dbo,$msg2);
  
  $msg=helperSqlite::getOneMsg($dbo,2);
  //print_r ($msg);
  
  $msg3=makeMsg("SuperPuper","Blablablablablablablabla!!!Blablablablablablablabla!!!");
  helperSqlite::addMsg($dbo,$msg3);
  
  $all=helperSqlite::getPackMsg($dbo,100,999);
  print_r ($all);
  
  //try {
  
  //} catch ()
  

  
 
 
 
 
 

?>