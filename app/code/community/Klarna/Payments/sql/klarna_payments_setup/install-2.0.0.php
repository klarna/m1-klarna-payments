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
    ->newTable($installer->getTable('klarna_payments/quote'))
    ->addColumn(
        'payments_quote_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary'  => true,
        ), 'Payments Id'
    )
    ->addColumn('session_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Session Id')
    ->addColumn('client_token', Varien_Db_Ddl_Table::TYPE_TEXT, '64k', array(), 'Client Token')
    ->addColumn('authorization_token', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Authorization Token')
    ->addColumn(
        'is_active', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable' => false,
        'default'  => '0',
        ), 'Is Active'
    )
    ->addColumn(
        'quote_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Quote Id'
    )
    ->addForeignKey(
        $installer->getFkName('klarna_payments/quote', 'quote_id', 'sales/quote', 'entity_id'),
        'quote_id', $installer->getTable('sales/quote'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Klarna Payments Quote');
$installer->getConnection()->createTable($table);

$installer->endSetup();
