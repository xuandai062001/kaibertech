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

namespace Lof\Autosearch\Block\Result;

class Cmspage extends \Magento\Framework\View\Element\Template
{

	/**
     * @var \Magento\Framework\Url\Helper\Data
     */
	protected $urlHelper;

    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    protected $formKey;

    /**
     * @param \Magento\Catalog\Block\Product\Context $context   
     * @param \Magento\Framework\Url\Helper\Data     $urlHelper 
     * @param \Magento\Framework\Data\Form\FormKey   $formKey   
     * @param array                                  $data      
     */
	public function __construct(
		\Magento\Catalog\Block\Product\Context $context,
		\Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\Data\Form\FormKey $formKey,
		array $data = []
		){	
		if(isset($data['template']) && $data['template']) {
			$this->setData("template", $data['template']);
		}
		parent::__construct($context, $data);
		$this->urlHelper = $urlHelper;
		$this->formKey   = $formKey;
	}


	/**
     * Rendering block content
     *
     * @return string
     */
	public function _toHtml() 
	{
		if($template = $this->getConfig("template")) {
			$this->setTemplate( $template );
		} else {
			$this->setTemplate( "Lof_Autosearch::result/cms_page.phtml" );
		}
		return parent::_toHtml();
	}

	/**
	 * get value of the extension's configuration
	 *
	 * @return string
	 */
	public function getConfig($key, $default = '')
	{
		if($this->hasData($key))
		{
			return $this->getData($key);
		}
		return $default;
	}

	public function getHelperData(){
		return $this->urlHelper;
	}

}
