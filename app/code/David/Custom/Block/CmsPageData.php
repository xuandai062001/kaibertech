<?php
namespace David\Custom\Block;

class CmsPageData extends \Magento\Framework\View\Element\Template
{
    protected $_urlInterface;

    public function __construct(
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Cms\Api\PageRepositoryInterface $pageRepositoryInterface,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\UrlInterface $urlInterface,
        array $data = []
    ) {
        $this->pageRepositoryInterface = $pageRepositoryInterface;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->categoryRepository = $categoryRepository;
        parent::__construct($context, $data);
        $this->_urlInterface = $urlInterface;
    }

    public function getCategoryLinks($id)
        {
            $categoryId = $id; //Parent ID of the Category which you want to move to the header
            $category = $this->categoryRepository->get($categoryId);
            return $category;
        }

  	/* Get Pages Collection from site. */
    public function getPages() {
        $searchCriteria = $searchCriteria = $this->searchCriteriaBuilder->create();
        $pages = $this->pageRepositoryInterface->getList($searchCriteria)->getItems();
        return $pages;
    }
    public function getUrlInterfaceData()
    {
        return $this->_urlInterface->getUrl();
    }

    public function getCurrentUrl(){
        return $this->_urlInterface->getCurrentUrl();
    }
}