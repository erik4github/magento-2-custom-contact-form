<?php
namespace VendorName\ContactForms\Controller\FormName;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Mail\Template\TransportBuilder;
use VendorName\ContactForms\Model\FormName as ContactConfig;
use Psr\Log\LoggerInterface;

class FormName extends Action
{

    /**
     * Email template path
     */
    const XML_PATH_EMAIL_TEMPLATE = 'formname_email_template';

    /**
     * File upload size limit for this form
     * 2MB
     */
    const ATTACHMENT_SIZE_LIMIT = 2100000;

    /**
     * @var ContactConfig
     */
    protected $_contactConfig;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $resultFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $fileSystem;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * @var \Magento\Framework\File\UploaderFactory
     */
    protected $fileUploaderFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param ContactConfig $contactConfig
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\Controller\ResultFactory $resultFactory
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param \Magento\Framework\File\UploaderFactory $fileUploaderFactory
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     */

    public function __construct(
        Context $context,
        ContactConfig $contactConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\File\UploaderFactory $fileUploaderFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->_contactConfig = $contactConfig;
        $this->transportBuilder = $transportBuilder;
        $this->fileSystem = $fileSystem;
        $this->directoryList = $directoryList;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->resultFactory = $resultFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Send Custom Form Form Email
     *
     * @return void
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        $files = $this->getRequest()->getFiles();

        if (!$post || !$post['form-key']) {
            return $this->_redirect('*/*/');
        }

        if (!empty($post)) {
            try {

                // There's actually several more input arrays, which is the reasoning
                // for not just using DataObject and $postObject->setData($post);
                // As well as Magento's lack of support for nested variables 
                // like data.general-info.city in email templates

                $generalInfoFields = $post['general-info'];

                $billingFields = $post['billing'];

                if ($files['attachments'] && $files['attachments']['size'] > 0) {
                    if ($files['attachments']['size'] > self::ATTACHMENT_SIZE_LIMIT) {
                        $this->messageManager->addErrorMessage(__('File size too large.'));
                        return $this->_redirect('*/*/');
                    }
                    try {
                        $uploader = $this->fileUploaderFactory->create(['fileId' => 'attachments']);
                        $uploader->setAllowRenameFiles(true);
                        $uploader->setFilesDispersion(true);
                        $uploader->setAllowCreateFolders(true);
                        $uploader->setAllowedExtensions(['pdf']);

                        // save to TMP directory since they're unneeded once sent

                        $filePath = $this->fileSystem->getDirectoryRead(DirectoryList::TMP)->getAbsolutePath();

                        $result = $uploader->save($filePath);
                        $resultName = $uploader->getUploadedFileName();

                        $attachmentPath = $result['path'] . $result['file'];
                        $attachmentName = $result['name'];
                    } catch (\Exception $e) {
                        // there's also client side file validation for type and size
                        $this->messageManager->addErrorMessage(__('File format not supported.'));
                        $this->logger->error($e);
                        return $this->_redirect('*/*/');
                    }
                } else {
                    $attachmentPath = '';
                    $attachmentName = '';
                }

                $templateVars = [
                    'general_info' => $this->cleanInputArray($generalInfoFields),
                    'billing' => $this->cleanInputArray($billingFields),
                ];

                $senderName = $this->_contactConfig->getSenderName();
                $senderEmail = $this->_contactConfig->getSenderEmail();

                $sender = [
                    'name' => $senderName,
                    'email' => $senderEmail
                ];

                $templateOptions = [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId(),
                ];

                $transport = $this->transportBuilder
                    ->setTemplateIdentifier(self::XML_PATH_EMAIL_TEMPLATE)
                    ->setTemplateOptions($templateOptions)
                    ->setTemplateVars($templateVars)
                    ->setFrom($sender)
                    ->addTo($senderEmail)
                    ->addPDFAttachment($attachmentPath, $attachmentName)
                    ->getTransport();

                $transport->sendMessage();

                $this->messageManager->addSuccessMessage(__('Form submitted successfully!'));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addErrorMessage(__('An error occurred while processing your form. Please try again later.'));
            }
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->_redirect('*/*/'));

            return $resultRedirect;
        }
    }

    /**
     * @param array
     * @return string
     */
    private function cleanInputArray($arr)
    {
        $fieldsWithLineBreaks = [];
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $key => $value) {
                $value = $this->escapeHTML($value);
                $key = $this->cleanHTMLClassName($key);
                $fieldsWithLineBreaks[$key] = "$key: $value";
            }
            return implode("<br/>", $fieldsWithLineBreaks);
        }
    }

    /**
     * @param string
     * @return string
     */
    private function escapeHTML($input)
    {
        return htmlspecialchars(trim($input));
    }

    /**
     * Replace the dashes in HTML classnames with
     * an empty space for output in email
     * @param string
     * @return string
     */
    private function cleanHTMLClassName($input)
    {
        $input = ucwords($input, '-');
        return str_replace('-', ' ', $input);
    }
}
