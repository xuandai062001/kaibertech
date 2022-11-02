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
namespace Lof\Autosearch\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\CatalogSearch\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Search\Model\QueryFactory;
use Magento\Catalog\Model\Layer\Resolver;
use \Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Catalog\Model\Layer\Filter\DataProvider\Category as CategoryDataProvider;
use Magento\Framework\Controller\ResultFactory;
use \Magento\Search\Helper\Data as SearchHelper;

class Ajaxgetproduct extends Action
{
	protected $catalogSearchData;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var QueryFactory
     */
    private $_queryFactory;

     /**
     * Catalog Layer Resolver
     *
     * @var Resolver
     */
     private $layerResolver;

	/**
     * @var \Magento\Catalog\Model\Category
     */
	protected $_categoryModel;

	/**
     * @var \Magento\Search\Helper\Data
     */
	protected $searchHelper;

	protected $_searchModel;

	 /**
     * Catalog Layer Resolver
     *
     * @var Resolver
     */
	protected $_layerResolver;

	/**
     * @var \Magento\Framework\Url\Helper\Data
     */
	protected $urlHelper;

	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
		Data $catalogSearchData,        
		StoreManagerInterface $storeManager,
		QueryFactory $queryFactory,
		Resolver $layerResolver,
		\Lof\Autosearch\Helper\Data $helper,
		\Lof\Autosearch\Model\Search $searchModel,
		\Magento\Catalog\Model\Category $categoryModel,
		\Magento\Search\Model\ResourceModel\Query\CollectionFactory $queriesFactory,
		SearchHelper $searchHelper,
		\Magento\Framework\Url $urlHelper
		){
		$this->resultPageFactory = $resultPageFactory;
		$this->catalogSearchData = $catalogSearchData;
		$this->_storeManager     = $storeManager;
		$this->_queryFactory     = $queryFactory;
		$this->layerResolver     = $layerResolver;
		$this->_helper           = $helper;
		$this->_categoryModel    = $categoryModel;
		$this->_queriesFactory   = $queriesFactory;
		$this->searchHelper      = $searchHelper;
		$this->_searchModel		 = $searchModel;
		$this->urlHelper = $urlHelper;
		parent::__construct($context);
	}

	/**
     * Add product to shopping cart action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) 
     */
	public function execute()
	{
		$this->_view->loadLayout();
		$data = $this->getRequest()->getPostValue();
		if (!$data) {
			$this->_redirect($this->_redirect->getRefererUrl());
			return;
		}
		$rootCategoryId = $this->_storeManager->getStore()->getRootCategoryId();
		$json          = [];
		$helper        = $this->_helper;
		$limit         = (int)$helper->getLimit();
		$thumbW        = (int)$helper->getThumbWidth();
		$thumbH        = (int)$helper->getThumbHeight();
		$showImage     = $helper->getShowImage();
		$showPrice     = $helper->getShowPrice();
		$showDes       = $helper->getShowShortDes();
		$showSku       = $helper->getShowSku();
		$showReview    = $helper->getShowReview();
		$showAddtocart = $helper->getShowAddtocart();
		$shortMax      = $helper->getShowMax();
		$searchFulltext= $helper->searchFulltext();
		$limitTerm 	   = $helper->getLimitTerm();
		$enableAjaxTerm = $helper->getEnableAjaxTerm();
		$categoryId    = isset($data['cat'])?$data['cat']:$rootCategoryId;
		$searchstring  = isset($data['q'])?$data['q']:'';
		$storeId       = $this->_storeManager->getStore()->getId();

		$limitTerm 	   = (isset($data['limit_term']) && $data['limit_term'])?(int)$data['limit_term']:(int)$limitTerm;
		$query = $this->_queryFactory->get();
		$queryText = $query->getQueryText();
		$queryText = $queryText?$queryText:$searchstring;
		$searchstring = $searchstring?$searchstring:$queryText;
		$query->setStoreId($storeId);

		if ($queryText) {
			$this->_layerResolver = $this->_objectManager->create("Magento\Catalog\Model\Layer\Resolver");
			$this->_layerResolver->create(LayerResolver::CATALOG_LAYER_SEARCH);
			if ($searchFulltext == '1') {
				if(!$categoryId){
					$categoryId = $rootCategoryId;
				}
				$category = $this->_categoryModel->load($categoryId);
				$collection = $this->_searchModel->getResultSearchCollection($searchstring,$category, $storeId);
			}else{
				$collection = $this->_layerResolver->get()
					->getProductCollection()
					->addAttributeToSelect('*')
					->addSearchFilter($queryText);
					if($categoryId && ($categoryId != $rootCategoryId)){
						$category = $this->_categoryModel->load($categoryId);
						$collection = $collection->addCategoryFilter($category);
					}
			}
			
			$collection->setOrder('entity_id','desc')->setPageSize($limit)->setCurPage(1);
			if ($this->_objectManager->get('Magento\CatalogSearch\Helper\Data')->isMinQueryLength()) {
				$query->setId(0)->setIsActive(1)->setIsProcessed(1);
			}else{
				$query->saveIncrementalPopularity()->saveNumResults($collection->getSize());
				if ($query->getRedirect()) {
					$this->getResponse()->setRedirect($query->getRedirect());
					return;
				}
			}

			$json = [];
			$json['total'] = $collection->getSize();
			$products = [];
			$i = 1;
			foreach ($collection as $_product){
				if($i > $limit) break;
				$item_html = $this->_view->getLayout()->createBlock('Lof\Autosearch\Block\Item')
				->assign('product', $_product)
				->assign('thumbW', $thumbW)
				->assign('thumbH', $thumbH)
				->assign('showImage', $showImage)
				->assign('showPrice', $showPrice)
				->assign('showDes', $showDes)
				->assign('shortMax', $shortMax)
				->assign('showSku', $showSku)
				->assign('showReview', $showReview)
				->assign('showAddtocart', $showAddtocart)
				->assign('queryText', $queryText)
				->toHtml();

				$products[] = [
				'product_id' => $_product->getId(),
				'name'       => strip_tags(html_entity_decode($_product->getName(), ENT_QUOTES, 'UTF-8')),
				'image'      => '1',
				'link'       => $_product->getProductUrl(),
				'price'      => $_product->getPrice(),
				'html'       => $item_html
				]; 
				$i++;
			}
			// die('test'); 
			if(empty($products)){
				$products[] = [
				'product_id' => 0,
				'name'       => '',
				'image'      => '1',
				'link'       => '',
				'price'      => 0,
				'html'       => __('No products exists')
				];
			}
			$json['products'] = $products;
			if($enableAjaxTerm) {
				$_suggestCollection = $this->_queriesFactory->create();
				$_suggestCollection->setPopularQueryFilter($storeId);
				$_suggestCollection->getSelect()->where('main_table.query_text LIKE "%' . $this->getRequest()->getParam('q') . '%"')->order('main_table.num_results DESC');

				if($limitTerm) {
					$_suggestCollection->setPageSize($limitTerm);
				}

				$data = [];
				$i = 1;
				foreach ($_suggestCollection as $item) {
					if($i > $limitTerm) break;
					$suggestData = $item->getData();
					$suggestData['url'] = $this->searchHelper->getResultUrl($item['query_text']);
					$data[] = $suggestData;
					$i++;  
				}
				$json['suggested'] = $data;
			} else {
				$json['suggested'] = [];
			}
			$enable_search_cms = $helper->getConfig("search_options/enable_search_cms");
			$enable_search_vesblog = $helper->getConfig("search_options/enable_search_vesblog");
			$enable_search_vesbrand = $helper->getConfig("search_options/enable_search_vesbrand");
			$enable_search_loffaq = $helper->getConfig("search_options/enable_search_loffaq");
			$enable_search_category = $helper->getConfig("search_options/enable_search_category");

			$json['cms'] = $json['category'] = $json['vesblog'] = $json['loffaq'] = [];
			//enable search cms pages
			if($enable_search_category) {
				$category_colection = $this->_objectManager->create("Lof\Autosearch\Model\ResourceModel\CatalogCategory\Collection");
				$category_colection->addAttributeToSelect('*');
				if ($searchFulltext == '1') {
			        $search_arr = explode(" ", $searchstring);
			        if(count($search_arr) <= 1) {
			            $search_arr = $searchstring;
			        }
			        $category_colection->addSearchFilter($search_arr);
				} else {
					$category_colection->addSearchFilter($searchstring);
				}
				$category_colection->addIsActiveFilter();
				$category_colection->setStore($this->_storeManager->getStore());
				$category_colection->setPageSize($limit)->setCurPage(1);
				$json['category_total'] = $category_colection->getSize();
				$categories = [];
				$i = 1;
				foreach ($category_colection as $category){
					if($i > $limit) break;
					if($category->getUrlKey()){
						$item_html = $this->_view->getLayout()->createBlock('Lof\Autosearch\Block\Result\Category')
						->assign('category', $category)
						->assign('queryText', $queryText)
						->assign('searchFulltext', $searchFulltext)
						->toHtml();
					
						$categories[] = [
						'identifier' => $category->getUrlKey(),
						'title'       => strip_tags(html_entity_decode($category->getName(), ENT_QUOTES, 'UTF-8')),
						'link'       => $category->getUrl(),
						'html'       => $item_html
						];
						$i++;
					}
				}
				$json['category'] = $categories;
			}
			//enable search cms pages
			if($enable_search_cms) {
				$cms_page_colection = $this->_objectManager->create("Lof\Autosearch\Model\ResourceModel\CmsPage\Collection");

				if ($searchFulltext == '1') {
			        $search_arr = explode(" ", $searchstring);
			        if(count($search_arr) <= 1) {
			            $search_arr = $searchstring;
			        }
			        $cms_page_colection->addSearchFilter($search_arr);
				} else {
					$cms_page_colection->addSearchFilter($searchstring);
				}
				$cms_page_colection->addFieldToFilter('is_active',1);
				$cms_page_colection->addStoreFilter($storeId);
				$cms_page_colection->setPageSize($limit)->setCurPage(1);

				$json['cms_total'] = $cms_page_colection->getSize();
				$cms_pages = [];
				$i = 1;
				foreach ($cms_page_colection as $_cms_page){
					if($i > $limit) break;
					$item_html = $this->_view->getLayout()->createBlock('Lof\Autosearch\Block\Result\Cmspage')
					->assign('cms_page', $_cms_page)
					->assign('queryText', $queryText)
					->assign('searchFulltext', $searchFulltext)
					->toHtml();

					$cms_pages[] = [
					'identifier' => $_cms_page->getIdentifier(),
					'title'       => strip_tags(html_entity_decode($_cms_page->getTitle(), ENT_QUOTES, 'UTF-8')),
					'link'       => $this->urlHelper->getUrl(null, ['_direct' => $_cms_page->getIdentifier()]),
					'html'       => $item_html
					]; 
					$i++;
				}
				$json['cms'] = $cms_pages;
			}
			//enable search ves blog posts
			if($enable_search_vesblog && $this->_helper->checkModuleInstalled("Ves_Blog")) {
				$postModel = $this->_objectManager->create("Ves\Blog\Model\Post");
				$blogHelper      = $this->_objectManager->create("Ves\Blog\Helper\Data");
				$store = $this->_storeManager->getStore();
				$postCollection = $postModel->getCollection()
			        ->addFieldToFilter('is_active',1)
		        	->addStoreFilter($store)
		        	->setPageSize($limit)
		        	->setCurPage(1);

		        $postCollection->getSelect()->order("creation_time DESC");
		        $search_arr = explode(" ", $searchstring);
		        if(count($search_arr) <= 1) {
		            $search_arr = $searchstring;
		        }
				if ($searchFulltext == '1' && is_array($search_arr)) {
		        	$wheres = [];
		            $wheres['title'] = [];
		            $wheres['meta_keywords'] = [];
		            $wheres['meta_description'] = [];
		            $wheres['identifier'] = [];
		            $wheres['short_content'] = [];
		            foreach($search_arr as $_searchstring) {
		            	if(strlen($_searchstring) > 2){
			                $wheres['title'][] = ' LOWER(title) like "%'.addslashes($_searchstring).'%"';
			                $wheres['meta_keywords'][] = ' LOWER(meta_keywords) like "%'.addslashes($_searchstring).'%"';
			                $wheres['meta_description'][] = ' LOWER(meta_description) like "%'.addslashes($_searchstring).'%"';
			                $wheres['identifier'][] = ' LOWER(identifier) like "%'.addslashes($_searchstring).'%"';
			                $wheres['short_content'][] = ' LOWER(short_content) like "%'.addslashes($_searchstring).'%"';

			            }
		            }

		            $tmp_wheres = [];
		            if($wheres['title']) {
		                $tmp_wheres[] = implode( " AND ", $wheres['title']);
		            }
		            if($wheres['meta_keywords']) {
		                $tmp_wheres[] = implode( " AND ", $wheres['meta_keywords']);
		            }
		            if($wheres['meta_description']) {
		                $tmp_wheres[] = implode( " AND ", $wheres['meta_description']);
		            }
		            if($wheres['identifier']) {
		                $tmp_wheres[] = implode( " AND ", $wheres['identifier']);
		            }
		            if($wheres['short_content']) {
		                $tmp_wheres[] = implode( " AND ", $wheres['short_content']);
		            }
		            if($tmp_wheres) {
			            $sql = implode(" OR ", $tmp_wheres);
			            $postCollection->getSelect()->where( $sql );
			        }
				} else {
					$postCollection->addFieldToFilter(['title', 'identifier', 'short_content', 'meta_keywords','meta_description'], [
			                                    ['like'=>'%'.addslashes($searchstring).'%'],
			                                    ['like'=>'%'.addslashes($searchstring).'%'],
			                                    ['like'=>'%'.addslashes($searchstring).'%'],
			                                    ['like'=>'%'.addslashes($searchstring).'%'],
			                                    ['like'=>'%'.addslashes($searchstring).'%']
			                            ]);
				}

				$json['vesblog_total'] = $postCollection->getSize();
				$blog_posts = [];
				$i = 1;
				foreach ($postCollection as $_blog_post){
					if($i > $limit) break;
					$item_html = $this->_view->getLayout()->createBlock('Lof\Autosearch\Block\Result\Vesblog')
					->assign('blog_post', $_blog_post)
					->assign('queryText', $queryText)
					->assign('searchFulltext', $searchFulltext)
					->toHtml();

					$blog_posts[] = [
					'identifier' => $_blog_post->getIdentifier(),
					'title'       => strip_tags(html_entity_decode($_blog_post->getTitle(), ENT_QUOTES, 'UTF-8')),
					'link'       => $blogHelper->getPostUrl($_blog_post),
					'html'       => $item_html
					]; 
					$i++;
				}
				$json['vesblog'] = $blog_posts;
			}
			//enable search ves brand
			if($enable_search_vesbrand && $this->_helper->checkModuleInstalled("Ves_Brand")) {
				$brandModel = $this->_objectManager->create("Ves\Brand\Model\Brand");
				$brandHelper      = $this->_objectManager->create("Ves\Brand\Helper\Data");
				$brandCollection = $brandModel->getCollection()
			        ->addFieldToFilter('status',1)
		        	->setPageSize($limit)
		        	->setCurPage(1);

		        $brandCollection->getSelect()->order("position ASC");
		        $search_arr = explode(" ", $searchstring);
		        if(count($search_arr) <= 1) {
		            $search_arr = $searchstring;
		        }

				if ($searchFulltext == '1' && is_array($search_arr)) {
		        	$wheres = [];
		            $wheres['name'] = [];
		            $wheres['description'] = [];
		            $wheres['url_key'] = [];
		            foreach($search_arr as $_searchstring) {
		            	if(strlen($_searchstring) > 2){
			                $wheres['name'][] = ' LOWER(name) like "%'.addslashes($_searchstring).'%"';
			                $wheres['description'][] = ' LOWER(description) like "%'.addslashes($_searchstring).'%"';
			                $wheres['url_key'][] = ' LOWER(url_key) like "%'.addslashes($_searchstring).'%"';
			            }
		            }

		            $tmp_wheres = [];
		            if($wheres['name']) {
		                $tmp_wheres[] = implode( " AND ", $wheres['name']);
		            }
		            if($wheres['description']) {
		                $tmp_wheres[] = implode( " AND ", $wheres['description']);
		            }
		            if($wheres['url_key']) {
		                $tmp_wheres[] = implode( " AND ", $wheres['url_key']);
		            }
		            if($tmp_wheres) {
			            $sql = implode(" OR ", $tmp_wheres);
			            $brandCollection->getSelect()->where( $sql );
			        }
			        
				} else {
					$brandCollection->addFieldToFilter(['name', 'description', 'url_key'], [
			                                    ['like'=>'%'.addslashes($searchstring).'%'],
			                                    ['like'=>'%'.addslashes($searchstring).'%'],
			                                    ['like'=>'%'.addslashes($searchstring).'%']
			                            ]);
				}

				

				$json['vesbrand_total'] = $brandCollection->getSize();
				$brand_posts = [];
				$i = 1;
				foreach ($brandCollection as $_vesbrand){
					if($i > $limit) break;
					$item_html = $this->_view->getLayout()->createBlock('Lof\Autosearch\Block\Result\Vesbrand')
					->assign('vesbrand', $_vesbrand)
					->assign('queryText', $queryText)
					->assign('searchFulltext', $searchFulltext)
					->toHtml();

					$brand_posts[] = [
					'identifier' => $_vesbrand->getUrlKey(),
					'title'       => strip_tags(html_entity_decode($_vesbrand->getName(), ENT_QUOTES, 'UTF-8')),
					'link'       => $_vesbrand->getUrl(),
					'html'       => $item_html
					]; 
					$i++;
				}
				$json['vesbrand'] = $brand_posts;
			}
			//enable search lof faq
			if($enable_search_loffaq && $this->_helper->checkModuleInstalled("Lof_Faq")) {
				$questionModel = $this->_objectManager->create("Lof\Faq\Model\Question");
				$faqHelper      = $this->_objectManager->create("Lof\Faq\Helper\Data");
				$store = $this->_storeManager->getStore();
				$questionCollection = $questionModel->getCollection()
			        ->addFieldToFilter('is_active',1)
			        ->addStoreFilter($store)
		        	->setPageSize($limit)
		        	->setCurPage(1);

		        $questionCollection->getSelect()->order("question_position ASC");
		        $search_arr = explode(" ", $searchstring);
		        if(count($search_arr) <= 1) {
		            $search_arr = $searchstring;
		        }
				if ($searchFulltext == '1' && is_array($search_arr)) {
		        	$wheres = [];
		            $wheres['title'] = [];
		            $wheres['answer'] = [];
		            foreach($search_arr as $_searchstring) {
		            	if(strlen($_searchstring) > 2){
			                $wheres['title'][] = ' LOWER(title) like "%'.addslashes($_searchstring).'%"';
			                $wheres['answer'][] = ' LOWER(answer) like "%'.addslashes($_searchstring).'%"';
			            }
		            }

		            $tmp_wheres = [];
		            if($wheres['title']) {
		                $tmp_wheres[] = implode( " AND ", $wheres['title']);
		            }
		            if($wheres['answer']) {
		                $tmp_wheres[] = implode( " AND ", $wheres['answer']);
		            }
		            if($tmp_wheres) {
			            $sql = implode(" OR ", $tmp_wheres);
			            $questionCollection->getSelect()->where( $sql );
			        }
			        
				} else {
					$questionCollection->getSelect()->where('(LOWER(title) LIKE "%' . addslashes($searchstring) . '%") OR (LOWER(answer) LIKE "%' . addslashes($searchstring) . '%")');
				}

				$json['loffaq_total'] = $questionCollection->getSize();
				$loffaqs = [];
				$i = 1;
				foreach ($questionCollection as $_question){
					if($i > $limit) break;
					$item_html = $this->_view->getLayout()->createBlock('Lof\Autosearch\Block\Result\Loffaq')
					->assign('question', $_question)
					->assign('queryText', $queryText)
					->assign('searchFulltext', $searchFulltext)
					->toHtml();

					$loffaqs[] = [
					'identifier' => $_question->getId(),
					'title'       => strip_tags(html_entity_decode($_question->getTitle(), ENT_QUOTES, 'UTF-8')),
					'link'       =>  $faqHelper->getQuestionUrl($_question),
					'html'       => $item_html
					]; 
					$i++;
				}
				$json['loffaq'] = $loffaqs;
			}
			$this->getResponse()->representJson(
				$this->_objectManager->get('Magento\Framework\Json\Helper\Data')->jsonEncode($json)
				);
			return;
		} else {
			$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
			$resultRedirect->setUrl($this->_url->getBaseUrl());
			return $resultRedirect;
		}
	} 
}