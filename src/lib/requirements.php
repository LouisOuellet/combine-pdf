<?php
if (version_compare(PHP_VERSION, '5.6.0') < 0) {
  trigger_error('Your PHP version must be equal or higher than 5.6.0 to use this Application.' . PHP_EOL, E_USER_ERROR);
}
if (!extension_loaded('intl')) {
  trigger_error('You must enable the intl extension to use this Application.' . PHP_EOL, E_USER_ERROR);
}
if (!extension_loaded('mbstring')) {
  trigger_error('You must enable the mbstring extension to use this Application.' . PHP_EOL, E_USER_ERROR);
}
if (!extension_loaded('imap')) {
  trigger_error('You must enable the imap extension to use this Application.' . PHP_EOL, E_USER_ERROR);
}
if (!extension_loaded('dom')) {
  trigger_error('You must enable the dom extension to use this Application.' . PHP_EOL, E_USER_ERROR);
}
