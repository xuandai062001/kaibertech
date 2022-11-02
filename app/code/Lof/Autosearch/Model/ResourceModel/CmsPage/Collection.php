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

namespace Lof\Autosearch\Model\ResourceModel\CmsPage;

/**
 * Search collection
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends \Magento\Cms\Model\ResourceModel\Page\Collection
{

     protected $_helperData;
     protected $_searchQuery;
     /**
     * Catalog resource helper
     *
     * @var \Magento\Catalog\Model\ResourceModel\Helper
     */
    protected $_resourceHelper;

     public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \Lof\Autosearch\Helper\Data   $helperData
    ) {
        $this->_helperData = $helperData;
        $this->_resourceHelper = $resourceHelper;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $storeManager, $metadataPool, null, null);
    }

    /**
     * Add search query filter
     *
     * @param string $query
     * @return $this
     */
    public function addSearchFilter($query)
    {
        $this->_searchQuery = $query;
        $likeOptions = ['position' => 'any'];
        $sql = "";
        if(is_array($this->_searchQuery)) {
            $wheres = [];
            $wheres['title'] = [];
            $wheres['meta_keywords'] = [];
            $wheres['meta_description'] = [];
            $wheres['content_heading'] = [];
            $wheres['content'] = [];
            $wheres['meta_title'] = [];
            foreach($this->_searchQuery as $search_query) {
                if(strlen($search_query) > 2){
                    $wheres['title'][] = $this->_resourceHelper->getCILike('main_table.title', $search_query, $likeOptions);
                    $wheres['meta_keywords'][] = $this->_resourceHelper->getCILike('main_table.meta_keywords', $search_query, $likeOptions);
                    $wheres['meta_description'][] = $this->_resourceHelper->getCILike('main_table.meta_description', $search_query, $likeOptions);
                    $wheres['content_heading'][] = $this->_resourceHelper->getCILike('main_table.content_heading', $search_query, $likeOptions);
                    $wheres['content'][] = $this->_resourceHelper->getCILike('main_table.content', $search_query, $likeOptions);
                    $wheres['meta_title'][] = $this->_resourceHelper->getCILike('main_table.meta_title', $search_query, $likeOptions);
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
            if($wheres['content_heading']) {
                $tmp_wheres[] = implode( " AND ", $wheres['content_heading']);
            }
            if($wheres['content']) {
                $tmp_wheres[] = implode( " AND ", $wheres['content']);
            }
            if($wheres['meta_title']) {
                $tmp_wheres[] = implode( " AND ", $wheres['meta_title']);
            }
            $sql = implode(" OR ", $tmp_wheres);
        } else {
            $wheres = [];
            $wheres[] = $this->_resourceHelper->getCILike('main_table.title', $this->_searchQuery, $likeOptions);
            $wheres[] = $this->_resourceHelper->getCILike('main_table.meta_keywords', $this->_searchQuery, $likeOptions);
            $wheres[] = $this->_resourceHelper->getCILike('main_table.meta_description', $this->_searchQuery, $likeOptions);
            $wheres[] = $this->_resourceHelper->getCILike('main_table.content_heading', $this->_searchQuery, $likeOptions);
            $wheres[] = $this->_resourceHelper->getCILike('main_table.content', $this->_searchQuery, $likeOptions);
            $wheres[] = $this->_resourceHelper->getCILike('main_table.meta_title', $this->_searchQuery, $likeOptions);

            $sql = implode(" OR ", $wheres);
        }
        if($sql) {
            $this->getSelect()->where( $sql );
        }
        return $this;
    }
}
