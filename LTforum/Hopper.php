<?php

class Hopper {
  protected $currentState;
  protected $nextState;
  protected $stopNow=0;
  protected $maxHops=10;
  public $trace=[];
  
  protected function next($nextHop) {
    if( !method_exists($this,$nextHop) ) throw new UsageException("Cannot find the method ".$nextHop);
    $this->nextState=$nextHop;
  }
  
  protected function setNextAndBreak($nextHop) {
    $this->setBreak();
    $this->next($nextHop);
  }
  
  protected function setBreak() {
    $this->stopNow=1;  
  }
  
  protected function call($hop,$context) {
    $ret=$this->$hop($context);
    return($ret);
  }

  public function go($context,$from=null) {
    $this->stopNow=0;
    $this->trace="";
    $i=0;
    if ($from) $step=$from;
    else $step=$this->nextState;
    do {
      $this->nextState="";
      $this->currentState=$step;   
      $ret=$this->call($step,$context);
      $this->trace.=" > ".$step;
      if ($this->stopNow || !$this->nextState) break;
      $step=$this->nextState;
    } while ($i++ < $this->maxHops);
    if ($i >= $this->maxHops) {
      $mes="Hops limit of ".$this->maxHops." exceeded.";
      $mes.=" Trace: ".$this->trace."<abort>";
      $mes.=" Next state: ".$this->nextState;
      throw new UsageException($mes);
    }
    return($ret);
  }
}
/*
class TestHopper1 extends Hopper {
  function __construct() {
    //$this->nextState="runOnce";
    //$this->nextState="runLikeHell";
    $this->nextState="run3";
  }
  
  protected function runOnce($context) {
    echo(" Hi, I'm runOnce");
    return (" Bye, I'm runOnce");
  }
  
  protected function runLikeHell($context) {
    echo(" Hi, I'm runLikeHell");
    //$this->next("runLikeHell");
    $this->setNextAndBreak("runLikeHell");
    return (" Bye, I'm runLikeHell");
  }
  
  protected function run3($context) {
    static $i=0;
    echo(" Hi, I'm run3 ".$i);
    $i++;
    if ($i==3) {
      $this->next("runOnce"); 
      return (" Bye ");
    }
    $this->next("run3");
  }
}

$th1=new TestHopper1;
echo(" Trying..".$th1->go(null,null));
echo("\r\nTrace: ".$th1->trace);
*/
?>