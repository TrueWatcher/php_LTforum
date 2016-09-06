<?php
/**
 * @pakage LTforum
 * @version 1.1 + search command
 */ 
/**
 * Functions just for View (Search Results), usually creating control elements.
 * @uses $vr ViewRegistry
 */
class SearchElements {

  static function titleSuffix (ViewRegistry $context) {
    $s="search for \"".$context->g("query")."\"";
    return ($s);
  }

  static function idLink ($msg,ViewRegistry $context) {
    $qs="act=&amp;begin=".$msg["id"]."&amp;length=".$context->g("length");
    $link=RollElements::genericLink ( $qs,"#".$msg["id"] );
    return('<b title="View page">'.$link.'</b>&nbsp;');     
  }
  
  static function localControls ($msg,ViewRegistry $context,PageRegistry $pageContext) {
    $c=self::idLink($msg,$context);
    return ($c);
  } 
  
  static function oneMessage ($msg,$localControlsString,$context) {
    if ( $context->g("highlight") ) {
      // repeat search on each field and insert highlighting tags into each field
      foreach ($msg as $key=>&$field) {
        if ( $key!=="id" && !empty($field) ) {
          $field=" ".$field." ";
          $found=Act::searchInString($field,$context->g("searchTerms"));
          if ( !empty($found) ) {
            $field=self::highlight($field,$found,"h");
            $field=self::openHrefs($field);
          }
        }
      }
    }
    $html=RollElements::oneMessage ($msg,$localControlsString);
    return($html);
  }

  /**
   * Turns hyperlinks into pieces of text to make their addresses visible in search results
   * @param string $str
   * @returns string
   */
  private static function openHrefs($str) {
    $str=str_replace("<a ","&lt;a ",$str);
    $str=str_replace("</a>","&lt;/a>",$str);
    return($str);
  }

  /**
   * Wraps <span> tags around found entries.
   * @param string $str object, usually a text+html field
   * @param array $found results from Act::searchInString()
   * @param string $class css class to be added to span
   * @return string text with highlight tags
   */
  static function highlight ($str,array $found,$class,$color="") {
    $starts=[];
    $ends=[];
    $span0="<span class=\"".$class."\"";
    if (!empty($color)) $span0.=" style=\"background-color:".$color."\"";
    $span0.=">";
    $span1="</span>";
    
    if ( !$found ) return ($str);// something is wrong with new search
    $starts=$found[0];
    sort($starts);    
    $ends=$found[1];
    sort($ends);
    $blockList=self::fixOverlap($starts,$ends);
     
    $res="";
    $pos=0;
    for ($i=0;$i<count($starts);$i++) {
      //print("{$starts[$i]}_{$ends[$i]} ");
      if ( $ends[$i]<=$starts[$i] ) break;
      if ( in_array($i,$blockList) ) break;
      $res.=mb_substr($str,$pos,$starts[$i]-$pos+1);
      $res.=$span0;
      $pos=$starts[$i]+1;
      $res.=mb_substr($str,$pos,$ends[$i]-$pos+1);
      $res.=$span1;
      $pos=$ends[$i]+1;      
    }
    $res.=mb_substr($str,$pos);
    return ($res);
  }
  
  static function fixOverlap (array &$s, array &$e) {
    $blockList=[];
    if ( count($s)!=count($e) ) throw new UsageException("Array counts are different");
    for ($i=0;$i<count($s);$i++) {
      if ( !in_array($i,$blockList) ) {
        for ($j=$i+1;$j<count($s);$j++) {
          $collides=( ( $s[$i]>=$s[$j] && $s[$i]<=$e[$j] ) || ( $e[$i]>=$s[$j] && $e[$i]<=$e[$j] ) );
          // start or end of one interval falls inside another interval
          if ($collides) {
            // try to extend the first interval
            $s[$i]=min($s[$i],$s[$j],$e[$i],$e[$j]);
            $e[$i]=max($s[$i],$s[$j],$e[$i],$e[$j]);
            // dismiss the second interval
            $blockList[]=$j;
          }        
        }
      }
    }  
    return ($blockList);
  }
  
  static function prevPageLink (ViewRegistry $context,$anchor="View first page",$showDeadAnchor=false) {
    $anchor="View first page";
    $qs="act=&amp;begin=1&amp;length=".$context->g("length");
    return( RollElements::genericLink($qs,$anchor) );    
  }
  
  static function nextPageLink (ViewRegistry $context,&$pageIsLast=false,$anchor="View last page",$showDeadAnchor=false) {
    $qs="act=&amp;end=-1&amp;length=".$context->g("length");
    return( RollElements::genericLink($qs,$anchor) );   
  }
  
  static function pagePanel (ViewRegistry $context) {}
    
  static function onreadyScript () {}
  
  static function numberForm (ViewRegistry $context) {}
  
  static function lengthForm (ViewRegistry $context) {
    $lengths=array(10,20,50,100,"*");
    
    $form="<form action=\"\" method=\"get\" id=\"resultsPerPage\"><p>Results per page: ";
    $form.="<select name=\"searchLength\">";
    $optList="";
    foreach ($lengths as $l) {
      $optList.="<option value=\"".$l."\"";
      if ( $l==$context->g("searchLength") ) $optList.=" selected=\"selected\"";
      $optList.=">".$l."</option>";
    } 
    //<option value="10">10</option>
    $form.=$optList;
    $form.="</select> <input type=\"submit\" value=\"Apply\"/>";
    $form.="<input type=\"hidden\" name=\"act\" value=\"search\"/>";
    $form.="<input type=\"hidden\" name=\"query\" value='".$context->g("query")."'/>";
    $form.="<input type=\"hidden\" name=\"length\" value=\"".$context->g("length")."\"/>";
    $form.="<input type=\"hidden\" name=\"order\" value=\"".$context->g("order")."\"/>";
    $form.="</p></form>";
    return ($form);
  }
  
  static function searchLinkForm (ViewRegistry $context) {
    //$orders=["asc"=>,"desc"];
  
    $form="<form action=\"\" method=\"get\" id=\"search\"><p>Search: ";
    $form.="<input type=\"text\" name=\"query\" value='".$context->g("query")."'/>";
    $form.="</select> <input type=\"submit\" value=\"Search\"/><br/>";
    
    $form.=" order : <input type=\"radio\" name=\"order\" value=\"desc\"";
    if ( $context->g("order")==="desc" ) $form.=" checked=\"checked\"";
    $form.=" /> from new to old, descending&nbsp;&nbsp;";  
    $form.="<input type=\"radio\" name=\"order\" value=\"asc\"";
    if ( $context->g("order")==="asc" ) $form.=" checked=\"checked\"";
    $form.=" /> from old to new, ascending";
   
    $form.="<input type=\"hidden\" name=\"act\" value=\"search\"/>";    
    $form.="<input type=\"hidden\" name=\"length\" value=\"".$context->g("length")."\"/>";  
    $form.="<input type=\"hidden\" name=\"searchLength\" value=\"".$context->g("searchLength")."\"/>";
    $form.="</p></form>";
    return ($form);
  }
  
  static function bottomAlert (PageRegistry $pageContext,$actualCount) {
    if ( $actualCount==0 && !empty($pageContext->g("query")) ) return ("Sorry, no results");
    if ( $actualCount>0 && $actualCount==$pageContext->g("searchLength") ) return ("Only ".$actualCount." results shown, there may be more. Increase the page length or change order" );
  }
  
}