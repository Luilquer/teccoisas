<?php

namespace Hcode\DB;

class Sql
{

	const HOSTNAME = "127.0.0.1";
	const USERNAME = "root";
	const PASSWORD = "";
	const DBNAME = "db_teccoisas";
	//a senha é outra, tem que verificar depois 
	// const DBNAME = "db_teccoisas";
	//Senha para logar: 123456
	//user: admin

	private $conn;

	public function __construct()
	{

		$this->conn = new \PDO(
			"mysql:dbname=" . Sql::DBNAME . ";host=" . Sql::HOSTNAME,
			Sql::USERNAME,
			Sql::PASSWORD
		);
	}

	private function setParams($statement, $parameters = array())
	{

		foreach ($parameters as $key => $value) {

			$this->bindParam($statement, $key, $value);
		}
	}

	private function bindParam($statement, $key, $value)
	{

		$statement->bindParam($key, $value);
	}

	public function query($rawQuery, $params = array())
	{

		$stmt = $this->conn->prepare($rawQuery);

		$this->setParams($stmt, $params);

		$stmt->execute();
	}

	public function select($rawQuery, $params = array()): array
	{

		$stmt = $this->conn->prepare($rawQuery);

		$this->setParams($stmt, $params);

		$stmt->execute();

		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}
}
