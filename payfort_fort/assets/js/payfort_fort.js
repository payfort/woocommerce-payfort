var payfortFort = (function () {
   return {
        validateCreditCard: function(element) {
            var isValid = false;
            var eleVal = jQuery(element).val();
            eleVal = this.trimString(element.val());
            eleVal = eleVal.replace(/\s+/g, '');
            jQuery(element).val(eleVal);
            jQuery(element).validateCreditCard(function(result) {
                /*jQuery('.log').html('Card type: ' + (result.card_type == null ? '-' : result.card_type.name)
                         + '<br>Valid: ' + result.valid
                         + '<br>Length valid: ' + result.length_valid
                         + '<br>Luhn valid: ' + result.luhn_valid);*/
                isValid = result.valid;
            });
            return isValid;
        },
        validateCardHolderName: function(element) {
            jQuery(element).val(this.trimString(element.val()));
            var cardHolderName = jQuery(element).val();
            if(cardHolderName.length > 255) {
                return false;
            }
            return true;
        },
        validateCvc: function(element) {
            jQuery(element).val(this.trimString(element.val()));
            var cvc = jQuery(element).val();
            if(cvc.length > 4 || cvc.length == 0) {
                return false;
            }
            if(!this.isPosInteger(cvc)) {
                return false;
            }
            return true;
        },
        translate: function(key, category, replacments) {
            if(!this.isDefined(category)) {
                category = 'payfort_fort';
            }
            var message = (arr_messages[category + '.' + key]) ? arr_messages[category + '.' + key] : key;
            if (this.isDefined(replacments)) {
                jQuery.each(replacments, function (obj, callback) {
                    message = message.replace(obj, callback);
                });
            }
            return message;
        },
        isDefined: function(variable) {
            if (typeof (variable) === 'undefined' || typeof (variable) === null) {
                return false;
            }
            return true;
        },
        isTouchDevice: function() {
            return 'ontouchstart' in window        // works on most browsers 
                || navigator.maxTouchPoints;       // works on IE10/11 and Surface
        },
        trimString: function(str){
            return str.trim();
        },
        isPosInteger: function(data) {
            var objRegExp  = /(^\d*$)/;
            return objRegExp.test( data );
        }
   };
})();

var payfortFortMerchantPage2 = (function () {
    var merchantPage2FormId = '#frm_payfort_fort_payment';
    return {
        validateCcForm: function (checkoutForm) {
            this.hideError();
            isValid = payfortFort.validateCreditCard(jQuery('#payfort_fort_card_number'));
            if(!isValid) {
                this.showError(checkoutForm, payfortFort.translate('error_invalid_card_number'));
                return false;
            }
            var isValid = payfortFort.validateCardHolderName(jQuery('#payfort_fort_card_holder_name'));
            if(!isValid) {
                this.showError(checkoutForm, payfortFort.translate('error_invalid_card_holder_name'));
                return false;
            }
            isValid = payfortFort.validateCvc(jQuery('#payfort_fort_card_security_code'));
            if(!isValid) {
                this.showError(checkoutForm, payfortFort.translate('error_invalid_cvc_code'));
                return false;
            }
            var expDate = jQuery('#payfort_fort_expiry_year').val()+''+jQuery('#payfort_fort_expiry_month').val();
            jQuery('#payfort_fort_expiry').val(expDate);
            return true;
        },
        showError: function(checkoutForm, msg) {
            jQuery(".woocommerce-error, .woocommerce-message").remove(), jQuery('#payfort_fort_cc_form').prepend('<div id="payfort_fort_msg" class="woocommerce-error" style="display: none;"></div>'), checkoutForm.removeClass("processing").unblock(), checkoutForm.find(".input-text, select").blur(), jQuery("html, body").animate({
                scrollTop: jQuery('#payfort_fort_msg').offset().top - 300
            }, 1e3);
            jQuery('#payfort_fort_msg').html(msg);
            jQuery('#payfort_fort_msg').show();
            //payfort_fort_cc_form
        },
        hideError: function() {
            jQuery('#payfort_fort_msg').hide();
        },
        submitMerchantPage: function() {
            var formParams = {};
            formParams.card_holder_name = jQuery('#payfort_fort_card_holder_name').val();
            formParams.card_number = jQuery('#payfort_fort_card_number').val();
            formParams.expiry_date = jQuery('#payfort_fort_expiry').val();
            formParams.card_security_code = jQuery('#payfort_fort_card_security_code').val();
            jQuery.each(formParams, function(k, v){
                jQuery('<input>').attr({
                    type: 'hidden',
                    id: k,
                    name: k,
                    value: v
                }).appendTo(merchantPage2FormId); 
            });
            jQuery(merchantPage2FormId).submit();
        }
    };
})();

var payfortFortMerchantPage = (function () {
    return {
        showMerchantPage: function(gatewayUrl) {
            if(jQuery("#payfort_merchant_page").size()) {
                jQuery( "#payfort_merchant_page" ).remove();
            }
            jQuery('<iframe  name="payfort_merchant_page" id="payfort_merchant_page"height="650px" frameborder="0" scrolling="no" onload="payfortFortMerchantPage.iframeLoaded(this)" style="display:none"></iframe>').appendTo('#pf_iframe_content');
            jQuery('.pf-iframe-spin').show();
            jQuery('.pf-iframe-close').hide();
            jQuery( "#payfort_merchant_page" ).attr("src", gatewayUrl);
            jQuery( "#frm_payfort_fort_payment" ).attr("action",gatewayUrl);
            jQuery( "#frm_payfort_fort_payment" ).attr("method","post");
            jQuery( "#frm_payfort_fort_payment" ).attr("target","payfort_merchant_page");
            jQuery( "#frm_payfort_fort_payment" ).submit();
            //fix for touch devices
            if (payfortFort.isTouchDevice()) {
                setTimeout(function() {
                    jQuery("html, body").animate({ scrollTop: 0 }, "slow");
                }, 1);
            }
            jQuery( "#div-pf-iframe" ).show();
        },
        closePopup: function() {
            jQuery( "#div-pf-iframe" ).hide();
            jQuery( "#payfort_merchant_page" ).remove();
            window.location = jQuery( "#payfort_cancel_url" ).val();
        },
        iframeLoaded: function(){
            jQuery('.pf-iframe-spin').hide();
            jQuery('.pf-iframe-close').show();
            jQuery('#payfort_merchant_page').show();
        },
    };
})();