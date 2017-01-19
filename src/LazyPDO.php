<?php

namespace alejoluc\LazyPDO;

class LazyPDO extends \PDO {

    /** @var \PDO $pdo_conn */
	private $pdo_conn = null;

	private $connectionString;
	private $connectionUser;
	private $connectionPasswd;
	private $connectionOptions;

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

        register_shutdown_function([$this, 'close']);
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
            $this->pdo_conn = new parent($this->connectionString, $this->connectionUser, $this->connectionPasswd, $this->connectionOptions);
        }
	}

    /**
     * Close the connection
     */
    public function close() {
        if ($this->isConnected()) {
            $this->pdo_conn = null;
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
     * See PHP documentation of this method, since there are several definitions for this method, so there are
     * multiple ways to call it
     * @link http://php.net/manual/en/pdo.query.php
     * @return \PDOStatement
     */
    public function query($statement, $mode = parent::ATTR_DEFAULT_FETCH_MODE, $arg3 = null) {
        // I had to call the underlying function in this way because it seems there are several declarations
        // of the PDO::query() method, so calling it with one set of parameters would trigger an error, and
        // calling it in another way, another error, and so on and so on. This fixes it.
        // It would be easier to use variadic function and argument unpacking, available since PHP5.6, but
        // phpdocumentor complains (see https://github.com/phpDocumentor/phpDocumentor2/issues/1821)
        // TODO: change signature and body to a variadic function with argument unpacking when/if phpdoc fixes issue
        $args = func_get_args();
        $this->connect();
        return call_user_func_array([$this->pdo_conn, 'query'], $args);
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