<?php

namespace Aphreton;

class DatabaseConnection {
	
    private $pdo;
    private string $dsn;
	private string $user;
	private string $password;

    public function __construct(string $dsn, string $user, string $password) {
        $this->dsn = $dsn;
		$this->user = $user;
		$this->password = $password;
    }

    public function query($sql, $params = null) {
		$result = [];
        if (is_null($this->pdo)) {
            $this->pdo = new \PDO($this->dsn, $this->user, $this->password);
			$this->pdo->setAttribute( \PDO::ATTR_EMULATE_PREPARES , false );
			$this->pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		}
		try {
			$stmt = $this->pdo->prepare($sql); 
			$stmt->execute($params);
			$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		} catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}
		return $result;
    }
	
	public function __destruct() {
		$this->pdo = null;
	}
}