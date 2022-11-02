<?php
/**
 * Venustheme
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the venustheme.com license that is
 * available through the world-wide-web at this URL:
 * http://venustheme.com/license
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Venustheme
 * @package    Ves_Testimonial
 * @copyright  Copyright (c) 2016 Landofcoder (http://www.venustheme.com/)
 * @license    http://www.venustheme.com/LICENSE-1.0.html
 */
namespace Lof\Autosearch\Model;

use Lof\Autosearch\Api\Data\ItemInterface;

use \Magento\Search\Helper\Data as SearchHelper;
use Magento\Store\Model\StoreManagerInterface;

class ResultItem implements ItemInterface
{
    protected $_result_products = [];
    protected $_product_total = 0;

    protected $_result_blog = [];
    protected $_blog_total = 0;

    protected $_result_brand = [];
    protected $_brand_total = 0;

    protected $_result_cms_page = [];
    protected $_cms_total = 0;

    protected $_result_faq = [];
    protected $_faq_total = 0;

    protected $_result_category = [];
    protected $_category_total = 0;

    protected $_result_suggestion = [];

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Search\Helper\Data
     */
    protected $searchHelper;

    /**
     * @var QueryFactory
     */
    private $_queryFactory;

    public function __construct(
       \Lof\Autosearch\Helper\Data $helper,
       \Magento\Search\Model\ResourceModel\Query\CollectionFactory $queriesFactory,
       SearchHelper $searchHelper,
       \Magento\Framework\View\LayoutInterface $layout,
       \Magento\Framework\ObjectManagerInterface $objectManager,
       StoreManagerInterface $storeManager
        ) {
        $this->_helper           = $helper;
        $this->_queriesFactory   = $queriesFactory;
        $this->searchHelper      = $searchHelper;
        $this->_layout = $layout;
        $this->_objectManager  = $objectManager;
        $this->_storeManager     = $storeManager;
    }

    /**
     * Get products
     * @return mixed|array|null
     */
    public function getProducts(){
        return $this->_result_products;
    }

    /**
     * Set products
     * @param {inherit}
     * @return $this
     */
    public function setProducts($products, $queryText){
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
        $limitTerm     = $helper->getLimitTerm();
        $enableAjaxTerm = $helper->getEnableAjaxTerm();

        $result_products = [];
        $i = 1;
        if($products) {
            foreach ($products as $_product){
                if($i > $limit) break;
                $item_html = $this->_layout->createBlock('Lof\Autosearch\Block\Item')
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

                $result_products[] = [
                'product_id' => $_product->getId(),
                'name'       => strip_tags(html_entity_decode($_product->getName(), ENT_QUOTES, 'UTF-8')),
                'image'      => '1',
                'link'       => $_product->getProductUrl(),
                'price'      => $_product->getPrice(),
                'html'       => $item_html
                ]; 
                $i++;
            }
        }
        if(empty($result_products)){
            $result_products[] = [
            'product_id' => 0,
            'name'       => '',
            'image'      => '1',
            'link'       => '',
            'price'      => 0,
            'html'       => __('No products exists')
            ];
        }
        $this->_result_products = $result_products;
        return $this;
    }

    /**
     * Get blog posts
     * @return mixed|array|null
     */
    public function getBlogPosts(){
        return $this->_result_blog;
    }

    /**
     * Set blog posts
     * @param {inherit}
     * @param {inherit}
     * @return $this
     */
    public function setBlogPosts($postCollection, $queryText){
        $helper        = $this->_helper;
        $enable_search_vesblog = $helper->getConfig("search_options/enable_search_vesblog");
        $limit         = (int)$helper->getLimit();
        $searchFulltext= $helper->searchFulltext();
        //enable search ves blog posts
        if($enable_search_vesblog && $this->_helper->checkModuleInstalled("Ves_Blog")) {
            $blog_posts = [];
            $i = 1;
            $blogHelper      = $this->_objectManager->create("Ves\Blog\Helper\Data");
            foreach ($postCollection as $_blog_post){
                if($i > $limit) break;
                $item_html = $this->_layout->createBlock('Lof\Autosearch\Block\Result\Vesblog')
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
            $this->_result_blog = $blog_posts;
        } else {
            $this->_result_blog = [];
        }
        return $this;
    }

    /**
     * Get brands
     * @return mixed|array|null
     */
    public function getBrands(){
        return $this->_result_brand;
    }

    /**
     * Set brand
     * @param {inherit}
     * @param {inherit}
     * @return $this
     */
    public function setBrand($brandCollection, $queryText){
        $helper        = $this->_helper;
        $enable_search_vesbrand = $helper->getConfig("search_options/enable_search_vesbrand");
        $limit         = (int)$helper->getLimit();
        $searchFulltext= $helper->searchFulltext();

        if($enable_search_vesbrand && $this->_helper->checkModuleInstalled("Ves_Brand")) {
            $brandHelper      = $this->_objectManager->create("Ves\Brand\Helper\Data");
            $brand_items = [];
            $i = 1;
            foreach ($brandCollection as $_vesbrand){
                if($i > $limit) break;
                $item_html = $this->_layout->createBlock('Lof\Autosearch\Block\Result\Vesbrand')
                ->assign('vesbrand', $_vesbrand)
                ->assign('queryText', $queryText)
                ->assign('searchFulltext', $searchFulltext)
                ->toHtml();

                $brand_items[] = [
                'identifier' => $_vesbrand->getUrlKey(),
                'title'       => strip_tags(html_entity_decode($_vesbrand->getName(), ENT_QUOTES, 'UTF-8')),
                'link'       => $_vesbrand->getUrl(),
                'html'       => $item_html
                ]; 
                $i++;
            }
            $this->_result_brand = $brand_items;
        } else {
            $this->_result_brand = [];
        }
        return $this;
    }

    /**
     * Get cms page
     * @return mixed|array|null
     */
    public function getCmsPage(){
        return $this->_result_cms_page;
    }

    /**
     * Set cms_page
     * @param {inherit}
     * @param {inherit}
     * @return $this
     */
    public function setCmsPage($cms_page_colection, $queryText){
        $helper        = $this->_helper;
        $enable_search_cms = $helper->getConfig("search_options/enable_search_cms");
        $limit         = (int)$helper->getLimit();
        $searchFulltext= $helper->searchFulltext();

        if($enable_search_cms) {
            $cms_pages = [];
            $i = 1;
            foreach ($cms_page_colection as $_cms_page){
                if($i > $limit) break;
                $item_html = $this->_layout->createBlock('Lof\Autosearch\Block\Result\Cmspage')
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
            $this->_result_cms_page = $cms_pages;
        } else {
            $this->_result_cms_page = [];
        }
        return $this;
    }

     /**
     * Get faq
     * @return mixed|array|null
     */
    public function getFaq(){
        return $this->_result_faq;
    }

    /**
     * Set faq
     * @param {inherit}
     * @param {inherit}
     * @return $this
     */
    public function setFaq($questionCollection, $queryText){
        $helper        = $this->_helper;
        $enable_search_loffaq = $helper->getConfig("search_options/enable_search_loffaq");
        $limit         = (int)$helper->getLimit();
        $searchFulltext= $helper->searchFulltext();

        if($enable_search_loffaq && $this->_helper->checkModuleInstalled("Lof_Faq")) {
            $faqHelper      = $this->_objectManager->create("Lof\Faq\Helper\Data");
            $loffaqs = [];
            $i = 1;
            foreach ($questionCollection as $_question){
                if($i > $limit) break;
                $item_html = $this->_layout->createBlock('Lof\Autosearch\Block\Result\Loffaq')
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
            $this->_result_faq = $loffaqs;
        } else {
            $this->_result_faq = [];
        }
        return $this;
    }

    /**
     * Get suggested
     * @return mixed|array|null
     */
    public function getSuggested(){
        return $this->_result_suggestion;
    }

    /**
     * Set suggested
     * @param {inherit}
     * @return $this
     */
    public function setSuggested($_suggestCollection, $queryText){
        $helper        = $this->_helper;
        $enableAjaxTerm = $helper->getEnableAjaxTerm();
        $limitTerm     = $helper->getLimitTerm();
        if($enableAjaxTerm) {
            
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
            $this->_result_suggestion = $data;
        } else {
            $this->_result_suggestion = [];
        }
        return $this;
    }

    /**
     * Get product total
     * @return int
     */
    public function getTotal(){
        return $this->_product_total;
    }

    /**
     * Set suggested
     * @param int $product_total
     * @return $this
     */
    public function setTotal($product_total){
        $this->_product_total = (int)$product_total;
        return $this;
    }

    /**
     * Get product total
     * @return int
     */
    public function getCmsTotal(){
        return $this->_cms_total;
    }

    /**
     * Set suggested
     * @param int $cms_total
     * @return $this
     */
    public function setCmsTotal($cms_total){
        $this->_cms_total = (int)$cms_total;
        return $this;
    }

     /**
     * Get product total
     * @return int
     */
    public function getBlogTotal(){
        return $this->_blog_total;
    }

    /**
     * Set suggested
     * @param int $vesblog_total
     * @return $this
     */
    public function setBlogTotal($vesblog_total){
        $this->_blog_total = (int)$vesblog_total;
        return $this;
    }

    /**
     * Get product total
     * @return int
     */
    public function getBrandTotal(){
        return $this->_brand_total;
    }

    /**
     * Set vesbrand_total
     * @param int $vesbrand_total
     * @return $this
     */
    public function setBrandTotal($vesbrand_total){
        $this->_brand_total = (int)$vesbrand_total;
        return $this;
    }

     /**
     * Get Faq total
     * @return int
     */
    public function getFaqTotal(){
        return $this->_faq_total;
    }

    /**
     * Set faq
     * @param int $faq_total
     * @return $this
     */
    public function setFaqTotal($faq_total){
        $this->_faq_total = (int)$faq_total;
        return $this;
    }

    /**
     * Get Catalog Category total
     * @return int
     */
    public function getCategoryTotal(){
        return $this->_category_total;
    }

    /**
     * Set catalog category total
     * @param int $category_total
     * @return $this
     */
    public function setCategoryTotal($category_total){
        $this->_category_total = (int)$category_total;
        return $this;
    }

    /**
     * Get categories
     * @return mixed|null
     */
    public function getCategory(){
        return $this->_result_category;
    }

    /**
     * Set categories
     * @param {inherit}
     * @param {inherit}
     * @return $this
     */
    public function setCategory($categories, $queryText){
        $helper        = $this->_helper;
        $enable_search_category = $helper->getConfig("search_options/enable_search_category");
        $limit         = (int)$helper->getLimit();
        $searchFulltext= $helper->searchFulltext();

        if($enable_search_category) {
            $searchstring = $queryText;
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
            $category_total = $category_colection->getSize();
            $this->setCategoryTotal($category_total);
            $categories = [];
            $i = 1;
            foreach ($category_colection as $category){
                if($i > $limit) break;
                if($category->getUrlKey()){
                    $item_html = $this->_layout->createBlock('Lof\Autosearch\Block\Result\Category')
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
            $this->_result_category = $categories;
        } else {
            $this->_result_category = [];
        }
        return $this;
    }


}
