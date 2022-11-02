<?php


namespace Lof\Autosearch\Api;

interface AutosearchManagementInterface
{
    /**
     * Retrieve Search Result Items matching the specified criteria.
     * @param string $query_text
     * @param string $category_id
     * @param string $store_id
     * @param string $limit_terms
     * @return \Lof\Autosearch\Api\Data\SearchResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAutosearch(
        $query_text,
        $category_id,
        $store_id,
        $limit_terms
    );
}
