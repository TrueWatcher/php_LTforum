<?php
/**
 * @pakage LTforum
 * @version 1.1 added Search command, refactored View classes
 */ 
/**
 * Common template for RollElements,SearchElements and ExportElements.
 * @uses  ViewRegistry $context
 */
abstract class SectionElements {

  abstract static function titleSuffix (ViewRegistry $context);
  
  abstract static function localControls ($msg,ViewRegistry $context,PageRegistry $pageContext); 

  static function oneMessage ($msg,$localControlsString,$context) {
    $newline="<hr />\r\n";
    $newline.='<address>'.$msg['author'].' <em>wrote us on '.$msg["date"]." at ".$msg["time"]."</em>:";
    if ( $localControlsString ) $newline.='<b class="fr">'.$localControlsString.'</b>';
    $newline.="</address>\r\n";
    $newline.='<p class="m">'.$msg['message']."</p>\r\n";
    if ( !empty($msg['comment']) ) $newline.='<p class="n">'.$msg['comment']."</p>\r\n";
    return ($newline);
  } 
  
  abstract static function prevPageLink (ViewRegistry $context,$anchor,$showDeadAnchor=false,$fragment=""); 
  
  abstract static function nextPageLink (ViewRegistry $context,&$pageIsLast=false,$anchor="Next page",$showDeadAnchor=false); 
  
  abstract static function pagePanel (ViewRegistry $context);
  
  abstract static function searchLinkForm (ViewRegistry $context);
  
  abstract static function lengthForm (ViewRegistry $context);

  abstract static function numberForm (ViewRegistry $context);
  
  abstract static function onreadyScript ();

  abstract static function bottomAlert (PageRegistry $pageContext,$actualCount);
  
  static function genericLink ($queryString,$linkText,$fragment="") {
    if ( !empty($fragment) ) $queryString.="#".$fragment;
    $ahref="<a href=\"?%s\">%s</a>";
    return ( sprintf($ahref,$queryString,$linkText) );
  }
  
}
?>