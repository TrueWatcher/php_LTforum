<?php
/**
 * @pakage LTforum
 * @version 1.2 added Access Controller and User Manager
 */

/**
 * Frontend Controller, the upper part of.
 * Initializations, AccessController call and command processor call.
 * Commands are in Act.php
 */

require_once ($mainPath."AssocArrayWrapper.php");
require_once ($mainPath."Registries.php");
require_once ($mainPath."CardfileSqlt.php");
require_once ($mainPath."Act.php");
require_once ($mainPath."MyExceptions.php");
require_once ($mainPath."AccessHelper.php");
require_once ($mainPath."AccessController.php");
require_once ($mainPath."Applicant.php");

//echo ("\r\nI'm LTforum/LTforum/LTforum.php");

// minimal initializations
$ivf=SessionRegistry::initVectorForFrontend($mainPath,$templatePath,$assetsPath,$forumName);
$sr=SessionRegistry::getInstance(2,$ivf);

// here goes the Access Controller
//$ivf=AuthRegistry::initVectorForFrontend($forumName,$templatePath,$assetsPath);
$ivf=AuthRegistry::initVector($forumName, "", $templatePath, $assetsPath, 0);
$ar=AuthRegistry::getInstance(1,$ivf);
$ac=new AccessController($ar);
$acRet=$ac->go();
//echo("Trace:".$ar->g("trace")."\n");
//echo( "Alert:".$ar->g("alert")."\n" );
if($acRet!==true) exit($acRet);

// more initializations using database
$pr=PageRegistry::getInstance( 0, [] );
$pr->initAllAfterAuth(null, null, $sr, "Act", $forumTitle, $forumName);

if ( $pr->queryStringIsEmpty() )  Act::redirectToView($pr);

Act::go($pr,$sr);

?>