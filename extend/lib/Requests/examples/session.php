<?php

// First, include Requests
include('../library/Requests.php');

// Next, make sure Requests can load internal classes
Requests::register_autoloader();

// Set up our session
$session = new Requests_Session('http://api.bacui.dev');
$session->headers['Accept'] = 'application/json';
$session->useragent = 'Awesomesauce';

// Now let's make a request!
$request = $session->get('/Index/Index/index');

// Check what we received
print_r($request->cookies['PHPSESSID']->value);

// Let's check our user agent!
//$request = $session->get('/user-agent');

// And check again
//var_dump($request);
