<?php
/**
 * @pakage LTforum
 * @version 1.0 experimental deployment
 */
 
/**
 * Form to edit existing record.
 * @uses PageRegistry $pr
 * @uses SessionRegistry $sr
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru" xml:lang="ru">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php print( $pr->g("title")." : edit message ".$pr->g("end") ); ?></title>
  <link rel="stylesheet" type="text/css" href="<?php print($sr->g("assetsPath")."form_t.css") ?>" media="all" />
</head>
<body>
<form action="?act=upd" method="post" >
  <fieldset>
    <input type="hidden" name="act" value="upd" />
    <input type="hidden" name="end" value="<?php print ($pr->g("end")); ?>" />
    <input type="hidden" name="user" value="<?php print ($pr->g("user")); ?>" />
    <input type="hidden" name="length" value="<?php print ($pr->g("length")); ?>" /> 
    <legend>Edit message</legend>
    <table>
      <tr>
        <th>Message id: </th>
        <td><?php print ($pr->g("end")); ?></td>
      </tr>    
      <tr>
        <th>Your name: </th>
        <td><?php print ($pr->g("user")); ?></td>
      </tr>
      <tr>
        <th><label for="del">Delete this message</label></th>
        <td><input type="checkbox" id="del" name="del" /></td>
      </tr>      
      <tr>
        <th colspan="2"><label for="txt">Edit this text (<span id="cnt">max <?php $maxMessageLetters=(int)($sr->g("maxMessageBytes")/2); print($maxMessageLetters); ?> letters</span>):</label></th>
      </tr>
      <tr>
        <td colspan="2" id="t"><textarea id="txt" name="txt" rows="" cols="" maxlength="<?php print($maxMessageLetters); ?>" ><?php print( $pr->g("txt") ); ?></textarea></td>
      </tr>
      <tr>
        <th colspan="2"><label for="txt">Edit this comment (<span id="cnt2">max <?php $maxMessageLetters=(int)($sr->g("maxMessageBytes")/2); print($maxMessageLetters); ?> letters</span>):</label></th>
      </tr>
      <tr>
        <td colspan="2" id="c"><textarea id="comm" name="comm" rows="" cols="" maxlength="<?php print($maxMessageLetters); ?>" ><?php print( $pr->g("comm") ); ?></textarea></td>
      </tr>      
      <tr>
        <th><label for="snap">View forum after posting</label></th>
        <td><input type="checkbox" id="snap" name="snap" checked="checked" /></td>
      </tr>
    </table>
    <table><tr>
        <td id="enter"><input class="submit" type="submit" value="Submit" /></td>
    </tr></table>
  </fieldset>
</form>
<script type="text/javascript">
function counter (ofield,ocounter,maxL) {
  var l=ofield.value.length;
  ocounter.style.color= l>maxL ? "#f11" : "";
  ocounter.innerHTML=" "+l+" letters, max "+maxL;
  return(false);
}
var txt=document.getElementById('txt');
var maxTxt=(txt.getAttribute('maxlength')||600);
var cnt=document.getElementById('cnt');
txt.onchange=txt.onmouseout=function () {counter(txt,cnt,maxTxt);};
var comm=document.getElementById('comm');
var maxTxt=(txt.getAttribute('maxlength')||600);
var cnt2=document.getElementById('cnt2');
comm.onchange=comm.onmouseout=function () {counter(comm,cnt2,maxTxt);};
</script>
</body>
</html>
