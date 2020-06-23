/**
 * This file is part of the Klarna Core module
 *
 * (c) Klarna Bank AB (publ)
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 */


function updateShippingAddressForKlarna(klarnaOrderUpdateData, storedData) {
    var storeDataValue = false;
    if (storedData) {
        storeDataValue = JSON.parse(decodeURIComponent(storedData.getValue()));
    }
    var address_list_visible = false,
        shipping_city = $('shipping:city'),
        shipping_country = $('shipping:country_id'),
        shipping_firstname = $('shipping:firstname'),
        shipping_lastname = $('shipping:lastname'),
        shipping_email = $('shipping:email'),
        shipping_telephone = $('shipping:telephone'),
        shipping_postcode = $('shipping:postcode'),
        shipping_region = $('shipping:region_id'),
        shipping_street_address_1 = $('shipping:street1'),
        shipping_street_address_2 = $('shipping:street2');

    var shipping_address_list = $('shipping_address_list');
    if (shipping_address_list !== undefined && shipping_address_list !== null) {
        if (shipping_address_list.visible()) {
            address_list_visible = true;
        }
    }

    if (shipping_city && address_list_visible) {
        klarnaOrderUpdateData.shipping_address.city = shipping_city.getValue();
    } else if (storeDataValue) {
        klarnaOrderUpdateData.shipping_address.city = storeDataValue.shipping_address.city;
    }

    if (shipping_firstname && address_list_visible) {
        klarnaOrderUpdateData.shipping_address.given_name = shipping_firstname.getValue();
    } else if (storeDataValue) {
        klarnaOrderUpdateData.shipping_address.given_name = storeDataValue.shipping_address.given_name;
    }

    if (shipping_lastname && address_list_visible) {
        klarnaOrderUpdateData.shipping_address.family_name = shipping_lastname.getValue();
    } else if (storeDataValue) {
        klarnaOrderUpdateData.shipping_address.family_name = storeDataValue.shipping_address.family_name;
    }

    if (shipping_country && address_list_visible) {
        klarnaOrderUpdateData.shipping_address.country = shipping_country.getValue();
    } else if (storeDataValue) {
        klarnaOrderUpdateData.shipping_address.country = storeDataValue.shipping_address.country;
    }

    if (shipping_email && address_list_visible) {
        klarnaOrderUpdateData.shipping_address.email = shipping_email.getValue();
    } else if (storeDataValue) {
        klarnaOrderUpdateData.shipping_address.email = storeDataValue.shipping_address.email;
    }

    if (shipping_telephone && address_list_visible) {
        klarnaOrderUpdateData.shipping_address.phone = shipping_telephone.getValue();
    } else if (storeDataValue) {
        klarnaOrderUpdateData.shipping_address.phone = storeDataValue.shipping_address.phone;
    }

    if (shipping_postcode && address_list_visible) {
        klarnaOrderUpdateData.shipping_address.postal_code = shipping_postcode.getValue();
    } else if (storeDataValue) {
        klarnaOrderUpdateData.shipping_address.postal_code = storeDataValue.shipping_address.postal_code;
    }

    if (shipping_region && address_list_visible) {
        var text = shipping_region.selectedIndex >= 0 ? shipping_region.options[shipping_region.selectedIndex].innerHTML : shipping_region.getValue();
        if (usregions[shipping_region.selectedIndex] != undefined) { // Only works for US
            text = usregions[shipping_region.selectedIndex];
        }
        klarnaOrderUpdateData.shipping_address.region = text;
    } else if (storeDataValue) {
        klarnaOrderUpdateData.shipping_address.region = storeDataValue.shipping_address.region;
    }

    if ((shipping_street_address_1 || shipping_street_address_2) && address_list_visible) {
        var shipping_street_line_1 = shipping_street_address_1.getValue();
        var shipping_street_line_2 = shipping_street_address_2.getValue();
        if (shipping_street_line_2.length > 0) {
            shipping_street_line_1 = shipping_street_line_1 + ',' + shipping_street_line_2;
        }
        klarnaOrderUpdateData.shipping_address.street_address = shipping_street_line_1;
    } else if (storeDataValue) {
        klarnaOrderUpdateData.shipping_address.street_address = storeDataValue.shipping_address.street_address;
    }
    return klarnaOrderUpdateData;
}

function updateBillingAddressForKlarna(klarnaOrderUpdateData, storedData) {
    var storeDataValue = false;
    if (storedData) {
        storeDataValue = JSON.parse(decodeURIComponent(storedData.getValue()));
    }

    var address_list_visible = false,
        billing_city = $('billing:city'),
        billing_firstname = $('billing:firstname'),
        billing_lastname = $('billing:lastname'),
        billing_country = $('billing:country_id'),
        billing_email = $('billing:email'),
        billing_telephone = $('billing:telephone'),
        billing_postcode = $('billing:postcode'),
        billing_region = $('billing:region_id'),
        use_for_shipping = $('billing:use_for_shipping_yes'),
        billing_street_address_1 = $('billing:street1'),
        billing_street_address_2 = $('billing:street2');

    var billing_address_list = $('billing_address_list');
    if (billing_address_list !== undefined && billing_address_list !== null) {
        if (billing_address_list.visible()) {
            address_list_visible = true;
        }
    }

    if (billing_city && address_list_visible) {
        klarnaOrderUpdateData.billing_address.city = billing_city.getValue();
    } else if (storeDataValue) {
        klarnaOrderUpdateData.billing_address.city = storeDataValue.billing_address.city;
    }

    if (billing_firstname && address_list_visible) {
        klarnaOrderUpdateData.billing_address.given_name = billing_firstname.getValue();
    } else if (storeDataValue) {
        klarnaOrderUpdateData.billing_address.given_name = storeDataValue.billing_address.given_name;
    }

    if (billing_lastname && address_list_visible) {
        klarnaOrderUpdateData.billing_address.family_name = billing_lastname.getValue();
    } else if (storeDataValue) {
        klarnaOrderUpdateData.billing_address.family_name = storeDataValue.billing_address.family_name;
    }

    if (billing_country && address_list_visible) {
        klarnaOrderUpdateData.billing_address.country = billing_country.getValue();
    } else if (storeDataValue) {
        klarnaOrderUpdateData.billing_address.country = storeDataValue.billing_address.country;
    }

    if (billing_email && address_list_visible) {
        klarnaOrderUpdateData.billing_address.email = billing_email.getValue();
    } else if (storeDataValue) {
        klarnaOrderUpdateData.billing_address.email = storeDataValue.billing_address.email;
    }

    if (billing_telephone && address_list_visible) {
        klarnaOrderUpdateData.billing_address.phone = billing_telephone.getValue();
    } else if (storeDataValue) {
        klarnaOrderUpdateData.billing_address.phone = storeDataValue.billing_address.phone;
    }

    if (billing_postcode && address_list_visible) {
        klarnaOrderUpdateData.billing_address.postal_code = billing_postcode.getValue();
    } else if (storeDataValue) {
        klarnaOrderUpdateData.billing_address.postal_code = storeDataValue.billing_address.postal_code;
    }

    if (billing_region && address_list_visible) {
        var text = billing_region.selectedIndex >= 0 ? billing_region.options[billing_region.selectedIndex].innerHTML : billing_region.getValue();
        if (usregions[billing_region.selectedIndex] != undefined) { // Only works for US
            text = usregions[billing_region.selectedIndex];
        }
        klarnaOrderUpdateData.billing_address.region = text;
    } else if (storeDataValue) {
        klarnaOrderUpdateData.billing_address.region = storeDataValue.billing_address.region;
    }

    if ((billing_street_address_1 || billing_street_address_2) && address_list_visible) {
        var billing_street_line_1 = billing_street_address_1.getValue();
        var billing_street_line_2 = billing_street_address_2.getValue();
        if (billing_street_line_2.length > 0) {
            billing_street_line_1 = billing_street_line_1 + ',' + billing_street_line_2;
        }
        klarnaOrderUpdateData.billing_address.street_address = billing_street_line_1;

    } else if (storeDataValue) {
        klarnaOrderUpdateData.billing_address.street_address = storeDataValue.billing_address.street_address;
    }

    if (use_for_shipping && use_for_shipping.getValue() == '1') {
        klarnaOrderUpdateData.shipping_address = klarnaOrderUpdateData.billing_address;
    }

    if (use_for_shipping && use_for_shipping.getValue() != '1') {
        klarnaOrderUpdateData = updateShippingAddressForKlarna(klarnaOrderUpdateData, storedData);
    }
    return klarnaOrderUpdateData;
}