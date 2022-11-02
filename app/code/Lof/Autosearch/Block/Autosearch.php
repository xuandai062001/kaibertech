<?php
/**
 * Landofcoder
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the landofcoder.com license that is
 * available through the world-wide-web at this URL:
 * http://landofcoder.com/license
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Landofcoder
 * @package    Lof_Autosearch
 * @copyright  Copyright (c) 2016 Landofcoder (http://www.landofcoder.com/)
 * @license    http://www.landofcoder.com/LICENSE-1.0.html
 */

namespace Lof\Autosearch\Block;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Search\Model\ResourceModel\Query\CollectionFactory;

class Autosearch extends \Magento\Catalog\Block\Product\AbstractProduct implements \Magento\Widget\Block\BlockInterface
{
	/**
	* @var \Magento\Customer\Model\Session
	*/
	protected $customerSession;

	/**
	 * @var \Magento\Framework\App\Config\ScopeConfigInterface
	 */
	protected $scopeConfig;

	/**
	 * @var array
	 */
	protected $_categories_links = [];

	/**
	 * @var \Magento\Catalog\Model\CategoryFactory
	 */
	protected $_categoryFactory;

	/**
	 * @var \Magento\Search\Model\ResourceModel\Query\CollectionFactory
	 */
	protected $_queryCollectionFactory;

	/**
	 * @var array
	 */
	protected $_terms;

	/**
	 * @var \Lof\Autosearch\Helper\Data
	 */
	protected $_autosearchHelper;

	protected $_is_widget = false;

	/**
	 * @param \Magento\Framework\View\Element\Template\Context $context                
	 * @param CustomerSession                                  $customerSession        
	 * @param \Magento\Catalog\Model\CategoryFactory           $categoryFactory        
	 * @param CollectionFactory                                $queryCollectionFactory 
	 * @param \Lof\Autosearch\Helper\Data                      $autosearchHelper       
	 * @param array                                            $data                   
	 */
	public function __construct(
		\Magento\Catalog\Block\Product\Context $context,
		CustomerSession $customerSession,
		\Magento\Catalog\Model\CategoryFactory $categoryFactory,
		CollectionFactory $queryCollectionFactory,
		\Lof\Autosearch\Helper\Data $autosearchHelper,
		array $data=[]
		){
		$this->customerSession         = $customerSession;
		$this->_categoryFactory        = $categoryFactory;
		$this->_queryCollectionFactory = $queryCollectionFactory;
		$this->_autosearchHelper       =$autosearchHelper;
		$this->scopeConfig             = $context->getScopeConfig();
		parent::__construct($context, $data);
	}

	public function _toHtml()
	{
		if(!$this->_is_widget){
			$categories = "";
			$searchCollection = "";
			if($this->_autosearchHelper->getConfig('general/show_filter_category')) {
				$rootCatId = $this->_storeManager->getStore()->getRootCategoryId();
				$maxLevel = $this->_autosearchHelper->getConfig('general/max_category_level');
				$categories = $this->getTreeCategories($rootCatId, 0,' ', (int)$maxLevel);
			}

			$this->assign( "categories_links", $this->_categories_links);
			$this->assign( "categories", $categories );
			$this->assign( "limit", $this->_autosearchHelper->getConfig('general/limit'));
			$this->assign( "limit_term" , $this->_autosearchHelper->getLimitTerm());
			$this->assign( "search_delay", $this->_autosearchHelper->getConfig('general/search_delay'));
			$this->assign( "thumb_width", $this->_autosearchHelper->getConfig('general/thumb_width'));
			$this->assign( "thumb_height", $this->_autosearchHelper->getConfig('general/thumb_height'));
			$this->assign( "listProductLink", $this->listProductLink() );
			$this->assign( "prefix", $this->_autosearchHelper->getConfig('general/prefix') );
			$this->assign( "show_filter_category", $this->_autosearchHelper->getConfig('general/show_filter_category') );
			$this->assign( "show_image", $this->_autosearchHelper->getConfig('general/show_image') );
			$this->assign( "show_price", $this->_autosearchHelper->getConfig('general/show_price') );
			$this->assign( "show_short_description", $this->_autosearchHelper->getConfig('general/show_short_description') );
			$this->assign( "short_max_char", $this->_autosearchHelper->getConfig('general/short_max_char') );

			if(!$this->getTemplate()){
				$template = 'Lof_Autosearch::default.phtml';
				if($tmp_template = $this->getConfig("template")) {
					$template = $tmp_template;
				}
				$this->setTemplate($template);
			}
		}
		return parent::_toHtml();
	}

	/**
	 * @return string
	 */
	public function listProductLink()
	{
		$isSecure = $this->_storeManager->getStore()->isCurrentlySecure();
		if($isSecure) {
			return $this->getUrl('autosearch/index/ajaxgetproduct', array('_secure'=>true));
		} else {
			return $this->getUrl('autosearch/index/ajaxgetproduct');
		}
	}

	/**
	 * @return string
	 */
	public function getCatalogSearchLink() 
	{
		$isSecure = $this->_storeManager->getStore()->isCurrentlySecure();
		if($isSecure) {
			return $this->getUrl('catalogsearch/result/', array('_secure'=>true));
		} else {
			return $this->getUrl('catalogsearch/result/');
		}
	}

	/**
	 * @return string
	 */
	public function getCatalogAdvancedSearchLink() 
	{
		$isSecure = $this->_storeManager->getStore()->isCurrentlySecure();
		if($isSecure) {
			return $this->getUrl('catalogsearch/advanced/result/', array('_secure'=>true));
		} else {
			return $this->getUrl('catalogsearch/advanced/result/');
		}
	}

	/**
	 * @return string
	 */
	public function getTreeCategories($parentId,$level = 0, $caret = '  ', $maxLevel = 3){
		$category_id = $this->getRequest()->getParam("cat");
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$current_category = $objectManager->get('Magento\Framework\Registry')->registry('current_category');
		if(!$category_id && $current_category){
			$category_id = $current_category->getId();
		}
		$include_in_menu = $this->_autosearchHelper->getConfig('general/show_inmenu_category');
		$allCats = $this->_categoryFactory->create()->getCollection()
		->addAttributeToSelect('*')
		->addAttributeToFilter('is_active','1')
		->addAttributeToSort('position', 'asc');
		if($include_in_menu){
			$allCats->addAttributeToFilter('include_in_menu','1');
		}
		if ($parentId) {
			$allCats->addAttributeToFilter('parent_id',array('eq' => $parentId));
		}
		$html= '';
		$prefix = "";
		if($level) {
			for($i=0;$i < $level; $i++) {
				$prefix .= $caret;
			}
		}
		foreach($allCats as $category)
		{
			if(!isset($this->_categories_links[$category->getId()])) {
				$this->_categories_links[$category->getId()] = $category->getId();
				$subcats = $category->getChildren();
				$html .= '<option value="'.$category->getId().'" '.($category_id == $category->getId() ? 'selected="selected"':'') .'>'.$prefix.$category->getName().'</option>';
				$subcats = $category->getChildren();
				if ($subcats != '' && ((int)$level + 1) < $maxLevel) { 
					$html .= $this->getTreeCategories($category->getId(), (int)$level + 1, $caret.'&nbsp;', $maxLevel);
				}

			}

		}
		return $html;
	}

	/**
	 * @return string
	 */
	public function getTerms(){
		if($this->_autosearchHelper->getConfig('search_terms/enable_search_term')){
			$limit = $this->_autosearchHelper->getConfig('search_terms/limit_term');
			$storeId      = $this->_storeManager->getStore()->getId();
			$_suggestCollection = $this->_queryCollectionFactory->create();
			$_suggestCollection->setPopularQueryFilter($storeId)->setPageSize($limit)->getSelect()->order('num_results DESC');
			return $_suggestCollection;
		}
		return [];
	}

	/**
	 * @return int
	 */
	public function getRootCategoryId(){
		return $this->_storeManager->getStore()->getRootCategoryId();
	}

	public function getConfig($key, $default = '')
	{
		$value = '';
		if($this->hasData($key) && $this->getData($key))
		{
			$value = $this->getData($key);
		} else {
			$value = $this->_autosearchHelper->getConfig($key);
		}
		return $value?$value:$default;
	}
}
