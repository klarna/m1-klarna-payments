4.4.5 / 2020-05-29
==================

  * MAGE-2086 Add Oceania Support
  
4.4.4 / 2020-04-17
==================

  * MAGE-1990 Fix capture call when using FPT/Weee
  * MAGE-1856 Fix sending the email when the order has the state processing and Klarna was used.

4.4.3 / 2020-02-12
==================

  * MAGE-1425 Remove use of serialize/unserialize
  * MAGE-1641 Wrong link to Merchant Portal from Magento Admin

4.4.2 / 2019-07-02
==================

  * MAGE-479 Fix method not remaining selected with page refresh on OSC
  * MAGE-363 Fix issue with logging an error on first creation of Klarna session

4.4.1 / 2019-06-20
==================

  * MAGE-538 Make sure to use latest address changes from OSC when authorizing payment

4.4.0 / 2019-04-30
==================

  * MAGE 397 Switch places of payment text and logo for payment methods

4.3.2 / 2019-03-26
==================

  * MAGE-314 Update translation files
  * MAGE-510 Add upgrade script to fix the wrong saved payment method "klanra_payments" in the table "klarna_payments_quote"
  * MAGE-581 Fix typo in variable that breaks separate shipping address on OSC

4.3.1 / 2019-01-30
==================

  * MAGE-284 Fix issue with OSC and passing the value of the state/region to Klarna
  * MAGE-317 Update copyright info

4.3.0 / 2018-12-11
==================

  * MAGE-54 Add support for finalize call on native checkout
  * MAGE-74 Fix issue with blank values in DB and multiple rows added

4.2.0 / 2018-10-26
==================

  * PI-480 Add DOB to client update call

4.1.0 / 2018-08-24
==================

  * PI-402 Change conditional to better allow merchants to extend/override code
  * PPI-258 Add link to Merchant Portal
  * PPI-405 Added onboarding link
  * PPI-423 Add display of customer's selected payment method when viewing order

4.1.0 / 2018-08-08
==================

  * PI-402 Change conditional to better allow merchants to extend/override code
  * PPI-258 Add link to Merchant Portal
  * PPI-405 Add onboarding link.
  * PPI-423 Add display of payment method

4.0.0 / 2018-06-29
==================

  * PI-349 Encode json data to handle special char
  * PPI-354 Change to use payments endpoint and dynamic payment methods
  * PPI-370 OSC - iframe container shown without KP iframe loaded

3.0.2 / 2018-05-03
==================

  * Fix issues with OSC support

3.0.1 / 2018-03-15
==================

  * Fix issue with OSC displaying Klarna iframe

3.0.0 / 2018-03-12
==================

  * Add support for OSC
  * Add support for EMD data
  * Do not allow PII data to be shared for non-US countries
  * Add CHANGELOG.md
