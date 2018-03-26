INTRODUCTION
-------------
Toup functionality is responsible to do topup and get existing balance of user SIM.Its implement Legacy API (You may find sample legacy API at https://github.com/javiya-rupal/legacyApi ).

Based on it, either its do topup on user SIM or get and display balance of SIM or send notification in case of any problem in accessing functionality.

REQUIREMENTS
-------------
PHP Version >= 5.6 with below extension and modules enabled.

php_curl <br />
php_intl<br />
mod_rewrite<br />

Note: enable php_intl for command line too for unit test.

INSTALLATION
-------------
Same like other PHP app installation, just copy paste the folder into your public directory

CONFIGURATION
-------------
- We have used legacy API for topup, for same. make sure that you set below configurations.
in TopupHandler class of TopupHandler.php file,<br /><br />
 $apiUrl - Set this variable as base path of your legacy API<br />
 $apiUsername - Set username for legacy API (for http authentication)<br />
 $apiPassword - Set password for legacy API (for http authentication)<br />
 $apiMaxExecutionTime - Currently set to unlimited, this is for setting CURL request timeout<br />
 $smsApiUrl - Set and API url for sending notification SMS to end user.<br /><br />

TESTING
--------
Below are few SIM cards number for test this functionality and implemented/used in our legacy API statically

Blocked SIM:
02071838750

Balance >= -5 SIM:
4155552671

Balance < -5 SIM:
8885739922

Regular SIM with Positive amount and not blocked:
37250123123,
8132471896

------------------

(1) '37250123123' => ['number' => '37250123123', 'blocked' => 'false', 'balance' => '72.00', 'curr' => 'USD'],

(2) '8132471896' => ['number' => '8132471896', 'blocked' => 'false', 'balance' => '32.00', 'curr' => 'EUR'],

(3) '02071838750' => ['number' => '02071838750', 'blocked' => 'true', 'balance' => '20.00', 'curr' => 'EUR'],

(4) '4155552671' => ['number' => '4155552671', 'blocked' => 'false', 'balance' => '-4.99', 'curr' => 'USD'],

(5) '8885739922' => ['number' => '8885739922', 'blocked' => 'false', 'balance' => '-10.00', 'curr' => 'USD']

You may find unit testing also in "tests" folder which covers unit test of TopupHandlerMethod "getBalance" and "addBalance".

Also please note, unit test only cover testing of our "TopupHandler" class methods only. its assume that LegacyApi will working correctly with our assumed data as response.

FAQ
---
For any question or support write to me on rupal.javiya@gmail.com