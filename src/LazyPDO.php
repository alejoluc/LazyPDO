<?php

namespace alejoluc\LazyPDO;

class LazyPDO extends \PDO {

    /** @var \PDO $pdo_conn */
	private $pdo_conn = null;

	private $connectionString;
	private $connectionUser;
	private $connectionPasswd;
	private $connectionOptions;

    private $onConnectionErrorCallback  = null;
    private $onConnectCallback          = null;
    private $onCloseCallback            = null;

    /**
     * @link http://php.net/manual/en/pdo.construct.php
     * @param $connectionString
     * @param null $user
     * @param null $passwd
     * @param array $connectionOptions
     * @return LazyPDO
     */
	public function __construct($connectionString, $user = null, $passwd = null, array $connectionOptions = []) {
		$this->pdo_conn = null;

		$this->connectionString  = $connectionString;
		$this->connectionUser    = $user;
		$this->connectionPasswd  = $passwd;
		$this->connectionOptions = $connectionOptions;

        /** @var callable onConnectionErrorCallback */
        $this->onConnectionErrorCallback = function($e){
            // By default, bubble up the exception
            throw $e;
        };
        /** @var callable onConnectCallback */
        $this->onConnectCallback = function(){};
        /** @var callable onCloseCallback */
        $this->onCloseCallback   = function(){};

        register_shutdown_function([$this, 'close']);
	}

    /**
     * Sets a function to be called if there is an error when trying to establish a connection with the underlying PDO object
     * @param callable $callback
     */
    public function onConnectionError(callable $callback) {
        $this->onConnectionErrorCallback = $callback;
    }

    /**
     * Sets a function to be called after the underlying PDO object succesfully establishes a connection
     * @param callable $callback
     */
	public function onConnectionOpen(callable $callback) {
	    $this->onConnectCallback = $callback;
    }

    /**
     * Sets a function to be called after the underlying PDO object is set to null, closing the connection.
     * @param callable $callback
     */
    public function onConnectionClose(callable $callback) {
        $this->onCloseCallback = $callback;
    }

    /**
     * @return bool
     */
	public function isConnected() {
		return $this->pdo_conn !== null;
	}

    /**
     * Connect, if the connection has not been established already
     */
	public function connect() {
	    if (!$this->isConnected()) {
            try {
                $this->pdo_conn = new parent($this->connectionString, $this->connectionUser, $this->connectionPasswd, $this->connectionOptions);
                $callback = $this->onConnectCallback;
                $callback($this->pdo_conn);
            } catch (\PDOException $e) {
                $callback = $this->onConnectionErrorCallback;
                $callback($e);
            }
        }
	}

    /**
     * Close the connection
     */
    public function close() {
        if ($this->isConnected()) {
            $this->pdo_conn = null;
            $callback = $this->onCloseCallback;
            $callback();
        }
    }

    /* Override parent functions to make them lazy */

    /**
     * @link http://php.net/manual/en/pdo.begintransaction.php
     * @return bool
     */
    public function beginTransaction() {
        $this->connect();
        return $this->pdo_conn->beginTransaction();
    }

    /**
     * @link http://php.net/manual/en/pdo.commit.php
     * @return bool
     */
    public function commit() {
        $this->connect();
        return $this->pdo_conn->commit();
    }

    /**
     * @link http://php.net/manual/en/pdo.rollback.php
     * @return bool
     */
    public function rollBack() {
        $this->connect();
        return $this->pdo_conn->rollBack();
    }

    /**
     * @link http://php.net/manual/en/pdo.intransaction.php
     * @return bool
     */
    public function inTransaction() {
        $this->connect();
        return $this->pdo_conn->inTransaction();
    }

    /**
     * @link http://php.net/manual/en/pdo.errorcode.php
     * @return mixed
     */
    public function errorCode() {
        $this->connect();
        return $this->pdo_conn->errorCode();
    }

    /**
     * @link http://php.net/manual/en/pdo.errorinfo.php
     * @return array
     */
    public function errorInfo() {
        $this->connect();
        return $this->pdo_conn->errorInfo();
    }

    /**
     * @link http://php.net/manual/en/pdo.exec.php
     * @param string $statement
     * @return int
     */
    public function exec($statement) {
        $this->connect();
        return $this->pdo_conn->exec($statement);
    }

    /**
     * @link http://php.net/manual/en/pdo.getattribute.php
     * @param int $attribute
     * @return mixed
     */
    public function getAttribute($attribute) {
        $this->connect();
        return $this->pdo_conn->getAttribute($attribute);
    }

    /**
     * @link http://php.net/manual/en/pdo.setattribute.php
     * @param int $attribute
     * @param mixed $value
     * @return bool
     */
    public function setAttribute($attribute, $value) {
        $this->connect();
        return $this->pdo_conn->setAttribute($attribute, $value);
    }

    /**
     * @link http://php.net/manual/en/pdo.getavailabledrivers.php
     * @return array
     */
    public static function getAvailableDrivers() {
        return parent::getAvailableDrivers();
    }

    /**
     * @link http://php.net/manual/en/pdo.lastinsertid.php
     * @param null $name
     * @return string
     */
    public function lastInsertId($name = null) {
        $this->connect();
        return $this->pdo_conn->lastInsertId($name);
    }

    /**
     * @link http://php.net/manual/en/pdo.prepare.php
     * @param string $statement
     * @param array $driver_options
     * @return \PDOStatement
     */
    public function prepare($statement, $driver_options = null) {
        $this->connect();
        if (!is_array($driver_options)) {
            $driver_options = [];
        }
        return $this->pdo_conn->prepare($statement, $driver_options);
    }

    /**
     * @link http://php.net/manual/en/pdo.query.php
     * @return \PDOStatement
     */
    public function query($statement, $mode = parent::ATTR_DEFAULT_FETCH_MODE, $arg3 = null) {
        /* I don't use the arguments as passed directly, but instead I get the arguments as an
        array and unpack it. This is because there seems to be several implementations of the
        query() method in native PDO, and calling it with the arguments as passed to this function
        almost always returns an error. This fixes it. */
        $this->connect();
        $args = func_get_args();
        return $this->pdo_conn->query(...$args);
    }

    /**
     * @link http://php.net/manual/en/pdo.quote.php
     * @param string $string
     * @param int $parameter_type
     * @return string
     */
    public function quote($string, $parameter_type = parent::PARAM_STR) {
        $this->connect();
        return $this->pdo_conn->quote($string, $parameter_type);
    }

}