<?php

namespace alejoluc\LazyPDO;

class LazyPDO {

	private $pdo_conn = null;

	private $connectionString;
	private $connectionUser;
	private $connectionPasswd;
	private $connectionOptions;

	private $exceptionHandler;

	public function __construct($connectionString, $user = null, $passwd = null, array $connectionOptions = []) {
		$this->pdo_conn = null;

		$this->connectionString  = $connectionString;
		$this->connectionUser    = $user;
		$this->connectionPasswd  = $passwd;
		$this->connectionOptions = $connectionOptions;

        register_shutdown_function([$this, 'close']);
	}

	public function __get($key) {
		return $this->pdo_conn->$key;
	}

	public function __set($key, $val) {
		return $this->pdo_conn->$key = $val;
	}

	public function __call($methodName, $arguments) {
		if (!$this->isConnected()) {
			$this->connect();
		}
        //return call_user_func_array([$this->pdo_conn, $methodName], $arguments); // For PHP < 5.6.0
        return $this->pdo_conn->$methodName(...$arguments);
	}

	public function isConnected() {
		return $this->pdo_conn !== null;
	}

	public function connect() {
		try {
			$this->pdo_conn = new \PDO($this->connectionString, $this->connectionUser, $this->connectionPasswd, $this->connectionOptions);
		} catch (\PDOException $e) {
			var_dump('LazyPDO received PDOException: ', $e->getMessage()); // TODO: Usar el handler
		}
	}

    public function close() {
        if ($this->isConnected()) {
            $this->pdo_conn = null;
        }
    }

}