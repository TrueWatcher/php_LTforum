<?php
/**
 * @pakage LTforum
 * @version 1.2 added SessionManager and UserManager
 */

/**
 * Admin panel for managing messages and users in one thread/group
 * @uses PageRegistry $pr
 * @uses SessionRegistry $sr
 */

  //print("Hi, I am admin.php");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="ru" xml:lang="ru">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?php print( $pr->g("title") ); ?></title>
  <link rel="stylesheet" type="text/css" href="<?php print($sr->g("assetsPath")."talk.css"); ?>" media="all" />
</head>
<body>
<h1><?php print($pr->g("forum")); ?></h1>
<p><?php printf("messages: %s..%s, language: %s",$pr->g("forumBegin"),$pr->g("forumEnd"),$pr->g("forumLang") ); ?></p>
<hr />
<p><?php print($pr->g("alert")); ?></p>

<form action="" method="get" id="export">
  <fieldset>
    Export to html<br />
    begin : <input type="text" name="begin" />,<br />
    end : <input type="text" name="end" /> or max size (x1000B) : <input type="text" name="kb" /><br />
    new IDs start from (blank to discard IDs, * to copy from forum) :
    <input type="text" name="newBegin" /><br />
    target file: <input type="text" name="obj" />.html<br />
    <input type="hidden" name="act" value="exp" />
    <input type="hidden" name="forum" value="<?php print( $pr->g("forum") ) ?>" />
    <input type="submit" value="Export" />
  </fieldset>
</form>

<form action="" method="get" id="import">
  <fieldset>
    Import from html<br />
    file : <input type="text" name="obj" />.html<br />
    order : <input type="radio" name="order" value="desc" /> from new to old, descending&nbsp;&nbsp;
    <input type="radio" name="order" value="asc" checked="checked" /> from old to new, ascending<br />
    Imported messages will be added on top of the present with fresh IDs.<br />
    <input type="hidden" name="act" value="imp" />
    <input type="hidden" name="forum" value="<?php print( $pr->g("forum") ) ?>" />
    <input type="submit" value="Import" />
  </fieldset>
</form>

<form action="" method="get" id="delRange">
  <fieldset>
    Delete a message block near begin or end of the forum<br />
    from : <input type="text" name="begin" /> to : <input type="text" name="end" value="<?php print( $pr->g("end") ) ?>" /><br />
    <input type="hidden" name="act" value="dr" />
    <input type="hidden" name="forum" value="<?php print( $pr->g("forum") ) ?>" />
    <input type="submit" value="Delete" />
  </fieldset>
</form>

<form action="" method="get" id="editAny">
  <fieldset>
    Edit any message<br />
    id : <input type="text" name="current" /><br />
    <input type="hidden" name="act" value="ea" />
    <input type="hidden" name="forum" value="<?php print( $pr->g("forum") ) ?>" />
    <input type="submit" value="Edit" />
  </fieldset>
</form>

<hr />

<div>
  <fieldset>
    All users: <a href="?forum=<?php print( $pr->g("forum") ); ?>&amp;act=lu"><button type="button">Get users list</button></a><br />
    <input type="textarea" style="width:100%" id="userList" value="<?php print($vr->g("userList")); ?>" />
    <br />
    All admins: <a href="?forum=<?php print( $pr->g("forum") ); ?>&amp;act=la"><button type="button">Get admins list</button></a><br />
    <input type="textarea" style="width:100%" id="adminList" value="<?php print($vr->g("adminList")); ?>" />
  </fieldset>
</div>

<script src="<?php print($sr->g("assetsPath")."authHelper.js"); ?>"></script>
<form action="" method="post" id="manUser">
  <fieldset>
    User:<br />
    Name : <input type="text" name="user" id="user" value="Turn on JS" />
    Password : <input type="text" name="ps" id="ps" value="Turn on JS" />
    <br />
    <button type="button" id="genEntry">Generate Entry</button>
    <br />
    <input type="textarea" style="width:100%" name="uEntry" id="uEntry" />
    <br />
    <button type="button" id="uAdd">Add</button>
    <button type="button" id="uDel">Remove</button>
    <input type="hidden" name="forum" value="<?php print( $pr->g("forum") ) ?>" />
    <input type="hidden" name="realm" id="realm" value="<?php print( $pr->g("forum") ) ?>" />
  </fieldset>
</form>
<form action="" method="post" id="manAdmin">
  <fieldset>
    User:<br />
    Name : <input type="text" name="aUser" id="aUser" value="Turn on JS" />
    <br />
    <button type="button" id="aAdd">Add to Admins</button>
    <button type="button" id="aDel">Remove from Admins</button>
    <input type="hidden" name="forum" value="<?php print( $pr->g("forum") ) ?>" />
  </fieldset>
</form>
<script>
  $("user").value=$("ps").value="";
  function genEntry() {
    if ( checkEmpty("user") || checkEmpty("ps") ) {
      return false;
    }
    var ha=makeHa();
    $("uEntry").value=$("user").value+"="+ha;
    return true;
  }
  $("genEntry").onclick=function(){ genEntry(); };
  function clearPrivate() {
    //$("user").value=$("ps").value=$("realm").value="";
    remove("user");
    remove("ps");
    remove("realm");
  }
  $("uAdd").onclick=function() {
    if ( !genEntry() ) return (false);
    addHidden("act","uAdd","manUser");
    clearPrivate("manUser");
    $("manUser").submit();
  };
  $("uDel").onclick=function() {
    if ( !genEntry() ) return (false);
    addHidden("act","uDel","manUser");
    clearPrivate("manUser");
    $("manUser").submit();
  };
  $("aUser").value="";
  $("aAdd").onclick=function() {
    addHidden("act","aAdd","manAdmin");
    $("manAdmin").submit();
  };
  $("aDel").onclick=function() {
    addHidden("act","aDel","manAdmin");
    $("manAdmin").submit();
  };
</script>

<fieldset>
  <a href="?forum=<?php print( $pr->g("forum") ); ?>&amp;reg=reset"><button type="button">Log out</button></a>
  <?php //echo("HttpHost:".$_SERVER['HTTP_HOST']." ,ServerName:".$_SERVER['SERVER_NAME']); ?>
</fieldset>

<p id="footer"></p>
<!--<table class="low"><tr>
  <td><?php
  if( !empty($pr->g("formLink")) )
  print ( "<a href=\"{$pr->g("formLink")}\">Try again</a>" );
  ?></td>
  <td><?php
  if( !empty($pr->g("viewLink")) )
  print ( "<a href=\"{$pr->g("viewLink")}\">Go read messages</a>" );
  ?></td>
</tr></table>-->
</body>
</html>