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

/**
 * Create table 'klarna_payments/quote'
 */
$table = $installer->getConnection()
                   ->addColumn(
                       $installer->getTable('klarna_payments/quote'),
                       'payment_method',
                       array(
                           'TYPE'     => Varien_Db_Ddl_Table::TYPE_TEXT,
                           'COMMENT'  => 'Payment Method',
                           'LENGTH'   => 255,
                           'DEFAULT'  => 'klarna_payments',
                           'NULLABLE' => false
                       )
                   );
$installer->getConnection()->update($installer->getTable('klarna_payments/quote'), array('payment_method' => 'klarna_payments'));

$installer->endSetup();
