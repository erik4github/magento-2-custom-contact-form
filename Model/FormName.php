<?php

namespace VendorName\ContactForms\Model;

class FormName
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Config constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getSenderName()
    {
        return $this->scopeConfig->getValue(
            'vendorname_contact_forms/general/form_name',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return string
     */
    public function getSenderEmail()
    {
        return $this->scopeConfig->getValue(
            'vendorname_contact_forms/general/form_email',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
