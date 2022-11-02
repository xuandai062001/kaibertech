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

use Lof\Autosearch\Api\Data\SearchResultInterface;


class SearchRepository implements SearchResultInterface
{
    protected $_search_result_items = null;
    /**
     * Get Search results list.
     * @return \Lof\Autosearch\Api\Data\ItemInterface
     */
    public function getItem(){
        return $this->_search_result_items;
    }

    /**
     * Set search results list.
     * @param \Lof\Autosearch\Api\Data\ItemInterface $item
     * @return $this
     */
    public function setItem($item){
        $this->_search_result_items = $item;
        return $this;
    }
}
