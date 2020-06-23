<?php
/**
 * This file is part of the Klarna Payments module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();


$installer->getConnection()
    ->addColumn(
        $installer->getTable('klarna_payments/quote'),
        'payment_method_categories',
        array(
            'TYPE'     => Varien_Db_Ddl_Table::TYPE_TEXT,
            'COMMENT'  => 'Payment Method Categories',
            'LENGTH'   => 4096,
            'NULLABLE' => false
        )
    );

$installer->run("
    UPDATE " . $installer->getTable('sales_flat_order_payment') . " 
    SET method = 'klarna_payments'
    WHERE method LIKE 'klarna_%' AND method != 'klarna_kco'
");

$installer->run("
    UPDATE " . $installer->getTable('klarna_payments_quote') . " 
    SET payment_method = 'klarna_payments'
    WHERE payment_method LIKE 'klarna_%' 
");

$installer->run("
    DELETE FROM " . $installer->getTable('core_config_data') . "
    WHERE path = 'payment/klarna_payments/title'
    LIMIT 1
");

$installer->run("
    DELETE FROM " . $installer->getTable('core_config_data') . "
    WHERE path = 'payment/klarna_payments/force_default'
    LIMIT 1
");

$installer->getConnection()->update($installer->getTable('klarna_payments/quote'), array('payment_method_categories' => '[]'));

$installer->endSetup();
