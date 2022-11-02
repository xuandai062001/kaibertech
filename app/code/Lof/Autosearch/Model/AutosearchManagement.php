<?php
/**
 * Landofcoder
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Landofcoder.com license that is
 * available through the world-wide-web at this URL:
 * https://Landofcoder.com/license
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Landofcoder
 * @package    Lof_Autosearch
 * @copyright  Copyright (c) 2016 Landofcoder (http://www.Landofcoder.com/)
 * @license    https://www.Landofcoder.com/LICENSE-1.0.html
 */

namespace Lof\Autosearch\Model;
use Lof\Autosearch\Api\Data\SearchResultInterfaceFactory;
use Lof\Autosearch\Api\Data\ItemInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Layer\Resolver;
use \Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Magento\Catalog\Model\Layer\Filter\DataProvider\Category as CategoryDataProvider;
use Magento\Framework\Controller\ResultFactory;
use \Magento\Search\Helper\Data as SearchHelper;
use Magento\CatalogSearch\Helper\Data;

class AutosearchManagement implements \Lof\Autosearch\Api\AutosearchManagementInterface
{

	public function __construct(
        ItemInterfaceFactory $dataItemFactory,
        SearchResultInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Lof\Autosearch\Helper\Data $helper,
        \Magento\Catalog\Model\Category $categoryModel,
        \Lof\Autosearch\Model\Search $searchModel,
        Resolver $layerResolver,
        Data $catalogSearchData,
        \Magento\Search\Model\ResourceModel\Query\CollectionFactory $queriesFactory,
		SearchHelper $searchHelper,
		\Magento\Framework\Url $urlHelper

    ) {
        $this->searchResultsFactory     = $searchResultsFactory;
        $this->dataObjectHelper         = $dataObjectHelper;
        $this->dataItemFactory          = $dataItemFactory;
        $this->dataObjectProcessor      = $dataObjectProcessor;
        $this->_objectManager           = $objectManager;
        $this->_helper            		= $helper;
        $this->_storeManager     		= $storeManager;
        $this->_categoryModel    = $categoryModel;
        $this->catalogSearchData = $catalogSearchData;
        $this->layerResolver     = $layerResolver;
        $this->searchHelper      = $searchHelper;
		$this->_searchModel		 = $searchModel;
		$this->urlHelper = $urlHelper;
		$this->_queriesFactory   = $queriesFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getAutosearch($queryText, $categoryId = 0, $storeId = 0, $limit_term = "")
    {

    	$rootCategoryId = $this->_storeManager->getStore()->getRootCategoryId();
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
		$limitTerm = $limitTerm?(int)$limitTerm:0;
		$enableAjaxTerm = $helper->getEnableAjaxTerm();
		$enable_search_cms = $helper->getConfig("search_options/enable_search_cms");
		$enable_search_vesblog = $helper->getConfig("search_options/enable_search_vesblog");
		$enable_search_vesbrand = $helper->getConfig("search_options/enable_search_vesbrand");
		$enable_search_loffaq = $helper->getConfig("search_options/enable_search_loffaq");
		$enable_search_category = $helper->getConfig("search_options/enable_search_category");

		$queryText = $queryText?str_replace("+"," ",$queryText):"";
		$queryText = trim($queryText);
		$searchstring = $queryText;

        $storeId = $storeId?(int)$storeId:$this->_storeManager->getStore()->getId();
        $categoryId = $categoryId?(int)$categoryId:(int)$rootCategoryId;
        $limitTerm = $limit_term?(int)$limit_term:(int)$limitTerm;
        $result_items = [];
        $total_products = $total_brands = $total_cms = $total_blogs = $total_faq = 0;
        $search_item_object = $this->dataItemFactory->create();

        if ($queryText) {
        	$this->_layerResolver = $this->_objectManager->create("Magento\Catalog\Model\Layer\Resolver");
			$this->_layerResolver->create(LayerResolver::CATALOG_LAYER_SEARCH);
			if ($searchFulltext == '1') {
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
			$collection->setPageSize($limit)->setCurPage(1);

			$total_products = $collection->getSize();

			$search_item_object->setProducts($collection, $queryText);
			$search_item_object->setTotal($total_products);

			//enable ajax suggestion keywords
			if($enableAjaxTerm) {
				$_suggestCollection = $this->_queriesFactory->create();
				$_suggestCollection->setPopularQueryFilter($storeId);
				$_suggestCollection->getSelect()->where('main_table.query_text LIKE "%' . $searchstring . '%"')->order('main_table.num_results DESC');

				if($limitTerm) {
					$_suggestCollection->setPageSize($limitTerm);
				}

				$search_item_object->setSuggested($_suggestCollection, $queryText);
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

				$total_cms = $cms_page_colection->getSize();
				$search_item_object->setCmsPage($cms_page_colection, $queryText);
				$search_item_object->setCmsTotal($total_cms);
			}

			//enable search categories
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
				$category_colection->setStore($storeId);
				$category_colection->setPageSize($limit)->setCurPage(1);

				$total_category = $category_colection->getSize();
				$search_item_object->setCategory($category_colection, $queryText);
				$search_item_object->setCategoryTotal($total_category);
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

				$total_blogs = $postCollection->getSize();

				$search_item_object->setBlogPosts($postCollection, $queryText);
				$search_item_object->setBlogTotal($total_blogs);
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

				
				$total_brands = $brandCollection->getSize();
				$search_item_object->setBrand($brandCollection, $queryText);
				$search_item_object->setBrandTotal($total_brands);
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

				$total_faq = $questionCollection->getSize();
				$search_item_object->setFaq($questionCollection, $queryText);
				$search_item_object->setFaqTotal($total_faq);
			}

        }
        
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setItem($search_item_object);
        return $searchResults;
    }
}
