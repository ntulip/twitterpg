<?php

require_once(dirname(__FILE__) . '/../conf/twitterpg.conf.php');

class DataAccess
{
	private static $instance;
	private $db_link = null;

	public static function getInstance() 
	{
		if (!isset(self::$instance)) {
			$object = __CLASS__;
			self::$instance = new $object;
		}
		return self::$instance;
	}
	
	public function __destruct()
	{
		$this->disconnect();
	}
	
	public function connect()
	{
		$link = mysql_connect(DAL_HOST, DAL_USERNAME, DAL_PASSWORD);
		if ($link === false) {
			return false;
		}
		$this->db_link = $link;
		return mysql_select_db(DAL_DATABASE, $this->db_link);
	}
	
	public function disconnect()
	{
		if ($db_link !== null) {
			mysql_close($this->db_link);
		}
	}
	
	public function getError()
	{
		return mysql_error($this->db_link);
	}
	
	public function getErrorNumber()
	{
		return mysql_errno($this->db_link);
	}
	
	public function getInsertID()
	{
		if ($this->checkLink() === false) {return false;}
		return mysql_insert_id($this->db_link);
	}
	
	public function execute($query)
	{
		if ($this->checkLink() === false) {return false;}
		$params = null;
		if (func_num_args() > 1) {
			$params = func_get_args();
			array_shift($params);
		}
		$query = $this->prepareQuery($query, $params);
		$result = mysql_query($query, $this->db_link);
		if ($result === false) {
			return false;
		}
		return true;
	}
	
	public function fetch($query)
	{
		if ($this->checkLink() === false) {return false;}
		$params = null;
		if (func_num_args() > 1) {
			$params = func_get_args();
			array_shift($params);
		}
		$query = $this->prepareQuery($query, $params);
		$result = mysql_query($query, $this->db_link);
		if ($result === false) {
			return false;
		}
		$recordset = array();
		while ($row = mysql_fetch_assoc($result)) {
			$recordset[] = $row;
		}
		mysql_free_result($result);
		return $recordset;
	}
	
	// ### PRIVATE FUNCTIONS ###
	
	private function __construct() {}
	
	/**
	 * Checks if a database connection is active; if not runs the connect() function
	 * Should be called before each public function which uses $this->db_link
	 */
	private function checkLink()
	{
		if ($this->db_link === null) {
			return $this->connect();
		}
		return true;
	}
	
	/**
	 * Prepares a query for execution by:
	 * 		1. Running mysql_real_escape_string() on each parameter (no injection attacks please!)
	 *		2. Quoting any non-numeric parameters
	 *		3. Replacing parameter tokens with their corresponding value (tokens are "$<parameter_index>")
	 *
	 * For example:
	 *		$this->prepareQuery('select * from users where id = $1 and name = $2', 150, 'geoff');
	 *
	 * Would return:
	 *		"select * from users where id = 150 and name = 'geoff'"
	 */
	private function prepareQuery($query, $params)
	{
		if (!empty($params)) {
			for ($v = 0; $v < count($params); $v++) {
				if ($params[$v] === null) {
					$params[$v] = 'null';
				} else {
					$params[$v] = mysql_real_escape_string($params[$v], $this->db_link);
					if (!is_numeric($params[$v])) { // quote the value if non numeric
						$params[$v] = "'".$params[$v]."'";
					}
				}
				$query = str_replace('$'.($v + 1), $params[$v], $query);
			}
		}
		return $query;
	}	
}

class DataObject
{
	protected $dataAccess = null;
	
	public function __construct()
	{
		$this->dataAccess = DataAccess::getInstance();
	}	
}

?>