<?php
/**
 * @pakage LTforum
 * @version 1.1 search command
 */

/**
 * Controller upper part: Initialization and Command resolver.
 * Commands are in Act.php
 */

require_once ($mainPath."CardfileSqlt.php");
require_once ($mainPath."AssocArrayWrapper.php");
require_once ($mainPath."Act.php");
require_once ($mainPath."MyExceptions.php");

// Classes and function, which have not found their own files

 
class PageRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;// private causes access error
    
    public function load() {
      $inputKeys=array("act","begin","end","length","user","txt","comm","snap","del","query","searchLength","order");
      foreach ($inputKeys as $k) {
        if ( array_key_exists($k,$_REQUEST) ) $this->s($k,$_REQUEST[$k]);
        else $this->s($k,"");
      }
      if (array_key_exists('PHP_AUTH_USER',$_SERVER) ) $this->s("user",$_SERVER['PHP_AUTH_USER']);
      //else $this->s("user","Creator");
    }
}
 
class SessionRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
}

class ViewRegistry extends SingletAssocArrayWrapper {
    protected static $me=null;
}

// MAIN

//echo ("\r\nI'm LTforum/LTforum/LTforum.php");

// instantiate and initialize Page Registry and Session Registry
$sr=SessionRegistry::getInstance( 2, array( "lang"=>"en", "viewDefaultLength"=>20, "viewOverlay"=>1, "toPrintOutcome"=>1,"mainPath"=>$mainPath, "templatePath"=>$templatePath, "assetsPath"=>$assetsPath, "maxMessageBytes"=>"1200", "forum"=>$forumName)
);

$pr=PageRegistry::getInstance( 0,array() );
$pr->load();
//$pr->s("forum",$forumName); // $forumName comes from index.php
if ($forumTitle) $pr->s("title",$forumTitle);
else $pr->s("title","LTforum::".$forumName);
$pr->s( "viewLink",Act::addToQueryString($pr,"","length","user") );//

try {
  $pr->s("cardfile",new CardfileSqlt($forumName,true));
}
catch (Exception $e) {
  Act::showAlert ($pr,$sr,$e->getMessage()); 
}

// some testing code
//$messages=new CardfileSqlt( $pr->g("forum"), true);

//$firstMsg=$pr->g("cardfile")->getOneMsg(1);
//print_r ($firstMsg);
/*for ($j=2;$j<=100;$j++) {
  $m = Act::makeMsg("Creator","This is message number ".$j);
  $pr->g("cardfile")->addMsg($m);
}*/
//$pr->g("cardfile")->deletePackMsg(1,15);
/*$search=[" ак ","http","-IT"];//,"dvd"];"AR",
$order="";//"desc";
$limit=10;
$results="";
$toShow=$pr->g("cardfile")->yieldSearchResults($search,$order,$limit);
foreach($toShow as $i=>$res) {
  $results.="<hr />".implode(":",$res)."<hr />";
  //$results.= ("!".strpos(implode("  ",$res),$search[1]) );
  //if(strpos(implode("  ",$res),$search[1])===false) print ("<!");
}
print($results);
exit();*/

if ($pr->g("act")=="search") {
  $q=$pr->g("query");
  if ( strlen($q)<2 ) $pr->s("alert","Please, enter the search string");
  if ( strpos($q," ")>0 && strpos($q," ")<strlen($q) ) $pr->s("alert","Use \"foo&bar\" to find messages containing both \"foo\" and \"bar\"<br/>Use \"foo bar\" to find messages containing  \"foo[SPACE]bar\"");
  if ( strpos($q,"&")>0 && strpos($q,"&")<strlen($q) ) $andTerms=explode("&",$q);
  else $andTerms=[$q];
  $lim=$pr->g("searchLength");
  if ( empty($lim) ) $lim=$pr->g("length");
  $toShow=$pr->g("cardfile")->yieldSearchResults($andTerms,$pr->g("order"),$lim);
  if ( count($toShow) ) print("!!".count($toShow));

      $vr=ViewRegistry::getInstance( true, array( "controlsClass"=>"SearchElements", "query"=>$pr->g("query"), "order"=>$pr->g("order"), "searchLength"=>$pr->g("searchLength"), "length"=>$pr->g("length"), "msgGenerator"=>$toShow
      ) );
      //$vr->s("no_such_key",0);// check catch UsageException

      //$vr->dump();
      
      require ($sr->g("templatePath")."SearchElements.php");
      include ($sr->g("templatePath")."roll.php");
      exit(0);  
}

$action=$pr->g("act");
switch ($action) {
  case "new":
    Act::newMessage($pr,$sr);
    break;
  case "el":
    Act::editLast($pr,$sr);
    break;
  case "upd":
    Act::updateLast($pr,$sr);
    break;
  case "add":
    Act::add($pr,$sr);
    break;
  default:
    Act::view($pr,$sr);
}
  
?>