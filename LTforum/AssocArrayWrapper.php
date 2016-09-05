<?php
/**
 * @pakage LTforum
 * @version 1.0 experimental deployment
 */
 
  class AssocArrayWrapper {
    protected $arr;
    protected $strict;
   
    public function s($key,$value) {
      if ( !array_key_exists($key,$this->arr) ) {
        if (strict) throw new UsageException ("Assignment by non-existent key ".$key.". Use forceSet if you really mean it" );
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
    
    public function __construct( $stric=true,$ar=array() ) {
      if ( isset($stric) ) $this->strict=$strict;
      if ( isset($ar) ) $this->arr=$arr;
    }
  }

  abstract class SetGet {
    abstract function s($key,$value); 
    abstract function g($key);    
  }
  
  class SingletAssocArrayWrapper extends SetGet {
    protected $arr;
    protected $strict;
    //protected static $me=null;// this should be in child classes
    
    public static function getInstance($stric=1,$ar=array()) {
      $sc=get_called_class();
      if ( empty( $sc::$me ) ) {
        //echo("Attaching instance to its \"$sc\" class\r\n");
        $sc::$me = new $sc($stric,$ar);
        // just =new singletAssocArrayWrapper($strict,$arr); is not good for childs
      }
      return $sc::$me;
    }
   
    public function s($key,$value) {
      if ( $this->strict==2 ) throw new UsageException ("Attempt to set value by key ".$key." while this instance was constucted in READONLY mode" );    
      if ( !array_key_exists($key,$this->arr) ) {
        if ( $this->strict==1 ) throw new UsageException ("Assignment by non-existent key ".$key." while this instance was constucted in STRICT mode" );
      }
      $this->arr[$key]=$value;
    }
    
    /*public function forceSet($key,$value) {
      $this->arr[$key]=$value;
    }*/
    
    public function g($key) {
      if ( !array_key_exists($key,$this->arr) ) {
        if (!$this->strict) return ("");
        else throw new Exception ("Reading by non-existent key ".$key);
      }
      return( $this->arr[$key] );
    }
    
    public function r($key) {
      if ( $this->strict==2 ) throw new UsageException ("Attempt to remove element by key ".$key." while this instance was constucted in READONLY mode" );         
      if ( array_key_exists($key,$this->arr) ) unset ($this->arr[$key]);
    }
    
    private function __construct( $stric=true,$ar=array() ) {
      if ( isset($stric) ) $this->strict=$stric;
      if ( isset($ar) ) $this->arr=$ar;
    }
    
    public function dump() {// DEBUG
      print_r($this->arr);
    }
  }// end SingletAssocArrayWrapper
  
?>