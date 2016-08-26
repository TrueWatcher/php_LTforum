<?php
/**
 * @pakage LTforum
 * @version 0.2.0 (functional viewAddEditDelete) refactored and cleaned
 */
/**
 * Forum thread entry point
 * Defines paths (possibly thread-specific), then passes to main module (common for all threads)
 */

//echo ("I'm LTforum/demo/index.php"); 
 
$forumName="test";// canonical forum name
$forumTitle="Dedicated tests thread";// page title
$mainPath="../LTforum/";// relative to here
$templatePath="templates/"; // relative to main LTforum folder
$assetsPath="../assets/"; // relative to main LTforum folder

require_once ($mainPath."LTforum.php");

?>