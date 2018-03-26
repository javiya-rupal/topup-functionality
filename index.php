<?php 
require_once("TopupHandler.php");

//Handle an action on submit form
$result = '';
if (!empty($_REQUEST['submit'])) {	
	$action = $_GET['action'];
	$simNumber = $_GET['number'];
	$currency = isset($_GET['curr']) ? $_GET['curr']:'';
	$amount = isset($_GET['amt']) ? $_GET['amt']:'';

	switch($action) {
		case "getBalance":
			$topupHandler = new TopupHandler();
			$result = $topupHandler->getBalance($simNumber);
			break;

		case "addBalance":
			$topupHandler = new TopupHandler();
			$result = $topupHandler->addBalance($simNumber, $currency, $amount);
			break;
	}
}
?>
<html>
	<title>Topup Sample App</title>
	<body>
		<form action="" method="get">
		<table>
			<tr>
				<td>SimCard Number:</td>
				<td><input type="text" name="number" value="<?php echo $_GET['number']?>"></td>
			</tr>
			<tr>
				<td></td>
				<td>
					<input type="hidden" name="action" value="getBalance">
					<input type="submit" name="submit" value="Get Balance">
				</td>
			</tr>
		</table>
		</form>

		<form action="" method="get">
		<table>
			<tr>
				<td>SimCard Number:</td>
				<td><input type="text" name="number" value="<?php echo $_GET['number']?>"></td>
			</tr>
			<tr>
				<td>Currency:</td>
				<td><input type="text" name="curr" value="<?php echo $_GET['curr']?>"></td>
			</tr>
			<tr>
				<td>Amount:</td>
				<td><input type="text" name="amt" value="<?php echo $_GET['amt']?>"></td>
			</tr>
			<tr>
				<td></td>
				<td>
					<input type="hidden" name="action" value="addBalance">
					<input type="submit" name="submit" value="Add Balance">
				</td>
			</tr>
		</table>
		</form>
		<?php 
		if (!empty($result)) {
			?>
			<div>
				<?php if (!empty($result['type'])) {?>
					<span style="color: red;">Error: <?php echo $result['text']; ?></span>
				<?php  } 
					else { 
					echo '<pre>';
					print_r($result);
					echo '</pre>';
				 } ?>
			</div>
		<?php } ?>
	</body>
</html>