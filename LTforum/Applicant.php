<?php

/**
 * Deals with authentication and other procedures changing $_SESSION.
 * used by AccessController
 */

class Applicant {

  protected $status;
  protected $authMode;
  protected $authMethod=null;
  protected $authName;
  protected $realm;
  //protected $isAdmin=false;
  protected $c;
    
  /**
   * Initializes Applicant for routine-level checks.
   * Sets $c, $status, $authName
   * Initialization for registration and advanced checks is initFull() 
   * @param {Object AuthRegistry} $ar
   * @uses $_SESSION
   * @return nothing
   */
  public function initMin (AuthRegistry $ar) {
    //print_r($_SESSION);
    $this->c = $ar;
    
    //$this->realm = $this->c->g("realm");
    //echo($this->realm);
    
    if ( empty($_SESSION) ) $this->status="zero";
    else {
      if ( !array_key_exists("status",$_SESSION) ) {
        throw new UsageException("No status found in existing SESSION");
      }
      $this->status = $_SESSION["status"];
      if ( array_key_exists("authName",$_SESSION) ) {
        $this->authName = $_SESSION["authName"];
      }
    }
  }
  
  /**
   * Currently does nothing as advanced procedures work directly with AuthRegistry and SESSION.
   */
  public function initFull(AuthRegistry $ar) {
    return(true);
    
    $this->authMode = $ar->g("authMode");
    if ( empty($_SESSION) ) return (false);
    $this->readSession();
  }
  
  /**
   * Utility function.
   */
  protected function readSession() {
    $keys=[/*"status"=>"status", "authName"=>"authName",*/ "isAdmin"=>"isAdmin", "realm"=>"realm", /*"notBefore"=>"notBefore", "activeUntil"=>"activeUntil",*/ "serverNonce"=>"serverNonce"  ];
    if ( !$_SESSION ) throw new UsageException("Applicant::readSession: no active session found !");//return(false);
    $i=0;
    foreach($keys as $sessKey=>$regKey) {
      if ( array_key_exists($sessKey,$_SESSION) && $_SESSION[$sessKey] ) {
        $this->$regKey = $_SESSION[$sessKey];
        $i++;
      }
    }
    return($i);
  }
  
  /**
   * Writes time limits to SESSION.
   * @param $int $lower minimal responce delay
   * @param $int $upper maximum responce delay (before downgrade to postAuth state)
   * @return nothing
   */
  public function setTimeLimits ($lower,$upper) {
    if ($lower) $_SESSION["notBefore"] = time()+$lower;
    if ($upper) $_SESSION["activeUntil"] = time()+$upper;
  }
  
  /**
   * Returns Applicant::status.
   * @return string
   */
  public function getStatus() {
    return ($this->status);
  }
  
  /**
   * @param string $s arbitrary status
   * @return true if Applicant::status equals to argument, false otherwise
   */
  public function statusEquals($s) {
    return ( !empty($s) && ($this->status === $s) );
  }
  
  /**
   * Changes state of SESSION (and Applicant::status).
   * @param string $newStatus
   * @return nothing
   */
  public function setStatus($newStatus) {
    switch ($newStatus) {
    case "zero":
      //unset($_SESSION); // does not help
      session_destroy();
      session_start();
      $this->authName = null;
      break;
        
    case "preAuth":
      if (array_key_exists("authName",$_SESSION)) unset($_SESSION["authName"]);
      if (array_key_exists("realm",$_SESSION)) unset($_SESSION["realm"]);
      if (array_key_exists("isAdmin",$_SESSION)) unset($_SESSION["isAdmin"]);
      if ( ! array_key_exists("serverNonce",$_SESSION) ) throw new UsageException ("Applicant::setStatus: missing serverNonce");
      $_SESSION["status"]="preAuth";
      session_write_close(); 
      break;
        
    case "postAuth":
      if ( ! array_key_exists("authName",$_SESSION)) throw new UsageException ("Applicant::setStatus: missing authName in SESSION");
      if ( ! array_key_exists("serverNonce",$_SESSION) ) throw new UsageException ("Applicant::setStatus: missing serverNonce");
      if (array_key_exists("isAdmin",$_SESSION)) unset($_SESSION["isAdmin"]);
      $_SESSION["status"]="postAuth";
      session_write_close();
      break;
        
    case "active":
      if ( empty($this->authName) ) throw new UsageException ("Applicant::setStatus: empty authName");
      if ( empty($this->realm) ) throw new UsageException ("Applicant::setStatus: empty realm");
      $_SESSION["authName"] = $this->authName;
      $_SESSION["realm"] = $this->realm;
      if ( array_key_exists("serverNonce",$_SESSION) ) unset ( $_SESSION["serverNonce"] );
      $_SESSION["status"]="active";
      // no write_close here because SESSION may be used by follow-up page code
      break;
        
    case "noChange":
      return;
        
    default:
      throw new UsageException (" Unknown state :".$newStatus."! ");
      return;
    }
    $this->status = $newStatus;
    //echo(" New status:".$newStatus." ");
    //print_r($_SESSION);
  }
  
  /**
   * Performs pre-registration checks and selects processing method.
   * @return true on success, error message on failure
   */
  public function beforeAuth (AuthRegistry $ar) {    
    $tryPlainText=( $ar->g("reg")=="authPlain" || ( $ar->g("reg")=="authOpp" && $ar->g("plain") ) );
    $tryDigest=( $ar->g("reg")=="authJs" || ($ar->g("reg")=="authOpp" && !$ar->g("plain") ) );
    if ( !$tryPlainText && !$tryDigest ) {
        // something strange
      return(" Out-of-order request was discarded ");      
    }
    // pre-authentication checks depending on configured mode
    if ($tryPlainText) {
      if ( $ar->g("authMode") == 2 ) { return(" Plaintext auth is turned off on the server ");}
      if ( empty($ar->g("user")) || empty($ar->g("ps")) ) { return("Empty username or password ");}
      $this->authMethod = "authPlain";
      return true;       
    }
    else { // tryDigest
      if ( $ar->g("authMode") == 0 ) { return(" Digest auth is turned off on the server ");}
      if ( $ar->g("user") || $ar->g("ps") ) { return(" This mode takes no credentials ");}
      if ( empty($ar->g("response")) || empty($ar->g("cn")) ) { return(" Missing login data ");}
      $this->authMethod = "authDigest";
      return true;   
    }
  }
  
  /**
   * Performs actual registration and post-registration checks.
   * @return true on success, error message on failure
   */
  public function processAuth() {
    $method=$this->authMethod;
    $ret=$this->$method( $this->c );
    if ( $ret!==true ) return($ret);
    $this->realm = $this->c->g("realm");
    
    //check admin rights at registration and save a flag to SESSION
    $ret=$this->authAdmin( $this->c );
    if ( $ret===true ) $_SESSION["isAdmin"]=true;
    else $_SESSION["isAdmin"]=false;
    
    // check admin access
    $ret=$this->checkAdmin( $this->c );
    return $ret;
  }
  
  /**
   * Sends Redirect header.
   * If original page request contained any params, they must have been saved to SESSION by AccessController::showForm. Now it's time to look for them and send the redirect header (so called PRG pattern).
   * @return true or message on redirect, false on no redirect required
   */
  public function optionalRedirect() {
    if ( array_key_exists("origUri",$_SESSION) ) {
        $r=$_SESSION["origUri"];
        unset($_SESSION["origUri"]);
        header( "Location: ".$r );
        session_write_close();
        //exit();
        return ( "redirected to ".$r );
     }
     return false;
  }
  
  /**
   * Checks existence of parameters, which are required for an Active state SESSION.
   * @return true on success, false or error message on failure
   */
  public function checkActiveParams() {
    return( $this->checkSessionKeys( ["notBefore","activeUntil","status","realm","authName"] ) );
  }
  
  /**
   * An utility function.
   * @param array $keys keys to look up in SESSION 
   * @return true on success, false or error message on failure   
   */
  public function checkSessionKeys($keys) {
    if ( empty($_SESSION) ) return "Empty session";//throw new UsageException ("checkActiveParams: empty SESSION");
    foreach ($keys as $k) {
      if ( !array_key_exists($k,$_SESSION) ) return ("Missing required SESSION key ".$k."!");
      if ( empty($_SESSION[$k]) ) return ("Empty value at SESSION key ".$k."!");
    }
    return true;  
  }
  
  public function checkNotBefore() {
    if ( !array_key_exists("notBefore",$_SESSION) ) throw new UsageException ("checkNotBefore: no such key found in SESSION");
    if ( time() < $_SESSION["notBefore"] ) return false; // failed
    return true;// passed
  }
  
  public function checkActiveUntil() {
    if ( !array_key_exists("activeUntil",$_SESSION) ) throw new UsageException ("checkActiveUntil: no such key found in SESSION");
    if ( time() > $_SESSION["activeUntil"] ) return false; // failed
    return true;// passed  
  }
  
  public function checkAdmin (AuthRegistry $ar) {
    if ( !$ar->g("isAdminArea") ) return true; // not needed
    if ( array_key_exists("isAdmin",$_SESSION) && $_SESSION["isAdmin"] ) return true;// passed
    // failed
    return ("This area is for admins only");
  }
  
  public function checkRealm (AuthRegistry $ar) {
    if ( $ar->g("realm") && $_SESSION["realm"]===$ar->g("realm") ) return true;// passed
    return ("Please, register to a new thread");
  }
  
   /**
     * Checks user form and performs PLaintext auth.
     * Passes to authSuccess on success or back to requestLogin on failure
     * @uses user name
     * @uses user password
     * @uses AccessController::parseGroup
     * @uses AccessController::makeHa1
     * @param {object AuthRegistry} $context
     * @returns true on success, false or error message on failure
     */     
  protected function authPlain (AuthRegistry $ar) {
      if ( !class_exists("AccessController") ) throw new UsageException ("Please, include the  AccessController class");
      //echo("Trying auth plaintext ");
      $realm=$ar->g("realm");
      $applicantName=$ar->g("user");
      $applicantPsw=$ar->g("ps");
      $applicantHa=AccessController::makeHa1($applicantName,$realm,$applicantPsw);
      //echo(">".makeHa1($_REQUEST["user"],$ar->g("realm"),$_REQUEST["ps"])."<");// DEBUG  
      $foundName="";
      $users=AccessController::parseGroup($ar->g("realm"),$ar);
      // simply use array as dictionary
      if ( array_key_exists($applicantName,$users) && $users[$applicantName]===$applicantHa ) $foundName=$applicantName;
      if ( !$foundName ) {// fail
        //sleep(rand(5,10));
        return("Wrong login or/and password");
      }
      // success
      $this->authName = $foundName;
      $ar->s("authName",$foundName);
      $ar->s("alert","Plaintext authentication OK as ".$foundName);
      return(true);
  }
  
    /**
     * Checks user form and performs Digest auth.
     * Passes to authSuccess on success or back to requestLogin on failure
     * @uses client nonce
     * @uses client response
     * @uses AccessController::parseGroup
     * @uses AccessController::makeResponse
     * @param {object AuthRegistry} $context
     * @returns true on success, false or error message on failure
     */
  function authDigest (AuthRegistry $ar) {
      if ( !class_exists("AccessController") ) throw new UsageException ("Please, include the  AccessController class");
      //echo(" Trying JS digest authentication ");
      $foundName="";
      $users=AccessController::parseGroup($ar->g("realm"),$ar);
      //print_r($users);
      //echo("sn>".$sn);
      
      // Cycle: hash -> proposed response -> check -> user name
      // so no need to send the name in a request
      foreach ($users as $name=>$ha) {
        $tryResponse=AccessController::makeResponse( $ar->g("serverNonce"), $ha, $ar->g("cn") );
        if ( $tryResponse == $ar->g("response") ) {
          $foundName=$name;
          break;
        }
      }
      if ( !$foundName ) { // fail
        //sleep(5);
        return ("Wrong login or/and password "/*." response=".$ar->g("response")*/);
      }
      // registered OK
      $ar->s("authName",$foundName);
      $this->authName = $foundName;
      $ar->s( "secret",AccessController::iterateSecret( $ha, $ar->g("cn") ) );
      $ar->s("clientCount",1);
      $ar->s("serverCount",1);  
      $ar->s("alert","Digest authentication OK as ".$foundName);
      $_SESSION["registry"] = $ar->exportToSession();// JS secrets
      return(true);
  }
  
    /**
     * Checks if the given user has admin rights against .group file.
     * @uses AccessController::parseGroup
     * @param {object AuthRegistry} $context
     * @returns true if has, false or error message if has not
     */
  protected function authAdmin(AuthRegistry $ar) {
    if ( !class_exists("AccessController") ) throw new UsageException ("Please, include the  AccessController class");
    
    $admins=AccessController::parseGroup( $ar->g("realm")."Admins", $ar );
    if ( array_key_exists( $this->authName, $admins ) ) return (true);
    return ("Not an admin");
  }
  
  /**
   * Checks if there are new messages since the latest current user's message.
   * Main feature of the postAuth state.
   * @return string notification
   */
  public function getUnanswered() {
    if ( !class_exists("CardfileSqlt") || !class_exists("AccessController") ) throw new UsageException ("Some dependency is missing");
    $dbFile = $this->c->g("targetPath").$this->c->g("realm");
    //try {
      $dbm=new CardfileSqlt($dbFile,false);
    //}
    //catch (Exception $e) {
      //AccessController::showAuthAlert ($this->c,$e->getMessage());
    //}
    $found=$dbm->getLastMsgByAuthor($this->authName);
    if (!$found) { // no messages by this user in this forum
      return("No messages by this user");
    }
    // check if the latest message is authored by this user
    $last=$dbm->getLastMsg();
    $unanswered = $last["id"] - $found["id"];
    $note="Unanswered messages:".$unanswered;
    if ( $unanswered ) $note.=", latest: ".$last["date"]." ".$last["time"];
    return ($note);
  }
}

?>