/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "react":
/*!************************!*\
  !*** external "React" ***!
  \************************/
/***/ ((module) => {

module.exports = window["React"];

/***/ }),

/***/ "@woocommerce/blocks-registry":
/*!******************************************!*\
  !*** external ["wc","wcBlocksRegistry"] ***!
  \******************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcBlocksRegistry"];

/***/ }),

/***/ "@woocommerce/settings":
/*!************************************!*\
  !*** external ["wc","wcSettings"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wc"]["wcSettings"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/html-entities":
/*!**************************************!*\
  !*** external ["wp","htmlEntities"] ***!
  \**************************************/
/***/ ((module) => {

module.exports = window["wp"]["htmlEntities"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
/*!**************************************************!*\
  !*** ./resources/js/frontend/aps_installment.js ***!
  \**************************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! react */ "react");
/* harmony import */ var react__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(react__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @woocommerce/blocks-registry */ "@woocommerce/blocks-registry");
/* harmony import */ var _woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @wordpress/html-entities */ "@wordpress/html-entities");
/* harmony import */ var _wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @woocommerce/settings */ "@woocommerce/settings");
/* harmony import */ var _woocommerce_settings__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_5__);






const defaultLabel = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('INSTALLMENTS', 'amazon-payment-services');
const settings = (0,_woocommerce_settings__WEBPACK_IMPORTED_MODULE_4__.getSetting)('aps_installment_data', {});
const label = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__.decodeEntities)(settings.title) || defaultLabel;
const redirecting = (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__.decodeEntities)(settings.redirect_message) || (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Redirecting...', 'amazon-payment-services');
const icons = [];
for (let el in settings.icons) {
  icons.push({
    id: 'aps-icon-' + el,
    src: settings.icons[el],
    alt: label
  });
}
const Label = props => {
  const {
    PaymentMethodLabel,
    PaymentMethodIcons
  } = props.components;
  return [(0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(PaymentMethodLabel, {
    text: label
  }), (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(PaymentMethodIcons, {
    icons: icons
  })];
};
let checkoutData = {};
let checkoutMeta = {};
let scriptLoaded = false;
const validateCard = card_number => {
  let card_type = "";
  let card_validity = true;
  let message = '';
  let card_length = 0;
  if (card_number) {
    card_number = card_number.replace(/ /g, '').replace(/-/g, '');
    // Visa
    let visa_regex = new RegExp('^4[0-9]{0,15}$');

    // MasterCard
    let mastercard_regex = new RegExp('^5$|^5[0-5][0-9]{0,16}$');

    // American Express
    let amex_regex = new RegExp('^3$|^3[47][0-9]{0,13}$');

    //mada TODO
    // let mada_regex = new RegExp( '/^' + aps_info.mada_bins + '/', 'm' );
    //
    //meeza
    // let meeza_regex = new RegExp( aps_info.meeza_bins, 'gm' );
    // if ( card_number.match( mada_regex ) ) {
    //     if ( aps_info.have_recurring_items ) {
    //         card_validity = false;
    //         message       = aps_info.error_msg.invalid_card;
    //     } else {
    //         card_type   = 'mada';
    //         card_length = 16;
    //     }
    // } else if ( card_number.match( meeza_regex ) ) {
    //     if ( aps_info.have_recurring_items ) {
    //         card_validity = false;
    //         message       = aps_info.error_msg.invalid_card;
    //     } else {
    //         card_type   = 'meeza';
    //         card_length = 19;
    //     }
    // } else
    if (card_number.match(visa_regex)) {
      card_type = 'visa';
      card_length = 16;
    } else if (card_number.match(mastercard_regex)) {
      card_type = 'mastercard';
      card_length = 16;
    } else if (card_number.match(amex_regex)) {
      card_type = 'amex';
      card_length = 15;
    } else {
      card_validity = false;
      message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Card number is invalid', 'amazon-payment-services');
    }
    if (card_number.length < 15) {
      card_validity = false;
      message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Invalid card length', 'amazon-payment-services');
    }
  } else {
    message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Card number cannot be empty', 'amazon-payment-services');
    card_validity = false;
  }
  return {
    card_type,
    validity: card_validity,
    msg: message,
    card_length
  };
};
const validateHolderName = card_holder_name => {
  let validity = true;
  let message = '';
  card_holder_name = card_holder_name.trim();
  if (card_holder_name.length > 255 || card_holder_name.length === 0) {
    validity = false;
    message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Card holder name is invalid', 'amazon-payment-services');
  }
  return {
    validity,
    msg: message
  };
};
const validateCVV = (card_cvv, cvv_element) => {
  let validity = true;
  let message = '';
  if (cvv_element.length === 1) {
    card_cvv = card_cvv.trim();
    let card_type = cvv_element.parentNode.parentNode.querySelector('.card-icon.card-amex.active');
    if (!card_type.length || card_type.length === 0) {
      if (card_cvv.length !== 3 || card_cvv.length === 0 || card_cvv === '000') {
        validity = false;
        message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Card CVV is invalid', 'amazon-payment-services');
      }
    } else {
      if (card_cvv.length !== 4 || card_cvv.length === 0 || card_cvv === '000') {
        validity = false;
        message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Card CVV is invalid', 'amazon-payment-services');
      }
    }
  }
  return {
    validity,
    msg: message
  };
};
const validateCardExpiry = (card_expiry_month, card_expiry_year) => {
  let validity = true;
  let message = '';
  if (card_expiry_month === '' || !card_expiry_month) {
    validity = false;
    message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Expiry month is invalid', 'amazon-payment-services');
  } else if (card_expiry_year === '' || !card_expiry_year) {
    validity = false;
    message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Expiry year is invalid', 'amazon-payment-services');
  } else if (parseInt(card_expiry_month) <= 0 || parseInt(card_expiry_month) > 12) {
    validity = false;
    message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Expiry month is invalid', 'amazon-payment-services');
  } else {
    let cur_date, exp_date;
    card_expiry_month = ('0' + (parseInt(card_expiry_month) - 1)).slice(-2);
    cur_date = new Date();
    exp_date = new Date(parseInt('20' + card_expiry_year), card_expiry_month, 30);
    if (exp_date.getTime() < cur_date.getTime()) {
      message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Expiry date is invalid', 'amazon-payment-services');
      validity = false;
    }
  }
  return {
    validity,
    msg: message
  };
};
const validateSavedCVV = (card_cvv, length) => {
  let validity = true;
  let message = '';
  card_cvv = card_cvv.trim();
  length = parseInt(length);
  if (card_cvv.length !== length || card_cvv.length === 0 || card_cvv === '000') {
    validity = false;
    message = (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Card CVV is invalid', 'amazon-payment-services');
  }
  return {
    validity: validity,
    msg: message
  };
};
const Content = props => {
  const {
    eventRegistration,
    emitResponse
  } = props;
  const {
    onCheckoutSuccess,
    onPaymentSetup
  } = eventRegistration;
  if (settings.is_hosted === true) {
    (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useEffect)(() => {
      const unsubscribe = onPaymentSetup(async () => {
        let aps_cc_token_checked = document.querySelector('.aps_installment_token:checked');
        let aps_cc_token_card_checked = document.querySelector('.aps_token_card:checked');
        if (aps_cc_token_checked) {
          let aps_cvv_checked = aps_cc_token_checked.parentNode.parentNode.querySelector('.aps_saved_card_cvv');
          if (!aps_cvv_checked || !aps_cvv_checked.value) {
            return {
              type: emitResponse.responseTypes.ERROR,
              message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Please enter your CVV!', 'amazon-payment-services')
            };
          }
        }
        if (aps_cc_token_card_checked) {
          let hosted_checkout_form_check = document.getElementById('aps_instalment_form');
          let card_number_check = hosted_checkout_form_check ? hosted_checkout_form_check.querySelector('.aps_card_number') : null;
          if (!card_number_check || !card_number_check.value) {
            return {
              type: emitResponse.responseTypes.ERROR,
              message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Enter Credit Card data before placing order!', 'amazon-payment-services')
            };
          }
        }
        let installment_plan_code = document.getElementById('aps_installment_plan_code');
        if (!installment_plan_code || !installment_plan_code.value) {
          return {
            type: emitResponse.responseTypes.ERROR,
            message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Please select an installment plan!', 'amazon-payment-services')
          };
        }
        let installment_term = document.getElementById('installment_term');
        if (!installment_term || !installment_term.checked) {
          return {
            type: emitResponse.responseTypes.ERROR,
            message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Please accept the terms and conditions!', 'amazon-payment-services')
          };
        }
        if (aps_cc_token_checked) {
          let aps_cvv = aps_cc_token_checked.parentNode.parentNode.querySelector('.aps_saved_card_cvv');
          let card_bin = aps_cc_token_checked.getAttribute('data-masking-card');
          let token_id = aps_cc_token_checked.getAttribute('data-token-id');
          let installment_issuer_code = document.getElementById('aps_installment_issuer_code');
          let installment_confirmation_en = document.getElementById('aps_installment_confirmation_en');
          let installment_confirmation_ar = document.getElementById('aps_installment_confirmation_ar');
          let installment_interest = document.getElementById('aps_installment_interest');
          let installment_amount = document.getElementById('aps_installment_amount');
          let validateSavedCvvResult = validateSavedCVV(aps_cvv.value, aps_cvv.getAttribute('maxlength'));
          if (!validateSavedCvvResult.validity && installment_plan_code) {
            if (!aps_cvv.className.indexOf('field_error')) {
              aps_cvv.className += ' field_error';
            }
            aps_cvv.focus();
            return {
              type: emitResponse.responseTypes.ERROR,
              message: validateSavedCvvResult.msg
            };
          } else {
            aps_cvv.className = aps_cvv.className.replaceAll('field_error', '');
            checkoutData = {};
            checkoutData.aps_token = '1';
            checkoutData.aps_token_id = token_id;
            checkoutData.aps_payment_cvv = aps_cvv.value;
            checkoutData.aps_payment_token_installment = aps_cc_token_checked.value;
            if (card_bin) {
              checkoutData.aps_card_bin = card_bin;
            }
            if (installment_plan_code) {
              checkoutData.aps_installment_plan_code = installment_plan_code.value;
              checkoutData.aps_installment_issuer_code = installment_issuer_code.value;
              checkoutData.aps_installment_confirmation_en = installment_confirmation_en.value;
              checkoutData.aps_installment_confirmation_ar = installment_confirmation_ar.value;
              checkoutData.aps_installment_interest = installment_interest.value;
              checkoutData.aps_installment_amount = installment_amount.value;
            }
            return {
              type: emitResponse.responseTypes.SUCCESS,
              meta: {
                paymentMethodData: checkoutData
              }
            };
          }
        } else {
          let hosted_checkout_form = document.getElementById('aps_instalment_form');
          if (hosted_checkout_form) {
            let card_number = hosted_checkout_form.querySelector('.aps_card_number');
            let card_holder_name = hosted_checkout_form.querySelector('.aps_card_holder_name');
            let expiry_month = hosted_checkout_form.querySelector('.aps_expiry_month');
            let expiry_year = hosted_checkout_form.querySelector('.aps_expiry_year');
            let card_security_code = hosted_checkout_form.querySelector('.aps_card_security_code');
            let card_remember_me = hosted_checkout_form.querySelector('.aps_card_remember_me');
            let installment_plan_code = document.getElementById('aps_installment_plan_code');
            let installment_issuer_code = document.getElementById('aps_installment_issuer_code');
            let installment_confirmation_en = document.getElementById('aps_installment_confirmation_en');
            let installment_confirmation_ar = document.getElementById('aps_installment_confirmation_ar');
            let installment_interest = document.getElementById('aps_installment_interest');
            let installment_amount = document.getElementById('aps_installment_amount');
            let validateCardResult = validateCard(card_number.value);
            let validateHolderNameResult = validateHolderName(card_holder_name.value);
            let validateCardCVVResult = validateCVV(card_security_code.value, card_security_code);
            let validateExpiryResult = validateCardExpiry(expiry_month.value, expiry_year.value);
            if (validateCardResult.validity && validateHolderNameResult.validity && validateCardCVVResult.validity && validateExpiryResult.validity && installment_plan_code) {
              let card_expiry_month = ('0' + (parseInt(expiry_month.value) - 1)).slice(-2);
              checkoutData = {};
              checkoutData.card_number = card_number.value;
              checkoutData.card_holder_name = card_holder_name.value;
              checkoutData.expiry_date = expiry_year.value + "" + card_expiry_month;
              checkoutData.card_security_code = card_security_code.value;
              checkoutMeta = {};
              checkoutMeta.aps_token = '1';
              checkoutMeta.remember_me = card_remember_me && card_remember_me.checked ? 'YES' : 'NO';
              checkoutMeta.aps_installment_plan_code = installment_plan_code.value;
              checkoutMeta.aps_installment_issuer_code = installment_issuer_code.value;
              checkoutMeta.aps_installment_confirmation_en = installment_confirmation_en.value;
              checkoutMeta.aps_installment_confirmation_ar = installment_confirmation_ar.value;
              checkoutMeta.aps_installment_interest = installment_interest.value;
              checkoutMeta.aps_installment_amount = installment_amount.value;
              return {
                type: emitResponse.responseTypes.SUCCESS,
                meta: {
                  paymentMethodData: checkoutMeta
                }
              };
            }
          }
        }
        return {
          type: emitResponse.responseTypes.ERROR,
          message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Enter Credit Card data before placing order!', 'amazon-payment-services')
        };
      });
      return () => {
        unsubscribe();
      };
    }, [emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup]);
  }
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_5__.useEffect)(() => {
    const unsubscribe = onCheckoutSuccess(response => {
      let order_id = response.orderId;
      if (order_id) {
        // disable the order now button and change text to 'Redirecting...'
        setTimeout(() => {
          let order_now_button = document.getElementsByClassName('wc-block-components-button__text');
          if (order_now_button[0]) {
            order_now_button[0].textContent = redirecting;
          }
        }, 500);
        let error_message = response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.error_message ? response.processingResponse.paymentDetails.error_message : '';
        if (error_message) {
          return {
            type: emitResponse.responseTypes.ERROR,
            message: error_message,
            messageContext: emitResponse.noticeContexts.PAYMENTS
          };
        }
        let redirect_url = response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.redirect_url ? response.processingResponse.paymentDetails.redirect_url : '';
        if (redirect_url) {
          // this means that we have to follow the redirect url
          window.location = redirect_url;
          return;
        }
        let aps_form = response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.form ? response.processingResponse.paymentDetails.form : '';
        if (aps_form) {
          document.body.insertAdjacentHTML('beforeend', aps_form);
          let aps_form_object = document.getElementById('aps_payment_form');
          if (aps_form_object) {
            if (settings.is_hosted === true) {
              for (let el in checkoutData) {
                aps_form_object.insertAdjacentHTML('beforeend', "<input type='hidden' name='" + el + "' value='" + checkoutData[el] + "' />");
              }
            }
            aps_form_object.submit();
          }
        }
      }
      return response;
    });
    return () => {
      unsubscribe();
    };
  }, [emitResponse.noticeContexts.PAYMENTS, emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, '', [], onCheckoutSuccess]);
  if (settings.extra_form !== null) {
    const htmlToElem = html => wp.element.RawHTML({
      children: html
    });
    return htmlToElem(settings.extra_form);
  }
  return (0,_wordpress_html_entities__WEBPACK_IMPORTED_MODULE_3__.decodeEntities)(settings.description || '');
};
const ApsPayments = {
  name: "aps_installment",
  paymentMethodId: 'aps_installment',
  label: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Label, null),
  content: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Content, null),
  edit: (0,react__WEBPACK_IMPORTED_MODULE_0__.createElement)(Content, null),
  canMakePayment: () => true,
  ariaLabel: label,
  supports: {
    features: settings.supports
  },
  placeOrderButtonLabel: 'Buy it now with Installments!'
};
(0,_woocommerce_blocks_registry__WEBPACK_IMPORTED_MODULE_2__.registerPaymentMethod)(ApsPayments);
/******/ })()
;
//# sourceMappingURL=blocks-aps_installment.js.map