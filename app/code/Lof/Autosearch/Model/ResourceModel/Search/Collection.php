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

namespace Lof\Autosearch\Model\ResourceModel\Search;

use Magento\Catalog\Model\Product;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\Api\Search\SearchResultFactory;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Search\Adapter\Mysql\TemporaryStorage;
use Magento\Framework\Search\Request\EmptyRequestDataException;
use Magento\Framework\Search\Request\NonExistingRequestNameException;
use Magento\Catalog\Model\ResourceModel\Product\Collection\ProductLimitationFactory;
/**
 * Search collection
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection implements \Magento\Search\Model\SearchCollectionInterface
{
    /**
     * Attribute collection
     *
     * @var array
     */
    protected $_attributesCollection;

    /**
     * Catalog Product Flat is enabled cache per store
     *
     * @var array
     */
    protected $_flatEnabled = [];

    /**
     * Search query
     *
     * @var string
     */
    protected $_searchQuery;

    /**
     * Attribute collection factory
     *
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    protected $_attributeCollectionFactory;

    protected $_helperData;

    protected $_current_store_id = null;

    /**
     * Collection constructor.
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Eav\Model\EntityFactory $eavEntityFactory
     * @param \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Validator\UniversalFactory $universalFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Url $catalogUrl
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Customer\Api\GroupManagementInterface $groupManagement
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory
     * @param \Lof\Autosearch\Helper\Data $helperData
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory,
        \Lof\Autosearch\Helper\Data   $helperData
    ) {
        $this->_attributeCollectionFactory = $attributeCollectionFactory;
        $this->_helperData = $helperData;

        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $storeManager,
            $moduleManager,
            $catalogProductFlatState,
            $scopeConfig,
            $productOptionFactory,
            $catalogUrl,
            $localeDate,
            $customerSession,
            $dateTime,
            $groupManagement,
            null
        );
    }
    public function setStoreToFilter($store_id =0){
        $this->_current_store_id = (int)$store_id;
        return $this;
    }
    /**
     * Add search query filter
     *
     * @param string $query
     * @param mixed $collection
     * @return $this|$collection
     */
    public function addSearchFilter($query, $collection = null)
    {
        $this->_searchQuery = $query;
        if(!$collection) {
            $collection = $this;
        }
        $collection->addFieldToFilter('entity_id', ['in' => new \Zend_Db_Expr($this->_getSearchEntityIdsSql($query))]);
        
        return $collection;
    }
    public function getFlatState()
    {
        return $this->_catalogProductFlatState;
    }

    public function isEnabledFlat()
    {
        if (!isset($this->_flatEnabled[$this->getStoreId()])) {
            $this->_flatEnabled[$this->getStoreId()] = $this->getFlatState()->isAvailable();
        }
        return $this->_flatEnabled[$this->getStoreId()];
    }

    /**
     * Add backend search query filter (search by all stores)
     *
     * @param string $query
     * @return $this
     */
    public function addBackendSearchFilter($query)
    {
        $this->_searchQuery = $query;
        $this->addFieldToFilter(
            'entity_id',
            ['in' => new \Zend_Db_Expr($this->_getSearchEntityIdsSql($query, false))]
        );
        return $this;
    }

    /**
     * Retrieve collection of all attributes
     *
     * @return \Magento\Framework\Data\Collection\AbstractDb
     */
    protected function _getAttributesCollection()
    {
        if (!$this->_attributesCollection) {
            $this->_attributesCollection = $this->_attributeCollectionFactory->create()->load();

            foreach ($this->_attributesCollection as $attribute) {
                $attribute->setEntity($this->getEntity());
            }
        }
        return $this->_attributesCollection;
    }

    /**
     * Check attribute is Text and is Searchable
     *
     * @param \Magento\Catalog\Model\Entity\Attribute $attribute
     * @return boolean
     */
    protected function _isAttributeTextAndSearchable($attribute)
    {
        if ($attribute->getIsSearchable() && !in_array(
            $attribute->getFrontendInput(),
            ['select', 'multiselect']
        ) && (in_array(
            $attribute->getBackendType(),
            ['varchar', 'text']
        ) || $attribute->getBackendType() == 'static')
        ) {
            $attribute_code = $attribute->getAttributeCode();
            if($this->checkAvaialbleAttributeCode($attribute_code)){
                return true;
            }
        }
        return false;
    }


    /**
     * Check attributes has options and searchable
     *
     * @param \Magento\Catalog\Model\Entity\Attribute $attribute
     * @return boolean
     */
    protected function _hasAttributeOptionsAndSearchable($attribute)
    {
        if ($attribute->getIsSearchable() && in_array($attribute->getFrontendInput(), ['select', 'multiselect'])
        ) {
            return true;
        }

        return false;
    }

     /**
     * Retrieve SQL for search entities
     *
     * @param unknown_type $query
     * @return string
     */
    protected function _getSearchEntityIdsSqlFlat($query)
    {
        $wheres = [];
        $selects = [];
        /* @var $resHelper Mage_Core_Model_Resource_Helper_Abstract */
        $likeOptions = array('position' => 'any');
        /**
         * Collect tables and attribute ids of attributes with string values
         */
        foreach ($this->_getAttributesCollection() as $attribute) {
            /** @var Mage_Catalog_Model_Entity_Attribute $attribute */
            $attributeCode = $attribute->getAttributeCode();
            if ($this->_isAttributeTextAndSearchable($attribute)) {
                if(is_array($this->_searchQuery)) {
                    $tmp_where = array();
                    foreach($this->_searchQuery as $key) {
                        $tmp_where[] = $this->_resourceHelper->getCILike($attributeCode, $key, $likeOptions);
                    }
                    $wheres[] = implode(" AND ", $tmp_where);
                } else {
                    $wheres[] = $this->_resourceHelper->getCILike($attributeCode, $this->_searchQuery, $likeOptions);
                }
                
            }
        }

        $sql = $this->_getSearchInOptionSqlFlat($query);
        
        if($sql && $wheres) {
            $sql = implode(" OR ", $wheres)." OR ".$sql;
        } elseif($wheres) {
            $sql = implode(" OR ", $wheres);
        }
        $this->getSelect()->where( $sql );
        return $this;
    }

     /**
     * Retrieve SQL for search entities by option
     *
     * @param unknown_type $query
     * @return string
     */
    protected function _getSearchInOptionSqlFlat($query)
    {   
        $attributeIds    = [];
        $attributeTables = [];
        $storeId = (int)$this->getStoreId();

        /**
         * Collect attributes with options
         */
        foreach ($this->_getAttributesCollection() as $attribute) {
            if ($this->_hasAttributeOptionsAndSearchable($attribute)) {
                //$attributeTables[$attribute->getFrontendInput()] = $attribute->getBackend()->getTable();
                $attributeIds[] = $attribute->getId();
            }
        }

        if (empty($attributeIds)) {
            return false;
        }

        $optionTable      = $this->_resource->getTableName('eav_attribute_option');
        $optionValueTable = $this->_resource->getTableName('eav_attribute_option_value');
        $attributesTable  = $this->_resource->getTableName('eav_attribute');


        /**
         * Select option Ids
         */

        $select = $this->getConnection()->select()
            ->from(array('d'=>$attributesTable),
                   array('attribute_code'))
            ->where('d.attribute_id IN (?)', $attributeIds);

        $options = $this->getConnection()->fetchAll($select);

        if (empty($options)) {
            return false;
        }

        $wheres = array();

        foreach($options as $key => $value) {
            if($value['attribute_code'] != "status") {
                if(is_array($this->_searchQuery)) {
                    $tmp_where = array();
                    foreach($this->_searchQuery as $key) {
                        $tmp_where[] = $this->_resourceHelper->getCILike($value['attribute_code']."_value", $key, array('position' => 'any'));
                    }
                    $wheres[] = implode(" AND ", $tmp_where);
                } else {
                    $wheres[] = $this->_resourceHelper->getCILike($value['attribute_code']."_value", $this->_searchQuery, array('position' => 'any'));
                }
            }
        }
        $sql = implode(" OR ", $wheres);
        return $sql;
    }

    /**
     * Retrieve SQL for search entities
     *
     * @param mixed $query
     * @param bool $searchOnlyInCurrentStore Search only in current store or in all stores
     * @return string
     */
    protected function _getSearchEntityIdsSql($query, $searchOnlyInCurrentStore = true)
    {
        $tables = [];
        $selects = [];
        $likeOptions = ['position' => 'any'];

        $is_flat_catalog = $this->_helperData->getCatalogConfig("frontend/flat_catalog_product", $this->_current_store_id);

        //$is_flat_catalog = false;
        /**
         * Collect tables and attribute ids of attributes with string values
         */
        foreach ($this->_getAttributesCollection() as $attribute) {
            /** @var \Magento\Catalog\Model\Entity\Attribute $attribute */
            $attributeCode = $attribute->getAttributeCode();
            if($is_flat_catalog) {
                $attribute->setBackendType(\Magento\Eav\Model\Entity\Attribute::TYPE_STATIC);
            }
            
            if ($this->_isAttributeTextAndSearchable($attribute)) {
                if($is_flat_catalog) {
                    $table = $attribute->getBackendTable();
                    $prefix_table = $this->_resource->getTableName($table);
                    $check_table = $this->_resource->getTableName('catalog_product_entity');
                    if($prefix_table == $check_table && $this->_current_store_id){
                        $table = $this->_resource->getTableName("catalog_product_flat_".(int)$this->_current_store_id);
                    }
                } else {
                    $table = $attribute->getBackend()->getTable();
                }

                if (!isset($tables[$table]) && $attribute->getBackendType() != 'static') {
                    $tables[$table] = [];
                }
                if ($attribute->getBackendType() == 'static') {
                    if(is_array($this->_searchQuery)) {
                        $select = $this->getConnection()->select()->from(
                            $table,
                            'entity_id'
                        );
                        foreach($this->_searchQuery as $search_query) {
                            $select->where(
                                $this->_resourceHelper->getCILike($attributeCode, $search_query, $likeOptions)
                            );
                        }
                        $selects[] = $select;
                    } else {
                        $selects[] = $this->getConnection()->select()->from(
                            $table,
                            'entity_id'
                        )->where(
                            $this->_resourceHelper->getCILike($attributeCode, $this->_searchQuery, $likeOptions)
                        );
                    }
                } else {
                    $tables[$table][] = $attribute->getId();
                }
            }
        }

        if ($searchOnlyInCurrentStore) {
            $joinCondition = $this->getConnection()->quoteInto(
                't1.entity_id = t2.entity_id AND t1.attribute_id = t2.attribute_id AND t2.store_id = ?',
                $this->getStoreId()
            );
        } else {
            $joinCondition = 't1.entity_id = t2.entity_id AND t1.attribute_id = t2.attribute_id';
        }

        $ifValueId = $this->getConnection()->getIfNullSql('t2.value', 't1.value');
        if($tables) {
        foreach ($tables as $table => $attributeIds) {
            $select = $this->getConnection()->select()->from(
                ['t1' => $table],
                'entity_id'
            )->joinLeft(
                ['t2' => $table],
                $joinCondition,
                []
            )->where(
                't1.attribute_id IN (?)',
                $attributeIds
            )->where(
                't1.store_id = ?',
                0
            );
            if(is_array($this->_searchQuery)) {
                foreach($this->_searchQuery as $search_query) {
                    $select->where(
                        $this->_resourceHelper->getCILike($ifValueId, $search_query, $likeOptions)
                    );
                }
            } else {
                $select->where(
                    $this->_resourceHelper->getCILike($ifValueId, $this->_searchQuery, $likeOptions)
                );
            }
           
            $selects[] = $select;
        }
    }

        $sql = $this->_getSearchInOptionSql($query);
        if ($sql) {
            $selects[] = "SELECT * FROM ({$sql}) AS inoptionsql"; // inherent unions may be inside
        }

        $sql = $this->getConnection()->select()->union($selects, \Magento\Framework\DB\Select::SQL_UNION_ALL);
        
        return $sql;
    }

    /**
     * Retrieve SQL for search entities by option
     *
     * @param mixed $query
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getSearchInOptionSql($query)
    {
        $attributeIds = [];
        $attributeTables = [];
        $is_flat_catalog = $this->_helperData->getCatalogConfig("frontend/flat_catalog_product", $this->_current_store_id);
        //$is_flat_catalog = false;
        $storeId = (int)$this->getStoreId();

        /**
         * Collect attributes with options
         */
        foreach ($this->_getAttributesCollection() as $attribute) {
            if ($this->_hasAttributeOptionsAndSearchable($attribute)) {
                if($is_flat_catalog) {
                    $attribute->setBackendType(\Magento\Eav\Model\Entity\Attribute::TYPE_STATIC);
                } else {
                    $attributeTables[$attribute->getFrontendInput()] = $attribute->getBackend()->getTable();
                }
                
                $attributeIds[] = $attribute->getId();
            }
        }
        if (empty($attributeIds)) {
            return false;
        }

        $optionTable = $this->_resource->getTableName('eav_attribute_option');
        $optionValueTable = $this->_resource->getTableName('eav_attribute_option_value');
        $attributesTable = $this->_resource->getTableName('eav_attribute');

        /**
         * Select option Ids
         */
        $ifStoreId = $this->getConnection()->getIfNullSql('s.store_id', 'd.store_id');
        $ifValue = $this->getConnection()->getCheckSql('s.value_id > 0', 's.value', 'd.value');
        $select = $this->getConnection()->select()->from(
            ['d' => $optionValueTable],
            ['option_id', 'o.attribute_id', 'store_id' => $ifStoreId, 'a.frontend_input']
        )->joinLeft(
            ['s' => $optionValueTable],
            $this->getConnection()->quoteInto('s.option_id = d.option_id AND s.store_id=?', $storeId),
            []
        )->join(
            ['o' => $optionTable],
            'o.option_id=d.option_id',
            []
        )->join(
            ['a' => $attributesTable],
            'o.attribute_id=a.attribute_id',
            []
        )->where(
            'd.store_id=0'
        )->where(
            'o.attribute_id IN (?)',
            $attributeIds
        );
        if(is_array($this->_searchQuery)) {
            foreach($this->_searchQuery as $search_query){
                $select->where(
                    $this->_resourceHelper->getCILike($ifValue, $search_query, ['position' => 'any'])
                );
            }
        } else {
            $select->where(
                $this->_resourceHelper->getCILike($ifValue, $this->_searchQuery, ['position' => 'any'])
            );
        }
        

        $options = $this->getConnection()->fetchAll($select);
        if (empty($options)) {
            return false;
        }

        // build selects of entity ids for specified options ids by frontend input
        $sql = '';
        $selects = [];
        foreach (['select' => 'eq', 'multiselect' => 'finset'] as $frontendInput => $condition) {
            if ($attributeTables && isset($attributeTables[$frontendInput])) {
                $where = [];
                foreach ($options as $option) {
                    if ($frontendInput === $option['frontend_input']) {
                        $findSet = $this->getConnection()->prepareSqlCondition(
                            'value',
                            [$condition => $option['option_id']]
                        );
                        $whereCond = "(attribute_id=%d AND store_id=%d AND {$findSet})";
                        $where[] = sprintf($whereCond, $option['attribute_id'], $option['store_id']);
                    }
                }
                if ($where) {
                    $selects[$frontendInput] = (string)$this->getConnection()->select()->from(
                        $attributeTables[$frontendInput],
                        'entity_id'
                    )->where(
                        implode(' OR ', $where)
                    );
                }
            }
        }
        if($selects) {
            $sql = $this->getConnection()->select()->union($selects, \Magento\Framework\DB\Select::SQL_UNION_ALL);
        }
        return $sql;
    }

    public function checkAvaialbleAttributeCode($attributeCode) {
        $result = true;
        $not_accept_autosearch = ['price','special_price','brand_id'];
        if(in_array($attributeCode, $not_accept_autosearch)){
            $result = false;
        }
        return $result;
    }
}
