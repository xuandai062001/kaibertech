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

namespace Lof\Autosearch\Block;

class Item extends \Magento\Catalog\Block\Product\AbstractProduct implements \Magento\Widget\Block\BlockInterface
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
			$this->setTemplate( "Lof_Autosearch::result_item.phtml" );
		}
		return parent::_toHtml();
	}

	public function getFormKey()
	{
		return $this->formKey->getFormKey();
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

	/**
	 * @param  \Magento\Catalog\Model\Product $product 
	 * @return [type]                                  
	 */
	public function getAddToCartPostParams(\Magento\Catalog\Model\Product $product)
	{
		$url = $this->getAddToCartUrl($product);
		return [
		'action' => $url,
		'data' => [
		'product' => $product->getEntityId(),
		\Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED =>
		$this->urlHelper->getEncodedUrl($url),
		]
		];
	}

	public function getHelperData(){
		return $this->urlHelper;
	}

}
