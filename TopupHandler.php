<?php
require_once("TopupConstants.php");
		
class TopupHandler {
	protected $apiUrl = 'http://127.0.0.1/legacyApi';

	protected $apiUsername = 'codeuser';
	
	protected $apiPassword = 'codepass';
	
	protected $apiMaxExecutionTime = 0;	

	protected $smsApiUrl = 'https://sms.example.com';

	/* 
	 * Third party API curl call for topup API and send response
	 * 
	 * @param string $apiName API action name 
	 * @param string $apiParametersString Query string sending to API call
	 *
	 * @return XML response of API
	 */
	protected function callApi($apiName, $apiParametersString) {
		ini_set('max_execution_time', $this->apiMaxExecutionTime); // prevents 30 seconds
		$ch = curl_init($this->apiUrl.'/?action='.$apiName.'&'.$apiParametersString);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		curl_setopt($ch, CURLOPT_USERPWD, $this->apiUsername . ":" . $this->apiPassword);
		
		$resp = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    if (in_array($httpCode, array(408, 500, 404))) {
	       throw new \Exception(BROKEN_API);
	    }
		curl_close($ch);
		return $resp;
	}

	/* 
	 * Third party API call and handling response for getBalance API
	 * 
	 * @param number $simNumber SIM number for get balance
	 *
	 * @return array response of getBalance API
	 */
	public function getBalance($simNumber) {
		getBalanceApiCall:
		try {
			$apiResponse = $this->callApi('getBalance', 'number='.$simNumber);
		}
		catch(\Exception $e) {
			return array('error' => true, 'text' => $e->getMessage());
		}
		$response = $this->parseXMLtoArray($apiResponse);

		// Recall API if Belarusian error
		if ($response['text'] == UNKNOWN_BELARUSIAN) {
			//echo here something, in order to check this error comes and again we are start with calling "getBalanceAPI" from top of this function
			goto getBalanceApiCall;
		}
		return $response;
	}

	/* 
	 * Third party API call and handling response for addBalance API
	 * Handling all possible test-cases, do appropriate notification or error
	 * 
	 * @param number $simNumber SIM number for get balance
	 * @param string $currency Currency short name
	 * @param float $amount Amount for add balance
	 *
	 * @return array response of addBalance API
	 */
	public function addBalance($simNumber, $currency, $amount) {
		getBalanceApiCall:
		try {
			$apiResponse = $this->callApi('getBalance', 'number='.$simNumber);
			$rawData = $this->parseXMLtoArray($apiResponse);
		}
		catch(\Exception $e) {
			return array('error' => true, 'text' => $e->getMessage());
		}
		
		// Multiple test-cases for checking into SIM
		if(isset($rawData['type']) && $rawData['type'] === 'ERROR')  {
			$response = $rawData;

			// Recall API if Belarusian error
			if ($response['text'] == UNKNOWN_BELARUSIAN) {
				//echo here something, in order to check this error comes and again we are start with calling "getBalanceAPI" from top of this function
				goto getBalanceApiCall;
			}
			if ($response['text'] == SYNTAX_ERROR) { //Send notifications on syntax error
				$this->sendEmailNotification(SYNTAX_ERROR); //Send email
				$this->sendSMSNotification();
			}
		}
		else {
			$simData = $rawData['card'];
			$formatter = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
			$currentBalance = $formatter->parse($simData['balance']);

			// Case-1: Cards can be blocked
			if ($simData['blocked'] == 'true') {
				$response = array('type' => 'ERROR', 'text' => CARD_IS_BLOCKED);
			}
			// Case-2: Currency mismatch
			else if (mb_strtolower($simData['curr']) != mb_strtolower($currency)) {
				$response = array('type' => 'ERROR', 'text' => CURRENCY_MISMATCH);
			}
			// Case-3: Negetive Balance
			else if ($currentBalance < 0 && abs($currentBalance) >= 5) {
				$response = array('type' => 'ERROR', 'text' => NEGETIVE_BALANCE);
			}
			else {
				//In case of Negetive balance, do full recharge same as topup amount
				//else add new amount in original balance.
				$newBalanceAmount = ($currentBalance < 0) ? $amount : ($currentBalance + $amount);

				addBalanceApiCall:
				try {
					$apiResponse = $this->callApi('addBalance', 'number='.$simNumber.'&currency='.$currency.'&amount='.$newBalanceAmount);

					$response = $this->parseXMLtoArray($apiResponse);
				}
				catch(\Exception $e) {
					return array('type' => 'ERROR', 'text' => $e->getMessage());
				}
				// Recall API if Belarusian error
				if ($response['text'] == UNKNOWN_BELARUSIAN) {
					//echo here something, in order to check this error comes and again we are start with calling "getBalanceAPI" from top of this function
					goto addBalanceApiCall;
				}
			}
		}
		return $response;
	}

	/* 
	 * Third party API call and handling response for getBalance API
	 * 
	 * @param string $xmlString XML response for legacy API
	 *
	 * @return array conversation of XML parameters/tags
	 */
	public function parseXMLtoArray($xmlString) {
		$xml = simplexml_load_string($xmlString);
		$json = json_encode($xml);
		$array = json_decode($json,TRUE);

		return $array;
	}
	
	/* 
	 * Send an SMS notification API call
	 *
	 * @return array API response
	 */
	public function sendSMSNotification() {
		ini_set('max_execution_time', $this->apiMaxExecutionTime); // prevents 30 seconds
		$ch = curl_init($this->smsApiUrl . "/?text=last topup is failed due to wrong data submitted on topup form, please check and fill all parameterrs correctly and try again!");

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);		
		$resp = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    if (in_array($httpCode, array(408, 500, 404))) {
	       throw new \Exception(BROKEN_API);
	    }
		curl_close($ch);
		return array('error' => false, 'text' => 'OK');
	}

	/* 
	 * Send an email notification specific to error
	 *
	 * @param string $errorType Specific error type based on which email will be send 
	 *
	 * @return array API response
	 */
	public function sendEmailNotification($errorType) {
		switch ($errorType) {
			case SYNTAX_ERROR:				
				$toEmail = EMAIL_NOTIFICATION_TO_USERS;
				$subject = SYNTAX_ERROR_EMAIL_SUBJECT;
				$replyTo = REPLY_TO_EMAIL;
				
				$message = 'Hi team, <br>';
				$message .= 'We found there\'s wrong usage of topup functionality from unknown customer. <br>';
				$message .= 'This email is regarding to inform you about it. its auto generated email to hightlight you that, customer is not aware of using our topup feature. and so entered wrong information for topup. <br>';
				$message .= 'Please reach them and either teach them how to use functionality or support them to do manual topup by your system. <br>';
				$message .= 'Regards, <br /> Topup development team';
				break;
			// More email notification case can be handle here, if needed in future
		}
		$headers = "From: " .$replyTo. "\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

		return mail($toEmail, $subject, $message, $headers); //send mail
	}
}
?>