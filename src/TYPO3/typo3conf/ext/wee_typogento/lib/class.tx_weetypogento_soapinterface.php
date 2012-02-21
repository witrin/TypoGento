<?php

/**
 * TypoGento SOAP interface
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class tx_weetypogento_soapinterface implements t3lib_Singleton {

	protected static $urlPostfix = 'api/soap/?wsdl';
	
	protected $_client = null;
	
	protected $_session = null;
	
	protected $_resource = null;
	
	protected $_cache = false;
	
	/**
	 * Constructor which needs Soap Connection Details
	 *
	 * @param string $url
	 * @param string $username
	 * @param string $password
	 */
	public function __construct() {
		$this->_cache = t3lib_div::makeInstance('tx_weetypogento_cache');
	}

	/**
	 * Magic function which enables SOAP Calls like: resource()->action();
	 *
	 * @param string $name
	 * @param array $params
	 * @return unknown
	 */
	public function __call($name, $params) {
		if ($this->_resource) {
			$resource = $this->_resource;
			$this->_resource = null;
			$result = $this->call($resource.'.'.$name, $params);

			return $result;
		} else {
			$this->_resource = $name;
			return $this;
		}
	}
	
	public function getUrl() {
		return tx_weetypogento_tools::getExtConfig('url').self::$urlPostfix;
	}
	
	protected function _getHash($resource, $parameters) {
		ksort($parameters);
		$serialized = serialize(array_filter($parameters));
		return sha1($resource.$serialized);
	}

	/**
	 * call Soap Interface
	 *
	 * @param string $resource
	 * @param array $params
	 * @return unknown
	 */
	public function call($resource, $parameters = array()) {

		$hash = $this->_getHash($resource, $parameters);
		if (empty($hash)) {
			return null;
		}
		
		if ($this->_cache->has($hash)) {
			return $this->_cache->get($hash);
		} else {
			// lock request before start
			$lock = $this->_acquireLock($hash);
			// init session if not set
			if (!isset($this->client)
			|| !isset($this->_session)) {
				$url = $this->getUrl();
				$user = tx_weetypogento_tools::getExtConfig('username');
				$password = tx_weetypogento_tools::getExtConfig('password');
				// start soap client
				$this->client = new SoapClient($url, array('exceptions' => true, 'cache_wsdl' => WSDL_CACHE_MEMORY));
				$this->_session = $this->client->login($user, $password);
				// unset credentials
				unset($password);
				unset($user);
			}
			// perform soap query
			$result = $this->client->call($this->_session, $resource, $parameters);
			// cache the result
			$this->_cache->set($hash, $result, array());
			// release the lock
			$this->_releaseLock($lock);
			// return the result
			return $result;
		}
		
		return null;
	}
	
	protected function _acquireLock($hash) {
		try {
			$lock = t3lib_div::makeInstance('t3lib_lock', $hash, 'simple');
			$lock->setEnableLogging(FALSE);
			$success = $lock->acquire();
		} catch (Exception $e) {
			//t3lib_div::sysLog('Locking: Failed to acquire lock: '.$e->getMessage(), 't3lib_formprotection_BackendFormProtection', t3lib_div::SYSLOG_SEVERITY_ERROR);
			return false;
		}

		return $lock;
	}
	
	protected function _releaseLock($lock) {
		$success = false;
			// If lock object is set and was acquired, release it:
		if (is_object($lock) && $lock instanceof t3lib_lock && $lock->getLockStatus()) {
			$success = $lock->release();
			$lock = null;
		}

		return $success;
	}

	/**
	 * get SoapClient
	 *
	 * @return SoapClient
	 */
	public function getClient(){
		return $this->connection;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wee_typogento/lib/class.tx_weetypogento_soapinterface.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wee_typogento/lib/class.tx_weetypogento_soapinterface.php']);
}

?>