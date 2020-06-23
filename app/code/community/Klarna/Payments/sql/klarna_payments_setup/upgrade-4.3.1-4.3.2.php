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

$installer->getConnection()->update(
    $installer->getTable('klarna_payments/quote'),
    array('payment_method' => 'klarna_payments'),
    'payment_method = "klanra_payments"'
);

$installer->endSetup();
