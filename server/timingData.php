<?php

	require_once('loadTimingData.php');

	$timing = new LoadTimingData();

	header("Content-Type: text/plain");

	$currentState = null;
	if (isset($_POST['currentState']))
	{
		$currentState = json_decode($_POST['currentState']);

		$timing->raceState = $currentState;
		$startTime = strtotime($currentState->timestamp);
		$endTime = $startTime + (1 * 20);	// Add 20 seconds
		$endTimeStamp = date("H:i:s.000", $endTime);
		$timing->processTimingData($currentState->timestamp, $endTimeStamp);
		$currentState = $timing->raceState;
		$currentState->timestamp = $endTimeStamp;
	}
	else
	{
		$timing->initialiseTimingData();
		$currentState = $timing->raceState;
	}
	$response = fopen('php://output', 'w');
	fputs($response, json_encode($currentState));
	fclose($response);
