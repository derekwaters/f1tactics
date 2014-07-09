<?php

	require_once('loadTimingData.php');


	function sortByLastLapTime ($left, $right)
	{
		if (strlen($left->lastlap) > 0)
		{
			if (strlen($right->lastlap) > 0)
			{
				return strcasecmp($left->lastlap, $right->lastlap);
			}
			else
			{
				return -1;
			}
		}
		else
		{
			return 1;
		}
	}


	$timing = new LoadTimingData();

	header("Content-Type: text/plain");

	$currentState = null;
	if (isset($_POST['currentState']))
	{
		$currentState = json_decode($_POST['currentState']);
		$timing->driverInfo = $currentState->driverInfo;
		// $timing->processTimingData($currentState->timestamp, somevalue);
		uasort($currentState->driverInfo, 'sortByLastLapTime');
	}
	else
	{
		$currentState = new stdClass();
		$currentState->timestamp = $timing->initialiseTimingData();
		$currentState->driverInfo = $timing->driverInfo;
	}
	$currentState->driverInfo = array_values($currentState->driverInfo);


	$response = fopen('php://output', 'w');
	fputs($response, json_encode($currentState));
	fclose($response);
