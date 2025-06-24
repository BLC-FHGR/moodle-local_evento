<?php  // tests/bootstrap.php
// Tell PHP‑Curl + OpenSSL exactly where the Debian bundle is
ini_set('curl.cainfo',  '/etc/ssl/certs/ca-certificates.crt');
ini_set('openssl.cafile','/etc/ssl/certs/ca-certificates.crt');

// Load Moodle's testing framework
require_once(__DIR__ . '/../../../lib/phpunit/bootstrap.php');

// No vendor/autoload.php required since we're using Moodle's autoloader