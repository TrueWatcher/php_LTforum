<?php

  class PlainAuthElements extends AuthElements {
    static function hiddenFields($context) {
      $h=parent::genericInput ("hidden","act","authPlain");
      return ($h);
    }
    static function plainChkbx () {}// disabled
  }

  class OpportunisticAuthElements extends AuthElements {
    static function hiddenFields($context) {
      $h=parent::genericInput ("hidden","act","authOpp");
      $h.=parent::genericInput ( "hidden","sn",$context->g("serverNonce") );
      $h.=parent::genericInput ( "hidden","realm",$context->g("realm") );
      return ($h);
    }  
    static function scriptHelper() {
      return ( parent::scriptHelper("protectHelper.js") );
    }
    static function scriptOnready() {
      $s="";
      $s.="$(\"plain\").checked=false;";
      $s.="$(\"authForm\").onsubmit=function() {";
      $s.="if ( !($(\"plain\").checked) ) {";
      $s.="  doAll();";
      $s.=" };";
      $s.="}";
      return ( parent::scriptOnready($s) );
    }
  }

  class StrictAuthElements extends AuthElements {
    static function hiddenFields($context) {
      $h=parent::genericInput ("hidden","act","authJs");
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
    static function scriptHelper() {
      return ( parent::scriptHelper("protectHelper.js") );
    }
    static function scriptOnready() {
      $s="";
      $s.="clearCredentials();";
      $s.="$(\"authForm\").onsubmit=function() {";
      $s.=" doAll();";
      $s.="}";
      return ( parent::scriptOnready($s) );
    }
  }

?>