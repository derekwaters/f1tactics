<?php

	// Parse the input file and add it to a database for later use.

	require_once "definitions.php";

	class Database
	{
		private $connection;

		function __construct ()
		{
		}

		public function addRow ($toTable, $rowData)
		{
			$this->connectToDatabase();

			$columns = array();
			$values = array();
			$valueStr = array();
			foreach ($rowData as $key => $value)
			{
				$columns[] = $key;
				$values[] = $value;
				$valueStr[] = "?";
			}

			$sql = "INSERT INTO " . $toTable . ' (' . implode(', ', $columns) . ') values (';
			$sql .= implode(', ', $valueStr) . ')';
			$statement = $this->connection->prepare($sql);
			if ($statement)
			{
				for ($i = 0; $i < count($values); $i++)
				{
					$statement->bindParam($i + 1, $values[$i]);
				}
				$statement->execute();
			}
			else
			{
				echo "\nFAILED TO ADD ROW:\n";
				var_dump($rowData);
			}
		}

		public function query ($sql)
		{
			$this->connectToDatabase();
			return $this->connection->query($sql);
		}

		public function setupDatabase ()
		{
			$this->connectToDatabase();
			$checkRet = $this->connection->exec("CREATE TABLE trans (identifier INTEGER, messagecount INTEGER, timestamp VARCHAR(20), column INTEGER, row INTEGER, colour VARCHAR(20), value VARCHAR(200), clock VARCHAR(20), informationvalid VARCHAR(20), sessionstate VARCHAR(20))");
			var_dump($checkRet);
			var_dump($this->connection->errorInfo());
		}

		private function connectToDatabase ()
		{
			if ($this->connection == null)
			{
				$this->connection = new PDO(Definition::DATABASE_DSN, Definition::DATABASE_USER, Definition::DATABASE_PASSWORD);
			}
		}
	}

	$theDatabase = new Database();
