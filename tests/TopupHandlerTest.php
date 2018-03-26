<?php
use PHPUnit\Framework\TestCase;

require_once "TopupHandler.php";
require_once "TopupConstants.php";

class TopupHandlerTest extends TestCase  {

	/**
     * @expectExceptionMessage BROKEN_API
     */
	public function testThrowsExceptionWhenLegacyApiIsBroken() {
	    $http = $this->getMockBuilder('TopupHandler')
                     ->setMethods(['callApi'])
                     ->getMock();
	    $http->expects($this->any())
	         ->method('callApi')
	         ->will($this->returnValue('404'));
	}

	public function testGetBalanceCardNotFound() {
		$http = $this->getMockBuilder('TopupHandler')
					    ->setMethods(['callApi'])
					    ->getMock();

	    $http->expects($this->once())
	         ->method('callApi')
	         ->will($this->returnValue(file_get_contents("data/cardNotFoundData.xml")));

		$result = $http->getBalance('8475893759834');
		$this->assertArraySubset(['type' => 'ERROR', 'text' => CARD_NOT_FOUND], $result);
	}

    public function testGetBalanceOk()
    {
		$http = $this->getMockBuilder('TopupHandler')
					    ->setMethods(['callApi'])
					    ->getMock();

	    $http->expects($this->once())
	         ->method('callApi')
	         ->will($this->returnValue(file_get_contents("data/correctSimData.xml")));

		$result = $http->getBalance('8132471896');

		$this->assertArraySubset(array(
					    'card' => array
					        (
					            'number' => '8132471896',
					            'blocked' => 'false',
					            'balance' => '32.00',
					            'curr' => 'EUR'
					        )

	                	), $result);
    }

    public function testAddBalanceCardIsBlocked() {
		$http = $this->getMockBuilder('TopupHandler')
					    ->setMethods(['callApi'])
					    ->getMock();

	    $http->expects($this->once())
	         ->method('callApi')
	         ->will($this->returnValue(file_get_contents("data/blockedCardData.xml")));

		$result = $http->addBalance('02071838750', 'EUR', '5.00');
		$this->assertArraySubset(['type' => 'ERROR', 'text' => CARD_IS_BLOCKED], $result);
	}

	public function testAddBalanceCardwithNegetiveBalance() {
		$http = $this->getMockBuilder('TopupHandler')
					    ->setMethods(['callApi'])
					    ->getMock();

	    $http->expects($this->once())
	         ->method('callApi')
	         ->will($this->returnValue(file_get_contents("data/negetiveBalanceData.xml")));

		$result = $http->addBalance('8885739922', 'USD', '5.00');
		$this->assertArraySubset(['type' => 'ERROR', 'text' => NEGETIVE_BALANCE], $result);
	}

	public function testAddBalanceCurrencyMismatch() {
		$http = $this->getMockBuilder('TopupHandler')
					    ->setMethods(['callApi'])
					    ->getMock();

	    $http->expects($this->once())
	         ->method('callApi')
	         ->will($this->returnValue(file_get_contents("data/correctSimData.xml")));

		$result = $http->addBalance('8132471896', 'USD', '5.00');
		$this->assertArraySubset(['type' => 'ERROR', 'text' => CURRENCY_MISMATCH], $result);
	}

	public function testAddBalanceOk() {
		$http = $this->getMockBuilder('TopupHandler')
					    ->setMethods(['callApi'])
					    ->getMock();

	    $http->expects($this->any())
	         ->method('callApi')
	         ->with(file_get_contents("data/correctSimData.xml"))
	         ->will($this->returnCallback(array($this, "checkedBalanceCallback")));
	}
	function checkedBalanceCallback($http) {
		$result = $http->addBalance('8132471896', 'EUR', '5.00');
		$this->assertArraySubset(['result' => 'OK'], $result);
	}
}
?>