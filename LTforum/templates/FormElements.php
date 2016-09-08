<?php
/**
 * @pakage LTforum
 * @version 1.1.2 refactored Form classes
 */ 
/**
 * Common template for NewElements,EditElements and EditanyElements.
 * @uses  ViewRegistry $context
 */
abstract class FormElements {

  protected static function wrapRow ($str) {
    return("<tr>".$str."</tr>");
  }
  
  protected static function wrapFld ($str) {
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
  
  static function idP ($context) {
    $label="Message ID : ";
    return ( self::wrapRow ( self::wrapFld($label).self::wrapFld($context->g("id")) ) );
  }
  
  static function userP ($context) {
    $label="Your name : ";
    return ( self::wrapRow ( self::wrapFld($label).self::wrapFld($context->g("author")) ) );
  }
  
  static function authorInput ($label,$inputName,$authorName,$context,$pageContext) {
    $row=self::wrapFld( self::genericLabel($inputName,$label) );
    $row.=self::wrapFld( self::genericInput("text",$inputName,$authorName) );
    return ( self::wrapRow ($row) );
  }
  
  static function deleteChkbx () {
    $label="Delete this message ";
    $row=self::wrapFld( self::genericLabel("clear",$label) );    
    $row.=self::wrapFld( '<input type="checkbox" id="del" name="del" />' );
    return ( self::wrapRow ($row) );   
  }
  
  static function clearChkbx () {
    $label="Set current date and time ";
    $row=self::wrapFld( self::genericLabel("clear",$label) );    
    $row.=self::wrapFld('<input type="checkbox" id="clear" name="clear" />');
    return ( self::wrapRow ($row) );    
  }
  
  static function txtText ($settings,$context) {
    $maxMessageLetters=(int)( $settings->g("maxMessageBytes")/2 );
    $label='<td colspan="2"><label for="txt">Edit this text (<span id="cnt">max '.$maxMessageLetters.' letters</span>):</label></td>';
    $textarea='<td colspan="2" id="t"><textarea id="txt" name="txt" rows="" cols="" maxlength="'.$maxMessageLetters.'" >'.$context->g("message").'</textarea></td>';
    return( self::wrapRow ($label)."\r\n".$textarea );
  }
  
  static function commText ($settings,$context) {
    $maxMessageLetters=(int)($settings->g("maxMessageBytes")/2);
    $label='<td colspan="2"><label for="comm">Edit this commentary (<span id="cnt2">max '.$maxMessageLetters.' letters</span>):</label></td>';
    $textarea='<td colspan="2" id="c"><textarea id="comm" name="comm" rows="" cols="" maxlength="'.$maxMessageLetters.'" >'.$context->g("comment").'</textarea></td>';
    return( self::wrapRow ($label)."\r\n".$textarea );
  }
  
  static function snapChkbx () {
    $label="Go straight to forum after posting";
    $row=self::wrapFld( self::genericLabel("snap",$label) );    
    $row.=self::wrapFld('<input type="checkbox" id="snap" name="snap" checked="checked" />');
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