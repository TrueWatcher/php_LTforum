<?php
/**
 * @pakage LTforum
 * @version 1.2 added SessionManager
 */
 
  class AuthRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
    
    function exportToSession() {
      $what=["pers","secret","clientCount","serverCount"];
      $exp=[];
      foreach ($what as $k) {
        $exp[$k]=$this->g($k);
      }
      return($exp);
    }
    
    function readInput($superGlobal) {
      $what=["reg","user","ps","cn","response","plain","pers"];
      $exp=[];
      foreach ($what as $k) {
        if ( array_key_exists($k,$superGlobal) ) $this->s($k,$superGlobal[$k]);
      }
    }
    
    function readSession() {
      $what=["serverNonce"];
      $this->s("serverNonce",$_SESSION["serverNonce"]);
    }
  }

  class AccessController extends Hopper {
    function __construct() {
      $this->nextState="verifySession";
    }
    
    static $groupFileName=".group";
    
    static function makeHa1($userName,$realm,$password) {
      $realm=strtolower($_SERVER["SERVER_NAME"]).$realm;
      //echo(">>".$userName.$realm.$password);
      return ( md5($userName.$realm.$password) ); 
    }
    
    static function makeResponse($sn,$ha1,$cn) {
      return ( md5($sn.$ha1.$cn) ); 
    }
    
    static function iterateSecret($secret,$cNonce) {
      return ( md5($secret.$cNonce) );
    }
    
    static function createEmptyGroupFile($path,$forum) {
      $nl="\n";
      $s="";
      $s.="; must not contain empty lines and must end with NL !".$nl;
      $s.="[".$forum."]".$nl;
      $s.="admin=".self::makeHa1("admin",$forum,"admin").$nl;
      $s.="[".$forum."Admins]".$nl;
      $s.="admin=";
      file_put_contents( $path.self::$groupFileName, $s);
    }
    
    static function parseGroup($realm,$context) {
      $targetPath="";
      if($context) $targetPath=$context->g("targetPath");
      $groupFile=$targetPath.self::$groupFileName;
      if ( !file_exists($groupFile) ) {
        //throw new AccessException ("No such file:".$groupFile."!");
        self::createEmptyGroupFile($targetPath,$context->g("realm"));
      }
      $parsed=parse_ini_file($groupFile,true);
      //print_r($parsed);
      if (!array_key_exists($realm,$parsed)) throw new AccessException ("Section ".$realm." not found in the file ".$groupFile);
      return($parsed[$realm]);
    }
    
    static function makeServerNonce() {
      if ( is_callable("openssl_random_pseudo_bytes") ) {
        $openSslOutcome=false;
        $sn=openssl_random_pseudo_bytes(16,$openSslOutcome);
        //$ar->s("alert",$ar->g("alert")." tried secure:".$openSslOutcome." " );
      }
      else {
        $sn0=(string)random_int(0,PHP_INT_MAX);
        $sn=md5( $sn0.time(),true );// true for binary output
      }
      $sn=base64_encode($sn);
      return($sn);
    }
    
    function isAdmin($user,$context) {
      $admins=self::parseGroup( $context->g("realm")."Admins", $context );
      //print_r($admins);
      if ( array_key_exists($user,$admins) ) return (1);
      return (0);
    }
    
    function makeRedirectUri() {
      $qs=$_SERVER["QUERY_STRING"];
      $matches=[];
      $r1=preg_match("~\&(reg=[^&]*)~",$qs,$matches);
      if ( !$r1 ) $r2=preg_match("~^(reg=[^&]*)~",$qs,$matches);
      //print ($qs."\r\n");
      //print_r($matches);
      if ( !empty($matches) ) {
        $qs=str_replace($matches[1],"",$qs);
        if ( (strpos($qs,"&"))===0 ) $qs=ltrim($qs,"&");
        if ( ($p=strpos($qs,"&&"))!==false ) $qs=str_replace("&&","&",$qs);
        if ( (strrpos($qs,"&"))===(strlen($qs)-1) ) $qs=rtrim($qs,"&");
      }
      //echo(" qs=".$qs."! ");
      if ( !empty($qs) ) {
        /*$url = 'http://';
        if ( (array_key_exists("HTTPS",$_SERVER)) && $_SERVER['HTTPS'] ) $url = 'https://';
        $url .= $_SERVER['HTTP_HOST'];// Get the server
        
        $request=$url.$qs;*/
        if (!class_exists("Act")) throw new UsageException ("AccessController: please, include all dependencies");
        $parsedReqUri=parse_url($_SERVER["REQUEST_URI"]);
        $path=$parsedReqUri["path"];
        $file="";
        $f="";
        if (preg_match("~\/([^.\/]*\.php)~",$path,$f) ) $file=$f[1];
        //print_r($f);
        //echo (" path=".$path." file=".$file);
        $qs=Act::myAbsoluteUri()."/".$file."?".$qs;
      }
      return ($qs);
    }
    
    function iniSet(AuthRegistry $ar) {
      ini_set("session.serialize_handler","php_serialize");
      ini_set("session.use_only_cookies",1);
      ini_set("session.use_cookies",1);
      ini_set("session.use_strict_mode",1);
      $sessionsDir=realpath( __DIR__. '/../sessions');// absolute path required
      //echo($sessionsDir);
      if ( !file_exists($sessionsDir) ) throw new AccessException ("Sessions directory ".$sessionsDir." not found");
      if ( !is_writable($sessionsDir) ) throw new AccessException ("Sessions directory ".$sessionsDir." is not writable, check the permissions");
      ini_set('session.save_path',$sessionsDir);
      ini_set('session.gc_probability',25);
      ini_set("session.gc_maxlifetime", $ar->g("maxDelayPage"));    
    }
    
    function verifySession(AuthRegistry $ar) {
      self::iniSet($ar);
      session_start();
      
      if ( array_key_exists("reg",$_REQUEST) && $_REQUEST["reg"]=="reset" ) {
        //unset($_SESSION);
        //session_destroy();
        //session_start();
        $_SESSION["state"]="aborted";
        $ar->s("alert"," Session aborted by user ");
        $this->next("requestLogin");
        return;      
      }
      if( empty($_SESSION) || !array_key_exists("state",$_SESSION) ) {
        $this->next("requestLogin");
        return;
      }
      $t=time();
      if ( empty($_SESSION["notBefore"]) || empty($_SESSION["notAfter"]) || $t > $_SESSION["notAfter"] ) {
        $_SESSION["state"]="junk";
        $this->next("requestLogin");
        return;      
      }
      if ( array_key_exists("realm",$_SESSION) && $_SESSION["realm"]!==$ar->g("realm") ) {
        $_SESSION["state"]="trip";
        $ar->s("alert","You need to register for a new thread");
        $this->next("requestLogin");
        return;      
      }
      if ( $t < $_SESSION["notBefore"] ) {
        $ar->s("alert","Please, wait a few seconds and click \"Refresh\"");
        $this->next("showAuthAlert");
        return; 
      }
      if ( $_SESSION["state"]=="preAuth" ) {
        $this->next("selectAuth");
        return;
      }
      if ( !array_key_exists("authName",$_SESSION) || $_SESSION["state"]!="auth" ) {
        $ar->s("alert","Something is wrong");
        $this->next("showAuthAlert");
        return;
      } 
      $user=$_SESSION["authName"];
      //echo ($user."====".(self::isAdmin($user)) );
      if ( $ar->g("isAdminArea") && !(self::isAdmin($user,$ar)) ) {
        $ar->s("alert","This area is for admins only");
        $this->next("requestLogin");
        return;      
      }
      return (true);
    }
    
    function requestLogin(AuthRegistry $ar) {
      if ($_SESSION) {
        unset($_SESSION);
        session_destroy();
        session_start();        
      }
      // initialize authentication
      $sn=self::makeServerNonce();
      $ar->s("serverNonce",$sn);// needed by form
  
      //$_SESSION["registry"]=$ar->export();
      $_SESSION["serverNonce"]=$sn;
      $_SESSION["notBefore"]=time()+$ar->g("minDelay");
      $_SESSION["notAfter"]=time()+$ar->g("maxDelayAuth");
      $_SESSION["state"]="preAuth";
      if ( $r=self::makeRedirectUri() ) $_SESSION["origUri"]=$r;
      //echo(">".$r);
      //exit();

      //session_write_close ();
      //$ar->s("alert",session_id());
      // show form
      require($ar->g("templatePath")."AuthElements.php");
      require($ar->g("templatePath")."SubAuthElements.php");
      $formSelect=[0=>"PlainAuthElements",1=>"OpportunisticAuthElements",2=>"StrictAuthElements"];
      $ar->s( "controlsClass", $formSelect[$ar->g("authMode")] );
      include($ar->g("templatePath")."authForm.php");
      $this->setBreak();// overly safe
      return(false);
    }
    
    function selectAuth (AuthRegistry $ar) {
      $ar->readInput($_REQUEST);
      $ar->readSession();
      if ( !($ar->g("reg")=="authPlain" || $ar->g("reg")=="authOpp" || $ar->g("reg")=="authJs") ) {
        // only authentication should happen in preAuth state, so reset session
        $ar->s("alert"," Out-of-order request was discarded ");
        $this->next("requestLogin");
        return;      
      }
      $tryPlainText=( $ar->g("reg")=="authPlain" || ( $ar->g("reg")=="authOpp" && $ar->g("plain") ) );
      $tryDigest=( $ar->g("reg")=="authJs" || ($ar->g("reg")=="authOpp" && !$ar->g("plain") ) );
      if ( !$tryPlainText && !$tryDigest ) {
        // something strange
        $ar->s("alert"," Out-of-order request was discarded ");
        $this->next("requestLogin");
        return;      
      }
      // pre-authentication checks
      if ($tryPlainText) {
        if ( $ar->g("authMode") == 2 ) {
          $ar->s("alert"," Plaintext auth is turned off on the server ");
          $this->next("requestLogin");
          return;
        }
        if ( empty($ar->g("user")) || empty($ar->g("ps")) ) {
          $ar->s("alert"," Empty username or password ");
          $this->next("requestLogin");
          return;
        }
        $this->next("authPlain");
        return;       
      }
      else { // tryDigest
        if ( $ar->g("authMode") == 0 ) {
          $ar->s("alert"," Digest auth is turned off on the server ");
          $this->next("requestLogin");
          return;        
        }
        if ( $ar->g("user") || $ar->g("ps") ) {
          $ar->s("alert"," This mode takes no credentials ");
          $this->next("requestLogin");          
          return;        
        }
        if ( empty($ar->g("response")) || empty($ar->g("cn")) ) {
          $ar->s("alert"," Missing login data ");
          $this->next("requestLogin");          
          return;        
        }
        $this->next("authDigest");
        return;       
      }
    }
    
    function authPlain(AuthRegistry $ar) {
    
      //echo("Trying auth plaintext ");
      $realm=$ar->g("realm");
      $applicantName=$ar->g("user");
      $applicantPsw=$ar->g("ps");
      $applicantHa=self::makeHa1($applicantName,$realm,$applicantPsw);
      //echo(">".makeHa1($_REQUEST["user"],$ar->g("realm"),$_REQUEST["ps"])."<");// DEBUG  
      $foundName="";
      $users=self::parseGroup($ar->g("realm"),$ar);
      // simply use array as dictionary
      if ( array_key_exists($applicantName,$users) && $users[$applicantName]===$applicantHa ) $foundName=$applicantName;
      if ( !$foundName ) {// fail
        $this->next("requestLogin");
        sleep(rand(5,10));
        $ar->s("alert","Wrong login or/and password");
        return;
      }
      // success
      $_SESSION["authName"]=$foundName;
      $ar->s("authName",$foundName);
      $ar->s("alert","Plaintext authentication OK as ".$foundName);
      $this->next("authSuccess");
    }
    
    function authDigest(AuthRegistry $ar) {
    
      //echo(" Trying JS digest authentication ");
      $foundName="";
      $users=self::parseGroup($ar->g("realm"),$ar);
      //print_r($users);
      //echo("sn>".$sn);
      
      // hash -> proposed response -> check -> user name
      // so no need to send name in open
      foreach ($users as $name=>$ha) {
        $tryResponse=self::makeResponse( $ar->g("serverNonce"), $ha, $ar->g("cn") );
        if ( $tryResponse == $ar->g("response") ) {
          $foundName=$name;
          break;
        }
      }
      if ( !$foundName ) {
        $this->next("requestLogin");
        sleep(5);
        $ar->s("alert","Wrong login or/and password "/*." response=".$ar->g("response")*/);
        return;
      }
      // registered OK
      $ar->s("authName",$foundName);
      $ar->s( "secret",self::iterateSecret( $ha, $ar->g("cn") ) );
      $ar->s("clientCount",1);
      $ar->s("serverCount",1);  
      $ar->s("alert","Digest authentication OK as ".$foundName);
      $_SESSION["registry"]=$ar->exportToSession();// JS secrets
      
      $this->next("authSuccess");
    }
    
    function authSuccess(AuthRegistry $ar) {
      if ( $ar->g("isAdminArea") && !self::isAdmin($ar->g("authName"),$ar ) ) {
        $this->next("requestLogin");
        $ar->s("alert","This is a restricted area");
        return;
      }
      $_SESSION["authName"]=$ar->g("authName");
      $_SESSION["realm"]=$ar->g("realm");
      $_SESSION["notAfter"]=time()+$ar->g("maxDelayPage");
      $_SESSION["state"]="auth";
      unset($_SESSION["serverNonce"]);
      session_regenerate_id();
      //session_write_close ();
      if ( /*false &&*/ array_key_exists("origUri",$_SESSION) ) {
        $r=$_SESSION["origUri"];
        //unset($_SESSION["origUri"]);
        header( "Location: ".$r );
        echo ( "redirected to ".$r );
        $this->setBreak();
        return ( "redirected to ".$r );
      }
      return (true);
    }
    
    function showAuthAlert(AuthRegistry $ar) {
      // show form
      require($ar->g("templatePath")."AuthElements.php");
      require($ar->g("templatePath")."SubAuthElements.php");
      $ar->s( "controlsClass", "AlertAuthElements" );
      include($ar->g("templatePath")."authForm.php");
      $this->setBreak();// overly safe
      return (false);
    }
    
  }// end SessionManager
?>