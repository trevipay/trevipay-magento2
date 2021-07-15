<?php
namespace TreviPay\TreviPayMagento\Model\Config\Backend;

use TreviPay\TreviPayMagento\Model\ConfigProvider;

class Image extends \Magento\Config\Model\Config\Backend\Image
{
    /**
     * Upload max file size in kilobytes
     *
     * @var int
     */
    protected $_maxFileSize = 20480;

    /**
     * Return path to directory for upload file
     *
     * @return string
     * @throw \Magento\Framework\Exception\LocalizedException
     */
    protected function _getUploadDir()
    {
        return $this->_mediaDirectory->getAbsolutePath($this->_appendScopeInfo(ConfigProvider::IMAGES_FOLDER));
    }

    /**
     * Makes a decision about whether to add info about the scope.
     *
     * @return boolean
     */
    protected function _addWhetherScopeInfo()
    {
        return true;
    }

    /**
     * Getter for allowed extensions of uploaded files.
     *
     * @return string[]
     */
    protected function getAllowedExtensions()
    {
        return ['jpg', 'jpeg', 'gif', 'png'];
    }

    /**
     * @return string|null
     */
    protected function getTmpFileName()
    {
        return is_array($this->getValue()) ? $this->getValue()['tmp_name'] : null;
    }

    /**
     * Save uploaded file before saving config value
     *
     * Save changes and delete file if "delete" option passed
     *
     * @return $this
     */
    public function beforeSave()
    {
        $value = $this->getValue();
        $deleteFlag = is_array($value) && !empty($value['delete']);
        $fileTmpName = $this->getTmpFileName();

        if ($this->getOldValue() && ($fileTmpName || $deleteFlag)) {
            $this->_mediaDirectory->delete(ConfigProvider::IMAGES_FOLDER . '/' . $this->getOldValue());
        }
        return parent::beforeSave();
    }
}
