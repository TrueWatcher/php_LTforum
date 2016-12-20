<?php

  class PlainAuthElements extends AuthElements {
    static function hiddenFields($context) {
      $h=parent::genericInput ("hidden","reg","authPlain");
      return ($h);
    }
    static function plainChkbx () {}// disabled
  }

  class AlertAuthElements extends AuthElements {
    //static function hiddenFields($context) {}// disabled
    static function scriptHelper($context) {}
    static function scriptOnready() {}
    static function plainChkbx () {}
    static function authorInput ($label,$inputName,$authorName,$context,$pageContext) {}
    static function pswInput ($label,$inputName,$pswValue,$context,$pageContext) {}
    static function submitButton() {}
    static function titleSuffix() { return("Login : alert"); }
    static function realmP($context) {}
    // pAlert inherited
  }  
  
  class OpportunisticAuthElements extends AuthElements {
    static function hiddenFields($context) {
      $h=parent::hiddenFields($context);
      $h.=parent::genericInput ("hidden","reg","authOpp");
      $h.=parent::genericInput ( "hidden","sn",$context->g("serverNonce") );
      $h.=parent::genericInput ( "hidden","realm",$context->g("realm") );
      return ($h);
    }  
    static function scriptHelper($context) {
      return ( parent::scriptHelper($context,"authHelper.js") );
    }
    static function scriptOnready() {
      $s="";
      $s.="$(\"plain\").checked=false;";
      $s.="$(\"authForm\").onsubmit=function() {";
      $s.="if ( !($(\"plain\").checked) ) {";
      $s.="  return( doAll() );";
      $s.=" };";
      $s.="}";
      return ( parent::scriptOnready($s) );
    }
  }

  class StrictAuthElements extends AuthElements {
    static function hiddenFields($context) {
      $h=parent::hiddenFields($context);
      $h.=parent::genericInput ("hidden","reg","authJs");
      $h.=parent::genericInput ( "hidden","sn",$context->g("serverNonce") );
      $h.=parent::genericInput ( "hidden","realm",$context->g("realm") );
      return ($h);
    }
    static function plainChkbx () {}// disabled
    static function authorInput ($label,$inputName,$authorName,$context,$pageContext) {
      return ( parent::authorInput ($label,$inputName,"You need Javascript to register",$context,$pageContext) );
    }
    static function pswInput ($label,$inputName,$pswValue,$context,$pageContext) {
      return ( parent::authorInput ($label,$inputName,"You need Javascript to register",$context,$pageContext) );
    }
    static function scriptHelper($context) {
      return ( parent::scriptHelper($context,"authHelper.js") );
    }
    static function scriptOnready() {
      $s="";
      $s.="clearCredentials();";
      $s.="$(\"authForm\").onsubmit=function() {";
      $s.=" return( doAll() );";
      $s.="}";
      return ( parent::scriptOnready($s) );
    }
  }

?>