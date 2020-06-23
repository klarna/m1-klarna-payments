<?php
/**
 * This file is part of the Klarna Payments module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

class Klarna_Payments_Block_Mark extends Mage_Core_Block_Template
{
    protected $method_code;

    protected $title = '';
    protected $logo = '';

    /**
     * @param string $method_code
     */
    public function setMethodCode($method_code)
    {
        $this->method_code = $method_code;
    }

    protected function _toHtml()
    {
        /** @var Klarna_Payments_Model_Filter $filter */
        $filter = Mage::getModel('klarna_payments/filter');
        $filter->setLogo($this->logo);

        return $filter->filter($this->title . ' {{klarna}}');
    }

    /**
     * @return string
     */
    public function getMethodCode()
    {
        $this->method_code;
    }

    /**
     * @param string $value
     * @return $this
     */
    public function setTitle($value)
    {
        $this->title = $this->_addTitleStyles($value);
        return $this;
    }

    /**
     * @param string $title
     * @return string
     */
    protected function _addTitleStyles($title)
    {
        $style = 'padding-left:10px;';
        return sprintf('<span style="%s">%s</span>', $style, $title);
    }

    /**
     * @param string $logo
     * @return $this
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;
        return $this;
    }

    protected function _construct()
    {
        $this->setCacheLifetime(null);
        parent::_construct();
    }

}