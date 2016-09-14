<?php
/**
 * @pakage LTforum
 * @version 1.1 added Search command, refactored View classes
 */ 
/**
 * Common template for NewElements,EditElements and EditanyElements.
 * @uses  ViewRegistry $context
 */
abstract class FormElements {

  protected static function wrapRow ($str) {
    return("<tr>".$str."</tr>");
  }
  
  protected static function wrapFldTh ($str) {
    return("<th>".$str."</th>");
  }
  
  protected static function wrapFldTd ($str) {
    return("<td>".$str."</td>");
  }  

  protected static function genericInput ($type,$name,$value) {
    return("<input type=\"".$type."\" id=\"".$name."\" name=\"".$name."\" value=\"".$value."\" />");
  }
  
  protected static function genericLabel ($name,$labelText) {
    return("<label for=\"".$name."\">".$labelText."</label>");
  }
  
  abstract static function titleSuffix (ViewRegistry $context);
  
  abstract static function hiddenFields ($pageContext);
  
  static function idP (ViewRegistry $context) {
    $label="Message ID : ";
    return ( self::wrapRow ( self::wrapFldTh($label).self::wrapFldTd($context->g("id")) ) );
  }
  
  static function userP (ViewRegistry $context) {
    $label="Your name : ";
    return ( self::wrapRow ( self::wrapFldTh($label).self::wrapFldTd($context->g("author")) ) );
  }
  
  static function authorInput ($label,$inputName,$authorName,$context,$pageContext) {
    $row=self::wrapFldTh( self::genericLabel($inputName,$label) );
    $row.=self::wrapFldTd( self::genericInput("text",$inputName,$authorName) );
    return ( self::wrapRow ($row) );
  }
  
  static function deleteChkbx () {
    $label="Delete this message ";
    $row=self::wrapFldTh( self::genericLabel("del",$label) );    
    $row.=self::wrapFldTd( '<input type="checkbox" id="del" name="del" />' );
    return ( self::wrapRow ($row) );   
  }
  
  static function clearChkbx () {
    $label="Set current date and time ";
    $row=self::wrapFldTh( self::genericLabel("clear",$label) );    
    $row.=self::wrapFldTd('<input type="checkbox" id="clear" name="clear" />');
    return ( self::wrapRow ($row) );    
  }
  
  static function txtText (SessionRegistry $settings,ViewRegistry $context,$labelText="Edit this text") {
    $maxMessageLetters=(int)( $settings->g("maxMessageBytes")/2 );
    $label='<th colspan="2"><label for="txt">'.$labelText.' (<span id="cnt">max '.$maxMessageLetters.' letters</span>):</label></th>';
    $textarea='<td colspan="2" id="t"><textarea id="txt" name="txt" rows="" cols="" maxlength="'.$maxMessageLetters.'" >'.$context->g("message").'</textarea></td>';
    return( self::wrapRow ($label)."\r\n".self::wrapRow  ($textarea)  );
  }
  
  static function commText (SessionRegistry $settings,ViewRegistry $context) {
    $maxMessageLetters=(int)($settings->g("maxMessageBytes")/2);
    $label='<th colspan="2"><label for="comm">Edit this commentary (<span id="cnt2">max '.$maxMessageLetters.' letters</span>):</label></th>';
    $textarea='<td colspan="2" id="c"><textarea id="comm" name="comm" rows="" cols="" maxlength="'.$maxMessageLetters.'" >'.$context->g("comment").'</textarea></td>';
    return( self::wrapRow ($label)."\r\n".self::wrapRow  ($textarea) );
  }
  
  static function snapChkbx () {
    $label="Go straight to forum after posting";
    $row=self::wrapFldTh( self::genericLabel("snap",$label) );    
    $row.=self::wrapFldTd('<input type="checkbox" id="snap" name="snap" checked="checked" />');
    return ( self::wrapRow ($row) );     
  }

  protected static function wrapJs ($str) {
    return ("\r\n".'<script type="text/javascript">'."\r\n".$str."\r\n".'</script>'."\r\n");
  }
  
  static function script1 () {
    $s='function counter (ofield,ocounter,maxL) {
          var l=ofield.value.length;
          ocounter.style.color= l>maxL ? "#f11" : "";
          ocounter.innerHTML=" "+l+" letters, max "+maxL;
          return(false);
        }';
    return( self::wrapJs($s) );
  }
  
  static function script2 () {
    $s="var txt=document.getElementById('txt');
        var maxTxt=(txt.getAttribute('maxlength')||600);
        var cnt=document.getElementById('cnt');
        txt.onchange=txt.onmouseout=function () {counter(txt,cnt,maxTxt);};";
    return( self::wrapJs($s) );
  }
  
  static function script3 () {
    $s="var comm=document.getElementById('comm');
        var maxTxt=(txt.getAttribute('maxlength')||600);
        var cnt2=document.getElementById('cnt2');
        comm.onchange=comm.onmouseout=function () {counter(comm,cnt2,maxTxt);}";  
    return( self::wrapJs($s) );
  }  
} 