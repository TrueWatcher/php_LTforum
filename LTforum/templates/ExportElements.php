<?php
/**
 * @pakage LTforum
 * @version 1.1 + search command
 */ 
/**
 * Functions just for View, usually creating control elements.
 * Need refactoring.
 * @uses $vr ViewRegistry
 */
class ExportElements {

  static function titleSuffix (ViewRegistry $context) {
    $s=$context->g("begin")."..".$context->g("end");
    return ($s);
  }
  
  static function idTitle ($msg) {
    if ( !empty($msg["id"]) ) return('<b title="'.$msg["id"].'">#</b>');
    return("");
  }
  
  static function localControls ($msg,ViewRegistry $context,PageRegistry $pageContext) {
    $c=self::idTitle($msg);
    return ($c);
  } 

  static function oneMessage ($msg,$localControlsString,$extra=null) {
    return (RollElements::oneMessage($msg,$localControlsString) );  
  }
  
  static function prevPageLink (ViewRegistry $context,$anchor="Previous page",$showDeadAnchor=false,$fragment="") {
    return("<a href=\"\">Previous page</a>");
  }
  
  static function nextPageLink (ViewRegistry $context,&$pageIsLast=false,$anchor="Next page",$showDeadAnchor=false) {
    return("<a href=\"./?begin=".($context->g("end")+1)."\">Next page</a>");
  }
  
  static function firstPageLink (ViewRegistry $context) {}
  
  static function lastPageLink (ViewRegistry $context) {}
  
  static function pagePanel (ViewRegistry $context) {}
  
  static function newMsgLink (ViewRegistry $context) {}
  
  static function searchLinkForm (ViewRegistry $context) {}

  static function lengthForm (ViewRegistry $context) {}

  static function numberForm (ViewRegistry $context) {}
  
  static function onreadyScript () {}

  static function bottomAlert () {}
  
}
?>