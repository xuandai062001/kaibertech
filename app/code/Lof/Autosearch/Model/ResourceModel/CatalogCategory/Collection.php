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

namespace Lof\Autosearch\Model\ResourceModel\CatalogCategory;

/**
 * Search collection
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Category\Collection
{

     protected $_helperData;
     protected $_searchQuery;
     /**
     * Catalog resource helper
     *
     * @var \Magento\Catalog\Model\ResourceModel\Helper
     */
    protected $_resourceHelper;
    /**
     * Add search query filter
     *
     * @param string $query
     * @return $this
     */
    public function addSearchFilter($query)
    {
        $this->_searchQuery = $query;
        if(is_array($this->_searchQuery)) {
            foreach($this->_searchQuery as $search_query) {
                if(strlen($search_query) > 2){
                    $this->addAttributeToFilter(
                        array(
                            array('attribute' => 'name', 'like' => '%'.$search_query.'%'),
                            array('attribute' => 'url_key', 'like' => '%'.$search_query.'%')
                        )
                    );
                }
            }

        } else {
            $this->addAttributeToFilter(
                array(
                    array('attribute' => 'name', 'like' => '%'.$query.'%'),
                    array('attribute' => 'url_key', 'like' => '%'.$query.'%')
                )
            );
        }
        return $this;
    }
}
