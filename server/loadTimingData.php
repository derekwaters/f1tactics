<?php

	// Parse the input file and add it to a database for later use.

	require_once "database.php";

	class LoadTimingData
	{
		public $raceState;

		public function __construct ()
		{
			$this->raceState = new stdClass();
			$this->raceState->mapRowToDriverId = new stdClass();
			$this->raceState->driverInfo = new stdClass();
			$this->raceState->currentLap = 0;
			$this->raceState->timestamp = null;
		}

		public function loadDataFrom ($filename)
		{
			global $theDatabase;

			$xml = new XMLReader();
			$xml->open($filename);

			echo "Processing: ";

			while ($xml->read())
			{
				if ($xml->nodeType == XMLReader::ELEMENT)
				{
					if ($xml->name == "transaction")
					{
						$addRowData = array();

						while ($xml->moveToNextAttribute())
						{
							$addRowData[$xml->name] = $xml->value;
						}
					}
					else
					{
						while ($xml->moveToNextAttribute())
						{
							$addRowData[$xml->name] = $xml->value;
						}
					}
				}
				else if ($xml->nodeType == XMLReader::END_ELEMENT)
				{
					if ($xml->name == "transaction")
					{
						$theDatabase->addRow('trans', $addRowData);
						echo "+";
					}
				}
			}

			echo "Done!\n\n";

			$xml->close();
		}

		public function initialiseTimingData ()
		{
			global $theDatabase;

			$query = $theDatabase->query("select messagecount from trans where sessionstate = 'started'");
			foreach ($query as $row)
			{
				$startSessionTransId = $row['messagecount'];
				break;
			}
			$query = $theDatabase->query("SELECT * FROM trans WHERE identifier = '101' AND messagecount <= '" . $startSessionTransId . "' ORDER BY messagecount ASC");
			$this->applyTimingData($query);
		}

		public function processTimingData ($fromTime, $toTime)
		{
			global $theDatabase;

			$query = $theDatabase->query("SELECT * FROM trans WHERE identifier = '101' AND timestamp >= '" . $fromTime . "' AND timestamp <= '" . $toTime . "' ORDER BY messagecount ASC");
			$this->applyTimingData($query);
		}

		private function applyTimingData ($query)
		{
			global $theDatabase;

			$count = 0;

			foreach ($query as $event)
			{
				$count++;
				$this->raceState->timestamp = $event['timestamp'];

				$row = $event['row'];
				if (isset($this->raceState->mapRowToDriverId->$row))
				{
					$driverId = $this->raceState->mapRowToDriverId->$row;
					if (strlen($driverId) > 0)
					{
						$driver = $this->raceState->driverInfo->$driverId;
						$driver->racePosition = $row;

						switch ($event['column'])
						{
							case '3':
								if (!isset($driver->name))
								{
									$driver->name = $event['value'];
								}
								break;
							case '4':
								if ($event['row'] == '1')
								{
									// text should be lap
									$driver->behind = '0.0';
								}
								else
								{
									if (strlen($event['value']) > 1 && $event['value'][strlen($event['value']) - 1] == 'L')
									{
										$driver->laps_behind = substr($event['value'], 0, strlen($event['value']) - 1);
									}
									$driver->behind = $event['value'];
								}
							case '5':
								if ($event['row'] == '1')
								{
									$this->raceState->currentLap = $event['value'];

									// echo "CURRENT LAP is " . $this->currentLap . "\n";
									// text is the lap number
									$driver->gap = '0.0';
								}
								else
								{
									$driver->gap = $event['value'];
								}
								break;
							case '6':
								if ($event['value'] == 'IN PIT')
								{
									$driver->status = 'IN PIT';
								}
								else if ($event['value'] == 'OUT')
								{
									$driver->status = 'OUT';
									$driver->lastPitstop = ($this->raceState->currentLap - $driver->laps_behind - 1);
								}
								else
								{
									$driver->status = 'RACING';
									$driver->lastlap = $event['value'];
								}
								break;
							case '7':
								$driver->sector1 = $event['value'];
								break;
							case '9':
								$driver->sector2 = $event['value'];
								break;
							case '11':
								$driver->sector3 = $event['value'];
								break;
							case '13':
								$driver->pitstops = $event['value'];
								break;
						}

						$this->raceState->driverInfo->$driverId = $driver;
					}
				}
				else if ($event['column'] == '2')
				{
					$driverId = $event['value'];
					if (strlen($driverId) > 0)
					{
						$row = $event['row'];
						$this->raceState->mapRowToDriverId->$row = $driverId;
						$this->raceState->driverInfo->$driverId = new stdClass();
						$this->raceState->driverInfo->$driverId->number = $driverId;
						$this->raceState->driverInfo->$driverId->laps_behind = 0;
					}
				}
			}

			// echo "Got " . $count . " items\n";
		}

		public function dumpDriverInfo ()
		{
			printf("----|--------------------------------|----------|-----|------------|\n");
			printf("%3s | %30s | %8s | %3s | %10s |\n", "#", "Driver", "Last Lap", "Cmp", "Behind");
			printf("----|--------------------------------|----------|-----|------------|\n");
			foreach ($this->driverInfo as $driver)
			{
				printf("%3s | %30s | %8s | %3s | %10s |\n", $driver->number, $driver->name, $driver->lastlap, $this->currentLap - 1 - $driver->laps_behind, $driver->behind);
			}
			printf("----|--------------------------------|----------|-----|------------|\n");
		}
	}

	// $check = new LoadTimingData();
	// $check->loadDataFrom('F1 Race.txt');
	// $check->initialiseTimingData();
	// $check->dumpDriverInfo();
	// $blah = strtotime($timestamp);
	// var_dump($blah);

	// $check->processTimingData("14:38:25.000", "14:42:00.000");
	// $check->dumpDriverInfo();

	// $theDatabase->setupDatabase();