<?php
/**
 * This file is part of the Klarna Payments module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

/**
 * Klarna Payments text filter
 */
class Klarna_Payments_Model_Filter
{

    protected $logo = '';

    /**
     * Filter string to replace template tags with values
     *
     * @param string $value
     *
     * @return string
     * @throws Exception
     */
    public function filter($value)
    {
        if (preg_match_all(Varien_Filter_Template::CONSTRUCTION_PATTERN, $value, $constructions, PREG_SET_ORDER)) {
            foreach ($constructions as $index => $construction) {
                $method = $construction[1] . 'Directive';
                $replacedValue = $value;
                if (method_exists($this, $method)) {
                    $replacedValue = $this->$method($construction);
                }

                $value = str_replace($construction[0], $replacedValue, $value);
            }
        }

        return $value;
    }

    public function setLogo($logo)
    {
        $this->logo = $logo;
        return $this;
    }

    /**
     * Insert the Klarna Logo with parameters
     *
     * @param array $construction
     *
     * @return string
     */
    public function klarnaDirective($construction)
    {
        return $this->doFilter($construction, $this->logo);
    }

    /**
     * Insert the appropriate logo with parameters
     *
     * @param array  $construction
     * @param string $logo
     *
     * @return string
     */
    protected function doFilter($construction, $logo)
    {
        $params = $this->_getIncludeParameters($construction[2]);
        $width = isset($params['width']) ? (int)$params['width'] : 50;
        $style = isset($params['style']) ? $params['style'] : 'display: inline-block; vertical-align:text-top';
        $locale = Mage::helper('klarna_payments')->getLocale();
        $src = sprintf($logo, $locale) . '?width=' . (2 * $width);

        return sprintf('<img width="%d" style="%s" src="%s" align="middle" />', $width, $style, $src);
    }

    /**
     * Get parameters from a directive
     *
     * @param string $value
     *
     * @return array
     */
    protected function _getIncludeParameters($value)
    {
        $tokenizer = new Varien_Filter_Template_Tokenizer_Parameter();
        $tokenizer->setString($value);

        return $tokenizer->tokenize();
    }
}
