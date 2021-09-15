<?php 
/**
	Author: Javohir Abdirasulov
	Email:  alienware7x@gmail.com
**/

//  For debugging only
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Access cross policy header
header('Access-Control-Allow-Origin: *');


// Database configurations
$servername = "localhost";
$username   = "DATABASE_USER";
$password   = "DATABASE_PASSWORD";
$database   = "DATABASE_NAME";

// NiceHash API tokens and endpoint url
$cfg['url_root']   = "https://api2.nicehash.com";
$cfg['org_id']     = "ORGANISATION_ID";
$cfg['api_key']    = "API_KEY";
$cfg['api_secret'] = "API_SECRET";
$cfg['algo']       = "YOUR_ALGORITHM";

// For first time download list of algorithms and save it as a file.
//$algo = file_get_contents("https://api2.nicehash.com/main/api/v2/mining/algorithms");
//file_put_contents('algo.cache', $algo);
 
$nhinfo = file_get_contents('algo.cache');
$algo   = get_algo_settings($nhinfo, $cfg['algo']);


// Creating new PDO connection to database
try {
  $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
  	echo "Connection failed: " . $e->getMessage();
}


// Search for your algorithm from algo.cache file
function get_algo_settings($raw, $a) {
	$algos = json_decode($raw, true)['miningAlgorithms'];
	foreach ($algos as $key => $algo) {
		if ($algo['algorithm'] == $a) {
			return $algo;
		}
	}
}
 
// Get 14 digit integer format NiceHash server time 
function get_time() {
	global $cfg;
	//get time
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl, CURLOPT_URL, $cfg['url_root']."/api/v2/time");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($curl);
	curl_close($curl);
	$time = json_decode($result, true)['serverTime'];
	return $time;
}


// Request current mining status
function get_info() {
	global $cfg;
	$time      = get_time();
	$nonce     = uniqid();
	$path      = "/main/api/v2/mining/algo/stats";
	$qs        = "ts=".$time."&op=LE&limit=1000&status=ACTIVE&algorithm=".$cfg['algo'];
	$signature = $cfg['api_key']."\x00".$time."\x00".$nonce."\x00"."\x00".$cfg['org_id']."\x00"."\x00"."GET"."\x00".$path."\x00".$qs;
	$signhash  = hash_hmac('sha256', $signature, $cfg['api_secret']);

	$headers = array(
		"X-Time: {$time}",
		"X-Nonce: {$nonce}",
		"X-Organization-Id: {$cfg['org_id']}",
		"X-Request-Id: {$nonce}",
		"X-Auth: {$cfg['api_key']}:{$signhash}"
	);
	$curl = curl_init();
	//curl_setopt($curl, CURLOPT_VERBOSE, true);
	curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

	curl_setopt($curl, CURLOPT_URL, $cfg['url_root'].$path."?".$qs);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	$result = curl_exec($curl);
	curl_close($curl);
	return json_decode($result, true);
}


// check if it is ajax
if($_SERVER['REQUEST_METHOD'] == "POST"){
	$sth = $conn->prepare("SELECT * FROM history ORDER BY id DESC LIMIT 20");
	$sth->execute();
	$result = $sth->fetchAll(PDO::FETCH_ASSOC);
 	echo json_encode($result);
 	exit();
}


// Load data to $result and insert it to database
$result = get_info()['algorithms'][$cfg['algo']];
	if($result['isActive']){
 	 	$conn->exec("INSERT INTO history (profit, power) VALUES ('".number_format($result['profitability'],8)."', '".$result['speedAccepted']."')");
		}

// Calculate average profitability between given time span
$row = $conn->query("SELECT AVG(profit) AS AverageProfit FROM history ORDER BY id ASC LIMIT 20")->fetch(); 
?>
<!DOCTYPE html>
<html>
	<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.5.0/Chart.min.js"></script>
	</head>
<body>
	<!-- Print current mining status if devices are active -->
	<?php if($result['isActive']){ ?>
		<h1> Profitability: <?=number_format($result['profitability'],8)?> BTC/sec</h1>
		<h2> speedAccepted: <?=$result['speedAccepted']." ".$result['displaySuffix']?></h2>
		<h3> speedRejected: <?=$result['speedRejected']?></h3>
		<h3> isActive:	    <?=$result['isActive']?></h3>
	<?php } ?>
		 <h2> Unpaid: <?=$result['unpaid']?> BTC</h2>
		 <h3> AverageProfit: <?=substr($row['AverageProfit'],0,10)?></h3>

	<div style="border: 1px solid black;height:400px;width:100%;">
		<canvas id="myChart" style="position:relative;height:40vh;width:80vw"></canvas>
	</div>
</body>
<script>
$( document ).ready(function() {
		var power  = []; 
		var profit = []; 
		var dates  = [];
	
	 // Exec ajax request for history
	 $.ajax({
        url:  "https://example.com/index.php",
        type: "POST",
                success: function (res) {
					 var response = JSON.parse(res);
					 response.sort();
					 response.reverse();
					  for(var i=0; i<response.length; i++){
						power[i]   = response[i]['power'];
						profit[i]  = response[i]['profit'];
						dates[i]   = response[i]['created_date'];
					}
		
		// Create new chartJS object and load data in it.			
		new Chart("myChart", {
			type: "line",
				data: {
					labels: dates,
					datasets: [{ 
						data: profit,
						borderColor: "red",
						 fill: false
							}]
						},
					options: {
						responsive: true,
    					maintainAspectRatio: false,
							legend: {
								display: false
							}
					   }
				 });
             }
        });
	})
</script>
