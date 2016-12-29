<?php
/**
 * An utility class implementing the State pattern.
 * May be used to build State Machines or complicated multi-unit logic processors.
 * Each State is a method in child class.
 * Each state has the same single argument $context.
 * Each State ends in $this->next(nextState) or $this->setNextAndBreak(nextState) or $this->setBreak() (and "return" after that).
 * The first State is set in Superclass::__construct as $this->nextState="init".
 * The main cycle is called as Superclass.go()
 * Imposes a limit on cycles; keeps a trace of invoked States
 * An example is given below.
 */
class Hopper {
  protected $currentState;
  protected $nextState;
  protected $stopNow=0;
  protected $maxHops=10;
  public $trace=[];
  
  /**
   * Called by a State method to set the next State method to invoke immediately, like GoTo.
   * @param string $nextHop neme of next State method
   * @returns void
   */
  protected function next($nextHop) {
    if( !method_exists($this,$nextHop) ) throw new UsageException("Cannot find the method ".$nextHop);
    $this->nextState=$nextHop;
  }

  /**
   * Called by a State method to set the next State method to invoke on next go() call, like a State Machine. Stops main cycle.
   * @param string $nextHop neme of next State method
   * @returns void
   */  
  protected function setNextAndBreak($nextHop) {
    $this->setBreak();
    $this->next($nextHop);
  }
  
  /**
   *  Called by a State method to stop the main cycle like Return.
   */
  protected function setBreak() {
    $this->stopNow=1;  
  }
  
  /**
   * Translates a State name into the method call.
   * @param string $hop current State name
   * @param mixed $context common context
   * @returns mixed return value of the called State
   */
  protected function call($hop,$context) {
    $ret=$this->$hop($context);
    return($ret);
  }

  /**
   * Main cycle for multi-unit processors or one step for State Machine.
   * @throws UsageException on exceeding cycles limit
   * @param mixed $context common context of all States
   * @param string $from name of the first State, if not set in __construct
   * @returns mixed the return value of last called State method
   */  
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