<?php

namespace VendorName\ContactForms\Model\Mail\Template;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    public function addPDFAttachment($file, $name)
    {
        if (!empty($file) && file_exists($file)) {
            $this->message->createAttachment(
                file_get_contents($file),
                'application/pdf',
                \Zend_Mime::DISPOSITION_ATTACHMENT,
                \Zend_Mime::ENCODING_BASE64,
                $name
            );
        }
        return $this;
    }
}
