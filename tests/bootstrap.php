<?php  // tests/bootstrap.php
// Tell PHP‑Curl + OpenSSL exactly where the Debian bundle is
ini_set('curl.cainfo',  '/etc/ssl/certs/ca-certificates.crt');
ini_set('openssl.cafile','/etc/ssl/certs/ca-certificates.crt');

require_once(__DIR__ . '/../../../lib/phpunit/bootstrap.php'); 
require_once __DIR__.'/../vendor/autoload.php';

\VCR\VCR::configure()
    ->setCassettePath(__DIR__.'/fixtures/cassettes')
    ->enableRequestMatchers(['method', 'url', 'host'])
    ->setStorage('yaml');

if (getenv('VCR_MODE') === 'record') {
    \VCR\VCR::configure()->setCurlOptions([
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
}    
