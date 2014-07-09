<?php

	// Parse the input file and add it to a database for later use.

	require_once "database.php";

	class LoadTimingData
	{
		public $mapRowToDriverId = array();
		public $driverInfo = array();
		public $currentLap = 0;

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
			$query = $theDatabase->query("SELECT * FROM trans WHERE identifier = '101' AND messagecount <= '" . $startSessionTransId . "'");
			return $this->applyTimingData($query);
		}

		public function processTimingData ($fromTime, $toTime)
		{
			global $theDatabase;

			$query = $theDatabase->query("SELECT * FROM trans WHERE identifier = '101' AND timestamp >= '" . $fromTime . "' AND timestamp <= '" . $toTime . "'");
			$this->applyTimingData($query);
		}

		private function applyTimingData ($query)
		{
			global $theDatabase;

			$count = 0;
			$lastTimestamp = null;

			foreach ($query as $event)
			{
				$count++;
				$lastTimestamp = $event['timestamp'];

				if (isset($this->mapRowToDriverId[$event['row']]))
				{
					$driverId = $this->mapRowToDriverId[$event['row']];
					if (strlen($driverId) > 0)
					{
						switch ($event['column'])
						{
							case '3':
								$this->driverInfo[$driverId]['name'] = $event['value'];
								break;
							case '4':
								if ($event['row'] == '1')
								{
									// text should be lap
									$this->driverInfo[$driverId]['behind'] = '0.0';
								}
								else
								{
									if (strlen($event['value']) > 1 && $event['value'][strlen($event['value']) - 1] == 'L')
									{
										$this->driverInfo[$driverId]['laps_behind'] = substr($event['value'], 0, strlen($event['value']) - 1);
									}
									$this->driverInfo[$driverId]['behind'] = $event['value'];
								}
							case '5':
								if ($event['row'] == '1')
								{
									$this->currentLap = $event['value'];

									// echo "CURRENT LAP is " . $this->currentLap . "\n";
									// text is the lap number
									$this->driverInfp[$driverId]['gap'] = '0.0';
								}
								else
								{
									$this->driverInfo[$driverId]['gap'] = $event['value'];
								}
								break;
							case '6':
								if ($event['value'] == 'IN PIT')
								{
									$this->driverInfo[$driverId]['status'] = 'IN PIT';
								}
								else if ($event['value'] == 'OUT')
								{
									$this->driverInfo[$driverId]['status'] = 'OUT';
								}
								else
								{
									$this->driverInfo[$driverId]['status'] = 'RACING';
									$this->driverInfo[$driverId]['lastlap'] = $event['value'];
								}
								break;
							case '7':
								$this->driverInfo[$driverId]['sector1'] = $event['value'];
								break;
							case '9':
								$this->driverInfo[$driverId]['sector2'] = $event['value'];
								break;
							case '11':
								$this->driverInfo[$driverId]['sector3'] = $event['value'];
								break;
							case '13':
								$this->driverInfo[$driverId]['pitstops'] = $event['value'];
								break;
						}
					}
				}
				else if ($event['column'] == '2')
				{
					$driverId = $event['value'];
					if (strlen($driverId) > 0)
					{
						$this->mapRowToDriverId[$event['row']] = $driverId;
						$this->driverInfo[$driverId] = array();
						$this->driverInfo[$driverId]['number'] = $driverId;
						$this->driverInfo[$driverId]['laps_behind'] = 0;
					}
				}
			}

			// echo "Got " . $count . " items\n";

			return $lastTimestamp;
		}

		public function dumpDriverInfo ()
		{
			printf("----|--------------------------------|----------|-----|------------|\n");
			printf("%3s | %30s | %8s | %3s | %10s |\n", "#", "Driver", "Last Lap", "Cmp", "Behind");
			printf("----|--------------------------------|----------|-----|------------|\n");
			foreach ($this->driverInfo as $driver)
			{
				printf("%3s | %30s | %8s | %3s | %10s |\n", $driver['number'], $driver['name'], $driver['lastlap'], $this->currentLap - 1 - $driver['laps_behind'], $driver['behind']);
			}
			printf("----|--------------------------------|----------|-----|------------|\n");
		}
	}

	// $check = new LoadTimingData();
	// $check->loadDataFrom('F1 Race.txt');
	// $check->initialiseTimingData();
	// $check->dumpDriverInfo();

	// $check->processTimingData("14:38:25.000", "14:42:00.000");
	// $check->dumpDriverInfo();

	// $theDatabase->setupDatabase();