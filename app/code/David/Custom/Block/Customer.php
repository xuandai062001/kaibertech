<?php
namespace David\Custom\Block;

class Customer extends \Magento\Framework\View\Element\Template
{ 
    protected $_customerSession;    // don't name this `$_session` since it is already used in \Magento\Customer\Model\Session and your override would cause problems
    protected $_urlInterface;

    /**
     * @var Magento\Customer\Model\SessionFactory
     */
    protected $customerSessionFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Customer\Model\SessionFactory $customerSessionFactory
    ) {
        $this->_customerSession = $session;
        $this->_urlInterface = $urlInterface;
        $this->customerSessionFactory = $customerSessionFactory;
        parent::__construct($context);
    }

    public function isCustomerLoggedIn()
    {
        $customerSession = $this->customerSessionFactory->create();
        return $customerSession->isLoggedIn();
    }


    public function checkLogin(){
        if ($this->_customerSession->isLoggedIn()) {
            // Customer is logged in 
            return true;
        } else {
            // Customer is not logged in
            return false;
        }
    }
    public function getUrlInterfaceData()
    {
        return $this->_urlInterface->getUrl();
    }
}