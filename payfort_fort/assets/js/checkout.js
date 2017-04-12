

jQuery('form.checkout').on('submit', function (e){
    if(jQuery('input[name=payment_method]:checked').val() == 'payfort') {
        e.preventDefault();
        return fortFormHandler(jQuery(this));
    }
});
jQuery('form#order_review').on('submit', function () {
    return fortFormHandler(jQuery(this));
});

function showError(form, data) {
    // Remove notices from all sources
    jQuery( '.woocommerce-error, .woocommerce-message' ).remove();

    // Add new errors returned by this event
    if ( data.messages ) {
            form.prepend( '<div class="woocommerce-NoticeGroup-updateOrderReview">' + data.messages + '</div>' );
    } else {
            form.prepend( data );
    }

    // Lose focus for all fields
    form.find( '.input-text, select, input:checkbox' ).blur();

    // Scroll to top
    jQuery( 'html, body' ).animate( {
            scrollTop: ( jQuery( form ).offset().top - 100 )
    }, 1000 );
}
var form = jQuery("form.checkout");
form.length ? (form.bind("checkout_place_order_payfort", function() {
    //return fortFormHandler(jQuery(this));
    return !1;
})) : jQuery("form#order_review").submit(function() {
    var paymentMethod = jQuery("#order_review input[name=payment_method]:checked").val();
    return "payfort" === paymentMethod ? fortFormHandler(jQuery(this)) : void 0;
});

function fortFormHandler(form) {
    if (form.is(".processing")) return !1;
    return initPayfortFortPayment(form);
}

function isMerchantPageMethod() {
    if(!jQuery('[data-method=NAPS]').is(':checked') && !jQuery('[data-method=SADAD]').is(':checked')
            && jQuery('#payfort_fort_cc_integration_type').val() == 'merchantPage') {
        return true;
    }
    return false;
}

function isMerchantPage2Method() {
    if(!jQuery('[data-method=NAPS]').is(':checked') && !jQuery('[data-method=SADAD]').is(':checked')
            && jQuery('#payfort_fort_cc_integration_type').val() == 'merchantPage2') {
        return true;
    }
    return false;
}

function initPayfortFortPayment(form) {
    var data = jQuery(form).serialize();
    var isSadad = jQuery('[data-method=SADAD]').is(':checked');
    var isNAPS = jQuery('[data-method=NAPS]').is(':checked');
    if(isMerchantPage2Method()) {
        //validate credit card form
        var isValid = payfortFortMerchantPage2.validateCcForm(form);
        if(!isValid) {
            return !1;
        }
    }
    data += '&SADAD=' + isSadad;
    data += '&NAPS=' + isNAPS;
    var ajaxUrl = wc_checkout_params.checkout_url;
//    if(jQuery('form#order_review').size() == 0){
//        ajaxUrl = '?wc-ajax=checkout';
//    }
    jQuery.ajax({
        'url': ajaxUrl,
        'type': 'POST',
        'dataType': 'json',
        'data': data,
        'async': false
    }).complete(function (response) {
        data = '';
        if(response.form) {
            data = response;
        }
        else{
            var code = response.responseText;
            var newstring = code.replace(/<script[^>]*>(.*)<\/script>/, "");
            if (newstring.indexOf("<!--WC_START-->") >= 0) {
                    newstring = newstring.split("<!--WC_START-->")[1];
            }
            if (newstring.indexOf("<!--WC_END-->") >= 0) {
                    newstring = newstring.split("<!--WC_END-->")[0];
            }
            try {
                data = jQuery.parseJSON( newstring );
            }
            catch(e) {}
        }
        if(data.result == 'failure') {
            showError(form, data);
            return !1;
        }
        if (data.form) {
            jQuery('#frm_payfort_fort_payment').remove();
            jQuery('body').append(data.form);
            window.success = true;
            if(isMerchantPage2Method()) {
                payfortFortMerchantPage2.submitMerchantPage();
            }
            else if(isMerchantPageMethod()) {
                payfortFortMerchantPage.showMerchantPage(jQuery('#frm_payfort_fort_payment').attr('action'));
            }
            else{                   
                jQuery( "#frm_payfort_fort_payment" ).submit();
            }
        }
    });
    return !1;
}