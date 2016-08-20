<?php
/**
 * @pakage LTforum
 * @version 0.1.5 added this file
 */
 
/**
 * HTML tags filter ( function mask_tags ) and its satellites.
 * @author TrueWatcher 2011-2016
 */
 
//looks for ">" after seek position, without "<"
function gt_present($start,$in_str) {
  $j=strpos($in_str,'>',$start+1);
  if ($j==0)return (FALSE);
  $k=strpos($in_str,'<',$start+1);
  if ($k===FALSE || $k>$j) return (TRUE);
  return (FALSE);
}

//returns TRUE if found "</TAG>" after starting position
//$next_lt -- position of next "<" or the end of the string
function closing_tag_present ($start,$closing,$in_str,&$next_lt) {
  //print("closed_with_".$closing."?");
  $next_lt=strpos($in_str,'<',$start+1);
  if ($next_lt===FALSE) {
    $next_lt=strlen($in_str);
    return (FALSE);
  }
  $j=strpos($in_str,$closing,$start+1);
  if ($next_lt==$j) return (TRUE);
  return (FALSE);
}

//checks for allowed tags from a list
//maybe an empty tag, maybe an open tag
//returns FALSE if not found or invalid, length otherwise
//if $closing is non-zero, it forms the closing tag "</TAG>" to be checked later
function valid_tag($seek,$in_str,$allowed,&$closing) {
  $i=count($allowed)-1;
  $found=FALSE;
  $terminates="";
  //print($i);
  //look for an allowed tag
  while ($i>=0 && !$found) {
    $l=strlen($allowed[$i]);
    $sub_i=substr($in_str,$seek+1,$l);
    //print("<br />testing:".$sub_i."_against:".$allowed[$i].";");
    if (strcmp($sub_i,$allowed[$i])===0) {
      $terminates=substr($sub_i,$l-1,1);
      if ($closing) {
      	 if($terminates==" ") $closing='</'.substr($sub_i,0,$l-1).'>'; 
         else $closing='</'.$sub_i.'>';
      }
      //print("\ntag_found:".$sub_i.";");
      //print("OK,match:".$closing."_ends with:".$terminates."*");
      $found=TRUE;
    }
    $i--;
  }//end while
  if (!$found) return (FALSE);
  //check for ">"
  //$closed_no_args=$closed_w_args=FALSE;
  $closed_no_args=($terminates!=' ' && substr($in_str,$seek+$l+1,1)=='>' );
  $closed_w_args=($terminates==' ' && gt_present($seek+$l,$in_str) );
  if ($closed_no_args || $closed_w_args) return ($l);
  else return (FALSE);
}//end function valid_tag

//finally replacing "<" with "&lt;" according to a list of positions
function mask_lt ($in_str,$cuts) {
  $lc=count($cuts);
  if($lc==0) return ($in_str);
  $ret="";
  $prev_end=0;
  for ($i=0; $i<$lc; $i++) {
    $l=$cuts[$i]-$prev_end;
    $ret.=substr($in_str,$prev_end,$l)."&lt;";
    //print("<br />".$i.":".substr($in_str,$prev_end,$l)."&lt;");
    $prev_end=$cuts[$i]+1;
  }
  $ret.=substr($in_str,$prev_end);
  return($ret);
}//end function mask_lt

//changes "<TAG>" to "&lt;TAG>" exept for specially listed tags
//
//example: print ("\n".mask_tags ($input, array("[s]","[/s]","[i]","[/i]","[b]","[/b]"), array("br","br ","br/"), array("center","em","del","s","i","b","a ") ) );
function mask_tags($in_str,$allowed_bbcode,$allowed_empty,$allowed_markup) {
  $cuts=array();
  
  if (strlen($in_str)==0) return(FALSE);
  
  if ((count($allowed_bbcode)+count($allowed_empty)+count($allowed_markup))==0) {
    return (str_replace("<","&lt;",$in_str));
  }
  
  //replacing "<SPACE" with "&lt;SPACE"
  $in_str=str_replace("< ","&lt; ",$in_str);
  
  //converting allowed BBCode tags to HTML
  $bbc2html=array_fill(0,count($allowed_bbcode),"");
  foreach($allowed_bbcode as $i=>$al_bc){
    $bbc2html[$i]=str_replace(array('[',']'),array('<','>'),$al_bc);
  }
  //print_r($bbc2html);
  $in_str=str_replace($allowed_bbcode,$bbc2html,$in_str);
  
  $cuts_count=0;
  $pos_seek=strpos($in_str,'<');
  while (!($pos_seek===FALSE)) {
    $valid_empty=$valid_markup=FALSE;
    $closing="";
    $l=valid_tag($pos_seek,$in_str,$allowed_empty,$closing);
    if ($l>0) {$valid_empty=TRUE;}
    else {//check against another list
      $closing="closing";
      $matched=FALSE;
      $ll=valid_tag($pos_seek,$in_str,$allowed_markup,$closing);
      if ($ll>0) {
        $valid_markup=TRUE;
        $matched=closing_tag_present($pos_seek+$ll,$closing,$in_str,$next_lt);
        if ($matched) {
          //print("closed!");
          $pos_seek=$next_lt+1;
        }
        else {//insert the closing tag before next "<"
           //print("inserting".$closing);
           $in_str=substr($in_str,0,$next_lt).$closing.substr($in_str,$next_lt);
           $pos_seek=$next_lt+strlen($closing)-1;
        }//end else
      }//end if ll
    }//end else
    if (!($valid_empty || $valid_markup)) {
        //invalid tag
        //print("-invalid_at_".$pos_seek);
        $cuts[$cuts_count]=$pos_seek;
        $cuts_count++;
    }//end if valid
  $pos_seek=strpos($in_str,'<',$pos_seek+1);
  }//end while 
  
  //print_r ($cuts);
  return( mask_lt($in_str,$cuts) );
}//end mask_tags
?>