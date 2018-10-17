<?php

namespace VendorName\ContactForms\Block;

class FormName extends \Magento\Framework\View\Element\Template
{

	/**
	 * @var \Magento\Directory\Model\ResourceModel\Country\CollectionFactory
	 */
	protected $_countryCollectionFactory;

	/**
	 * CreditApp Form Constructor
	 *
	 * @param \Magento\Framework\View\Element\Template\Context $context
	 * @param \Magento\Framework\Data\Form\FormKey $formKey
	 * @param \Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory
	 * @param array $data
	 */
	public function __construct(
		\Magento\Backend\Block\Template\Context $context,
		\Magento\Framework\Data\Form\FormKey $formKey,
		\Magento\Directory\Model\ResourceModel\Country\CollectionFactory $countryCollectionFactory,
		array $data = []
	) {
		parent::__construct($context, $data);
		$this->formKey = $formKey;
		$this->_countryCollectionFactory = $countryCollectionFactory;
	}

	/**
	 * Get form action URL
	 *
	 * @return string
	 */
	public function getFormAction()
	{
		return '/forms/creditapp/creditapp';
	}

	/**
	 * Get form key
	 * 
	 * @return string 
	 */
	public function getFormKey()
	{
		return $this->formKey->getFormKey();
	}

	/**
	 * @param null|string $defValue
	 * @param string $name
	 * @param string $id
	 * @param string $title
	 * @return string
	 */
	public function getCountryHtmlSelect($defValue = null, $name = 'country', $id = 'country', $class='country', $title = 'Country')
	{
		$options = $this->getCountryCollection()
			->setForegroundCountries($this->getTopDestinations())
			->toOptionArray();

		$options = $this->swapCountryLabels($options);

		$html = $this->getLayout()->createBlock(
			'Magento\Framework\View\Element\Html\Select'
		)->setName(
			$name
		)->setId(
			$id
		)->setClass(
			$class
		)->setTitle(
			__($title)
		)->setValue(
			$defValue
		)->setOptions(
			$options
		)->setExtraParams(
			'data-validate="{\'validate-select\':true}"'
		)->getHtml();

		return $html;
	}

	/**
	 * Takes toOptionArray() and returns an array with the values as the full country names
	 * 
	 * @param array $optionsArray
	 * @return array
	 */
	public function swapCountryLabels($optionsArray)
	{
		foreach ($optionsArray as $index => $array) {
			if (array_key_exists('label', $array)) {
				$optionsArray[$index]['label'] = $array['label'];
				$optionsArray[$index]['value'] = $array['label'];
			}
		}
		return $optionsArray;
	}

	/**
	 * @return \Magento\Directory\Model\ResourceModel\Country\Collection
	 */
	public function getCountryCollection()
	{
		$collection = $this->getData('country_collection');
		if ($collection === null) {
			$collection = $this->_countryCollectionFactory->create();
			$this->setData('country_collection', $collection);
		}
		return $collection;
	}
}
