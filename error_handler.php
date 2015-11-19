<?php

function myErrorHandler($errno, $errstr, $errfile, $errline) {
  if ( E_RECOVERABLE_ERROR===$errno ) {
    echo "'catched' catchable fatal error\n";
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
    // return true;
  }
  return false;
}
set_error_handler('myErrorHandler');