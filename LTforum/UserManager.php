<?php

  class UserManager {
  
  function addUser($userName,$realm,$password) {
    $name=@$_SESSION["authName"];
    if (!$name) throw new AccessException ("Cannot find name of the current user");
    /*$name=@$context->g("authName");
    if (!$name) $name=@$context->g("user");
    if (!$name) throw new AccessException ("Cannot find name of the current user");*/
    if ( !isAdmin($name) ) fail("You should be an Admin to do that");
    $nl="\n";
    $groupFile=".ini";
    $buf=file_get_contents($groupFile);
    $buf=str_replace("\r","",$buf);
    $beginSection=strpos($buf,"[".$realm."]");
    if ($beginSection===false) throw new AccessException ("Section ".$realm." not found in the file ".$groupFile);
    $ha=makeHa1($userName,$realm,$password);
    $entry=$userName."=".$ha.$nl;
    if (strpos($buf,$entry,$beginSection)!==false) fail("This entry already exists");
    $head=substr($buf,0,$beginSection+strlen($realm)+3);
    $tail=substr($buf,$beginSection+strlen($realm)+3);
    $buf=$head.$entry.$tail;
    file_put_contents($groupFile,$buf);
  }
  
  function delUser($userName,$realm,$password) {
    $name=@$_SESSION["authName"];
    if (!$name) throw new AccessException ("Cannot find name of the current user");
    if ( !isAdmin($name) ) fail("You should be an Admin to do that");
    if ( $userName==$name ) fail("You should not delete your own record"); 
    $nl="\n";
    $groupFile=".ini";
    $buf=file_get_contents($groupFile);
    $buf=str_replace("\r","",$buf);
    $section=strpos($buf,"[".$realm."]")-1;
    if ($section===false) throw new AccessException ("Section ".$realm." not found in the file ".$groupFile);
    $ha=makeHa1($userName,$realm,$password);
    $entry=$userName."=".$ha.$nl;
    $where=strpos($buf,$entry,$section);
    if($where===false) return ("Missing or invalid entry. Try manual editing");
    $after=@substr($buf,$where+strlen($entry),1);
    $before=@substr($buf,$where-2,1);
    echo(" before:".$before."; after:".$after."; ");
    if( ($before=="]" || $before===false) && ($after=="[" || $after===false ) ) return ("This seems to be the only record in that section. Create another one first");
    $head=substr($buf,0,$where);
    $tail=substr($buf,$where+strlen($entry));
    $buf=$head.$tail;
    file_put_contents($groupFile,$buf);
    return(true);
  }
  }
?>