<?php 

namespace Tx\Typogento\Core;


/**
 * Rewrites Magento URLs to TYPO3 URLs, by using the routing configuation.
 *
 * @author Artus Kolanowski <artus@ionoi.net>
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @see Typogento_Core_Model_Url
 */

class Rewriter implements \TYPO3\CMS\Core\SingletonInterface {
	
	/**
	 * @var \Tx\Typogento\Core\Routing\Router
	 */
	protected $router = null;
	
	/**
	 * @var \Tx\Typogento\Core\Dispatcher
	 */
	protected $dispatcher = null;
	
	/**
	 * @var \TYPO3\CMS\Core\Log\LogManager
	 */
	protected $logger = null;
	
	/**
	 * Initializes the rewriter.
	 */
	public function __construct() {
		$this->router = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx\\Typogento\\Core\\Routing\\Router');
		$this->dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx\\Typogento\\Core\\Dispatcher');
		$this->logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(__CLASS__);
	}
	
	/**
	 * Rewrites an URL.
	 * 
	 * @param string $url The Magento URL string to rewrite
	 * @param array $data The Magento URL parameter
	 * @return void
	 * @see Typogento_Core_Model_Url::getUrl()
	 */
	public function rewrite(&$url, array &$data) {
		// patch environment data
		$this->patchEnvironmentData($data);
		// build filter environment
		$filter = $this->buildFilterEnvironment($data);
		// build filter environment
		$target = $this->buildTargetEnvironment($data);
		// lookup matching route
		$route = $this->router->lookup(\Tx\Typogento\Core\Routing\Router::ROUTE_SECTION_RENDER, $filter);
		// rewrite url
		$rewritten = $this->router->process($route, $target);
		// log debug
		$this->logger->debug(
			sprintf(
				'Rewrite URL "%s" to "%s" using render route "%s".',
				urldecode($url), urldecode($rewritten), $route->getId()
			),
			$data
		);
		$url = $rewritten;
	}
	
	/**
	 * Patches the data for the filter and target environments.
	 *
	 * @param array $data
	 * @return void
	 */
	protected function patchEnvironmentData(array &$data) {
		/**
		 * Patches the 'uenc' parameter, which contains mostly an url
		 * generated by Mage_Core_Helper_Url::getCurrentUrl(), a method
		 * which is hardly to overwrite.
		 */
		if(isset($data[\Mage_Core_Controller_Varien_Action::PARAM_NAME_URL_ENCODED])) {
			// get dispatcher environment
			$environment = $this->dispatcher->getEnvironment();
			// get typo3 request uri
			$requestUri = $environment->get('REQUEST_URI', \Tx\Typogento\Core\Environment::ENVIRONMENT_SECTION_PRESERVED);
			// decode url
			$url = \Mage::helper('core/url')->urlDecode($data[\Mage_Core_Controller_Varien_Action::PARAM_NAME_URL_ENCODED]);
			// fix current url
			$url = str_replace(\Mage::helper('core/url')->getCurrentUrl(), $requestUri, $url);
			// fix missing parts
			$url = \TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($url);
			// encode url
			$data[\Mage_Core_Controller_Varien_Action::PARAM_NAME_URL_ENCODED] = \Mage::helper('core/url')->urlEncode($url);
		}
	}
	
	/**
	 * Builds the environment for the route filters.
	 *
	 * @param array $data The environment data
	 * @return \Tx\Typogento\Core\Environment
	 */
	protected function buildFilterEnvironment(array &$data) {
		// prepare filter environment
		$filter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx\\Typogento\\Core\\Environment');
		$filter->register('_GET', $_GET);
		$filter->register('QUERY_STRING', $_SERVER['QUERY_STRING']);
		$filter->set('_GET', $data);
		$filter->set('QUERY_STRING', \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $filter->get('_GET'), '', false, true));
		// return result
		return $filter;
	}
	
	/**
	 * Builds the environment for the route targets
	 *
	 * @param array $data The environment data
	 * @return \Tx\Typogento\Core\Environment
	 */
	protected function buildTargetEnvironment(array &$data) {
		// prepare target environment
		$target = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx\\Typogento\\Core\\Environment');
		$target->register('_GET', $_GET);
		$target->register('QUERY_STRING', $_SERVER['QUERY_STRING']);
		$target->set('_GET', array('tx_typogento' => $data));
		$target->set('QUERY_STRING', \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $target->get('_GET'), '', false, true));
		// return result
		return $target;
	}
}

?>