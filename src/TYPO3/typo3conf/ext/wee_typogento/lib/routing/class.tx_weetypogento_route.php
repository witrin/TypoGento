<?php 

/**
 * TypoGento route
 * 
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class tx_weetypogento_route {
	
	/**
	 * @var tx_weetypogento_routeFilter
	 */
	protected $_filter = null;
	
	/**
	 * @var tx_weetypogento_routeHandler
	 */
	protected $_handler = null;
	
	/**
	 * @var int
	 */
	protected $_priority = null;
	
	public function __construct(tx_weetypogento_routeFilter $filter, tx_weetypogento_routeHandler $handler, $priority = 0) {
		$this->_filter = $filter;
		$this->_handler = $handler;
		$this->_priority = (int)$priority;
	}
	
	public function __clone() {
		$this->_filter = clone $this->_filter;
		$this->_handler = clone $this->_handler;
	}
	
	public function __destruct() {
		unset($this->_filter);
		unset($this->_handler);
	}
	
	public function getFilter() {
		return $this->_filter;
	}
	
	public function getHandler() {
		return $this->_handler;
	}
	
	public function getPriority() {
		return $this->_priority;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wee_typogento/lib/routing/class.tx_weetypogento_route.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wee_typogento/lib/routing/class.tx_weetypogento_route.php']);
}

?>
