<?php
/**
 * This file is part of the Klarna Core module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */

/**
 * Klarna api integration interface for purchases
 */
interface Klarna_Core_Model_Api_PurchaseApiInterface
{
    /**
     * Create or update a session
     *
     * @param string     $sessionId
     * @param bool|false $createIfNotExists
     * @param bool|false $updateAllowed
     *
     * @return Klarna_Core_Model_Api_Response
     */
    public function initKlarnaSession($sessionId = null, $createIfNotExists = false, $updateAllowed = false);

    /**
     * @return Klarna_Core_Model_Api_Response
     */
    public function createSession();

    /**
     * @param string $sessionId
     *
     * @return Klarna_Core_Model_Api_Response
     */
    public function updateSession($sessionId);

    /**
     * Get Klarna Reservation Id
     *
     * @return string
     */
    public function getReservationId();
}
