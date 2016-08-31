<?php
/**
 * @pakage LTforum
 * @version 1.0 experimental deployment
 */
/**
 * My exception for file access errors.
 */
class AccessException extends Exception {}
/**
 * My exception for unsupported/forbidden client operations.
 */
class UsageException extends Exception {}
/**
 * My exception for exception in normal operations, like border situations.
 */
class OperationalException extends Exception {}
 
?>