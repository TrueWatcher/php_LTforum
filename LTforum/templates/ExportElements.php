<?php
/**
 * @pakage LTforum
 * @version 1.1 added Search command, refactored View classes
 */
/**
 * Functions for View, creating control elements for exporting html files.
 * Need refactoring.
 * @uses $vr ViewRegistry
 */
class ExportElements extends SectionElements {

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

  static function prevPageLink (ViewRegistry $context,$anchor,$showDeadAnchor=false,$fragment="") {
    return("<a href=\"\">".l("Previous page")."</a>");
  }

  static function nextPageLink (ViewRegistry $context,&$pageIsLast=false,$anchor="Next page",$showDeadAnchor=false) {
    return("<a href=\"./?begin=".($context->g("end")+1)."\">".l("Next page")."</a>");
  }

  static function pagePanel (ViewRegistry $context) {} // element is disabled

  static function searchLinkForm (ViewRegistry $context) {} // element is disabled

  static function lengthForm (ViewRegistry $context) {}  // element is disabled

  static function numberForm (ViewRegistry $context) {}  // element is disabled

  static function onreadyScript (SessionRegistry $sessionContext) {}  // element is disabled

  static function bottomAlert (PageRegistry $pageContext,$actualCount) {}  // element is disabled

}
?>