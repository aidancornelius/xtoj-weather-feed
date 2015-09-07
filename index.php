<?php

// Really simple weather data (xml) to JSON array parser for (regional) Australian Bureau of Meterology weather data 
// Could easily be adapted to handle other XML files on the anonymous FTP (and probably on the authenticated FTP $$$)

require_once 'vendor/autoload.php';

header("Content-Type: Application/JSON");

$router = new AltoRouter();

$router->map( 'GET', '/', function() {
    echo json_encode("No source selected. Tail this url with a lowercase Australian capital city. (ex: adelaide.json)", 128);
});

$router->map( 'GET', '/[:location].json', function ($location) {
	$locationsArray = array(
		"adelaide" => "ftp://ftp2.bom.gov.au/anon/gen/fwo/IDS65176.xml",
		"canberra" => "ftp://ftp2.bom.gov.au/anon/gen/fwo/IDN65176.xml",
		"sydney" => "ftp://ftp2.bom.gov.au/anon/gen/fwo/IDN65176.xml",
		"melbourne" => "ftp://ftp2.bom.gov.au/anon/gen/fwo/IDV65176.xml",
		"brisbane" => "ftp://ftp2.bom.gov.au/anon/gen/fwo/IDQ60604.xml",
		"perth" => "ftp://ftp2.bom.gov.au/anon/gen/fwo/IDW65176.xml",
		"hobart" => "ftp://ftp2.bom.gov.au/anon/gen/fwo/IDT65176.xml",
		"darwin" => "ftp://ftp2.bom.gov.au/anon/gen/fwo/IDD65176.xml",	
	);
	
	if ($locationsArray[$location]) {
		$dataSource = file_get_contents($locationsArray[$location]);
		if ($dataSource) {
			$xml = simplexml_load_string($dataSource);
			if ($xml) {
				$json = json_encode($xml, 128);
				
				if ($json) {
					$CleanIn = json_decode($json, true);
					$CleanOut = array();
					$count = 0;
					foreach ($CleanIn[product][group][obs] as $observation) {
						$stationName = $observation["@attributes"]["station"];
						$station =  array(
							// Static titles, could be dynamically updated, but I'm lazy.
							"Time updated"						=> $observation["@attributes"]["obs-time-local"],
							"Maximum temperature"				=> floatval($observation[d][0]),
							"Minimum temperature"				=> floatval($observation[d][1]),
							"Terrestrial minimum temperature"	=> floatval($observation[d][2]),
							"Wetbulb depression"				=> floatval($observation[d][3]),
							"Rain"								=> floatval($observation[d][4]),
							"Evaporation"						=> floatval($observation[d][5]),
							"Wind run"							=> floatval($observation[d][6]),
							"Sunshine"							=> floatval($observation[d][7]),
							"Solar radiation"					=> floatval($observation[d][8]),
							"5cm temperature"					=> floatval($observation[d][9]),
						);
						array_push($CleanOut, array("$stationName" => $station));
						$count++;
					}
					array_push($CleanOut, array("Total Stations" => $count));
					echo json_encode($CleanOut, 128);
				} else {
					echo json_encode(array("data invalid"=>$locationsArray[$location]), 128);
				}
			} else {
				echo json_encode(array("data invalid"=>$locationsArray[$location]), 128);
			}
		} else {
			echo json_encode(array("data not available"=>$locationsArray[$location]), 128);	
		}
	} else {
		echo json_encode(array("location not found"=>$location), 128);
	}
});

$match = $router->match();

if( $match && is_callable( $match['target'] ) ) {
	call_user_func_array( $match['target'], $match['params'] ); 
} else {
	// no route was matched
	header( $_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
}

?>