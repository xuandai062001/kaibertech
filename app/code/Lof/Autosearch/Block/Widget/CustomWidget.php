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

namespace Lof\Autosearch\Block\Widget;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Search\Model\ResourceModel\Query\CollectionFactory;

class CustomWidget extends \Lof\Autosearch\Block\Autosearch implements \Magento\Widget\Block\BlockInterface
{
    public function _toHtml()
    {
        $this->_is_widget = true;
        $categories = "";
        $searchCollection = "";
        if($this->_autosearchHelper->getConfig('general/show_filter_category')) {
            $rootCatId = $this->_storeManager->getStore()->getRootCategoryId();
            $maxLevel = $this->_autosearchHelper->getConfig('general/max_category_level');
            $categories = $this->getTreeCategories($rootCatId, 0, ' ', (int)$maxLevel);
        }

        $this->assign( "categories_links", $this->_categories_links);
        $this->assign( "categories", $categories );
        $this->assign( "limit_term" , $this->_autosearchHelper->getLimitTerm());
        $this->assign( "limit", $this->_autosearchHelper->getConfig('general/limit'));
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
            $template = 'Lof_Autosearch::widget/custom_widget.phtml';
            if($tmp_template = $this->getConfig("template")) {
                $template = $tmp_template;
            }
            $this->setTemplate($template);
        } 
        return parent::_toHtml();
    }

    /**
     * @return array
     */
    public function getTerms(){
        if($this->getConfig('enable_search_term')){
            $limit_term = $this->_autosearchHelper->getLimitTerm();
            $limit = $this->getConfig('limit_term', $limit_term);
            $storeId      = $this->_storeManager->getStore()->getId();
            $_suggestCollection = $this->_queryCollectionFactory->create();
            $_suggestCollection->setPopularQueryFilter($storeId)->setPageSize($limit)->getSelect()->order('num_results DESC');
            return $_suggestCollection;
        }
        return [];
    }
}
