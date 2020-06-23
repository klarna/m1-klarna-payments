<?php
/**
 * This file is part of the Klarna Core module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

/** @var $installer Mage_Sales_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();
/**
 * Create table 'klarna_core/order'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('klarna_core/order'))
    ->addColumn(
        'klarna_order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity' => true,
        'unsigned' => true,
        'nullable' => false,
        'primary'  => true,
        ), 'Order Id'
    )
    ->addColumn('session_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Session Id')
    ->addColumn('reservation_id', Varien_Db_Ddl_Table::TYPE_TEXT, 255, array(), 'Reservation Id')
    ->addColumn(
        'order_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned' => true,
        'nullable' => false,
        ), 'Order Id'
    )
    ->addColumn(
        'is_acknowledged', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'nullable' => false,
        'default'  => '0',
        ), 'Is Acknowledged'
    )
    ->addForeignKey(
        $installer->getFkName('klarna_core/order', 'order_id', 'sales/order', 'entity_id'),
        'order_id', $installer->getTable('sales/order'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE
    )
    ->setComment('Klarna Order');
$installer->getConnection()->createTable($table);

$installer->endSetup();
