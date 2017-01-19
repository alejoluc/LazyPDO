<?php

namespace alejoluc\LazyPDO;

class LazyPDO extends \PDO {

    /** @var \PDO $pdo_conn */
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

	public function isConnected() {
		return $this->pdo_conn !== null;
	}

	public function connect() {
	    if (!$this->isConnected()) {
            $this->pdo_conn = new parent($this->connectionString, $this->connectionUser, $this->connectionPasswd, $this->connectionOptions);
        }
	}

    public function close() {
        if ($this->isConnected()) {
            $this->pdo_conn = null;
        }
    }

    public function beginTransaction() {
        $this->connect();
        return $this->pdo_conn->beginTransaction();
    }

    public function commit() {
        $this->connect();
        return $this->pdo_conn->commit();
    }

    public function rollBack() {
        $this->connect();
        return $this->pdo_conn->rollBack();
    }

    public function inTransaction() {
        $this->connect();
        return $this->pdo_conn->inTransaction();
    }

    public function errorCode() {
        $this->connect();
        return $this->pdo_conn->errorCode();
    }

    public function errorInfo() {
        $this->connect();
        return $this->pdo_conn->errorInfo();
    }

    public function exec($statement) {
        $this->connect();
        return $this->pdo_conn->exec($statement);
    }

    public function getAttribute($attribute) {
        $this->connect();
        return $this->pdo_conn->getAttribute($attribute);
    }

    public function setAttribute($attribute, $value) {
        $this->connect();
        return $this->pdo_conn->setAttribute($attribute, $value);
    }

    public static function getAvailableDrivers() {
        return parent::getAvailableDrivers();
    }

    public function lastInsertId($name = null) {
        $this->connect();
        return $this->pdo_conn->lastInsertId($name);
    }

    public function prepare($statement, $options = null) {
        $this->connect();
        return $this->pdo_conn->prepare($statement, $options);
    }

    public function query(...$args) {
        $this->connect();
        return $this->pdo_conn->query(...$args);
    }

    public function quote($string, $parameter_type = parent::PARAM_STR) {
        $this->connect();
        return $this->pdo_conn->quote($string, $parameter_type);
    }

}