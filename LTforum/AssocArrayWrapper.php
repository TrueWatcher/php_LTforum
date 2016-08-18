<?php
/**
 * @pakage LTforum
 * @version 0.1.3 new folders structure
 */
 
  class AssocArrayWrapper {
    protected $arr;
    protected $strict;
   
    public function s($key,$value) {
      if ( !array_key_exists($key,$this->arr) ) {
        if (strict) throw new UsageException ("Assignment by non-existent key ".$key." Use forceSet if you really mean it" );
      }
      $this->arr[$key]=$value;
    }
    
    public function forceSet($key,$value) {
      $this->arr[$key]=$value;
    }
    
    public function g(string $key) {
      if ( !array_key_exists($key,$this->arr) ) {
        if (!strict) return ("");
        else throw new UsageException ("Reading by non-existent key ".$key);
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
 
  class SingletAssocArrayWrapper {
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
  }// end SingletAssocArrayWrapper
  
?>