<?php
/**
 * @pakage LTforum
 * @version 0.1.3 new folders structure
 */
/**
 * Forum thread entry point
 * Defines paths (possibly thread-specific), then passes to main module (common for all threads)
 */

//echo ("I'm LTforum/demo/index.php"); 
 
$forumName="demo";// canonical forum name
$forumTitle="Just another open miniforum";// page title
$mainPath="../LTforum/";// relative to here
$templatePath="templates/"; // relative to main LTforum folder
$assetsPath="../assets/"; // relative to main LTforum folder

require_once ($mainPath."LTforum.php");

?>