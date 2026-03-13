import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { useEffect } from '@wordpress/element';

const defaultLabel = __(
    'Credit / Debit card',
    'amazon-payment-services'
);

const settings = getSetting( 'aps_cc_data', {} );

const label = decodeEntities( settings.title ) || defaultLabel;
const redirecting = decodeEntities( settings.redirect_message ) || __(
    'Redirecting...',
    'amazon-payment-services'
);
const icons = [];
for (let el in settings.icons) {
    icons.push({
        id: 'aps-icon-' + el,
        src: settings.icons[el],
        alt: label,
    });
}

let checkoutData = {};

const Label = ( props ) => {
    const { PaymentMethodLabel, PaymentMethodIcons } = props.components;
    return [
        <PaymentMethodLabel text={ label } />,
        <PaymentMethodIcons icons={ icons } />
    ];
};

const validateCard = ( card_number ) => {
    let card_type     = "";
    let card_validity = true;
    let message       = '';
    let card_length   = 0;

    if ( card_number ) {
        card_number = card_number.replace( / /g,'' ).replace( /-/g,'' );
        // Visa
        let visa_regex = new RegExp( '^4[0-9]{0,15}$' );

        // MasterCard
        let mastercard_regex = new RegExp( '^5$|^5[0-5][0-9]{0,16}$' );

        // American Express
        let amex_regex = new RegExp( '^3$|^3[47][0-9]{0,13}$' );

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
            if ( card_number.match( visa_regex ) ) {
            card_type   = 'visa';
            card_length = 16;
        } else if ( card_number.match( mastercard_regex ) ) {
            card_type   = 'mastercard';
            card_length = 16;
        } else if ( card_number.match( amex_regex ) ) {
            card_type   = 'amex';
            card_length = 15;
        } else {
            card_validity = false;
            message       = __( 'Card number is invalid', 'amazon-payment-services' );
        }

        if ( card_number.length < 15 ) {
            card_validity = false;
            message       = __( 'Invalid card length', 'amazon-payment-services' );
        }
    } else {
        message       = __( 'Card number cannot be empty', 'amazon-payment-services' );
        card_validity = false;
    }

    return {
        card_type,
        validity: card_validity,
        msg: message,
        card_length
    }
};

const validateHolderName = ( card_holder_name ) => {
    let validity     = true;
    let message      = '';

    card_holder_name = card_holder_name.trim();
    if (card_holder_name.length > 255 || card_holder_name.length === 0) {
        validity = false;
        message  = __( 'Card holder name is invalid', 'amazon-payment-services' );
    }

    return {
        validity,
        msg: message
    }
};

const validateCVV = ( card_cvv, cvv_element ) => {
    let validity = true;
    let message  = '';

    if ( cvv_element.length === 1 ) {
        card_cvv      = card_cvv.trim();
        let card_type = cvv_element.parentNode.parentNode.querySelector( '.card-icon.card-amex.active' );
        if ( ! card_type.length || card_type.length === 0 ) {
            if ( card_cvv.length !== 3 || card_cvv.length === 0 || card_cvv === '000' ) {
                validity = false;
                message  = __('Card CVV is invalid', 'amazon-payment-services');
            }
        } else {
            if ( card_cvv.length !== 4 || card_cvv.length === 0 || card_cvv === '000' ) {
                validity = false;
                message  = __('Card CVV is invalid', 'amazon-payment-services');
            }
        }
    }

    return {
        validity,
        msg: message
    }
};

const validateCardExpiry = ( card_expiry_month, card_expiry_year ) => {
    let validity = true;
    let message  = '';

    if ( card_expiry_month === '' || ! card_expiry_month ) {
        validity = false;
        message  = __( 'Expiry month is invalid', 'amazon-payment-services' );
    } else if ( card_expiry_year === '' || ! card_expiry_year ) {
        validity = false;
        message  = __( 'Expiry year is invalid', 'amazon-payment-services' );
    } else if ( parseInt( card_expiry_month ) <= 0 || parseInt( card_expiry_month ) > 12  ) {
        validity = false;
        message  = __( 'Expiry month is invalid', 'amazon-payment-services' );
    } else {
        let cur_date, exp_date;
        card_expiry_month = ('0' + (parseInt( card_expiry_month ) - 1)).slice( -2 );
        cur_date          = new Date();
        exp_date          = new Date( parseInt( '20' + card_expiry_year ), card_expiry_month, 30 );
        if (exp_date.getTime() < cur_date.getTime()) {
            message  = __( 'Expiry date is invalid', 'amazon-payment-services' );
            validity = false;
        }
    }

    return {
        validity,
        msg: message
    }
};

const validateSavedCVV = ( card_cvv, length) => {
    let validity = true;
    let message  = '';

    card_cvv     = card_cvv.trim();
    length       = parseInt(length);

    if ( card_cvv.length !== length || card_cvv.length === 0 || card_cvv === '000' ) {
        validity = false;
        message  = __('Card CVV is invalid', 'amazon-payment-services');
    }
    return {
        validity: validity,
        msg: message
    }
};

const Content = ( props ) => {
    const { eventRegistration, emitResponse } = props;
    const { onCheckoutSuccess, onPaymentSetup } = eventRegistration;

    if (settings.is_hosted === true) {
        useEffect(() => {
            const unsubscribe = onPaymentSetup(async () => {
                let aps_cc_token_checked = document.querySelector('.aps_cc_token:checked');
                if (aps_cc_token_checked) {

                    let aps_cvv = aps_cc_token_checked.parentNode.parentNode.querySelector('.aps_saved_card_cvv');
                    let card_bin = aps_cc_token_checked.getAttribute('data-masking-card');
                    let token_id = aps_cc_token_checked.getAttribute('data-token-id');

                    let validateSavedCvvResult = validateSavedCVV(aps_cvv.value, aps_cvv.getAttribute('maxlength'));
                    if ( !validateSavedCvvResult.validity ) {
                        if (!aps_cvv.className.indexOf('field_error')) {
                            aps_cvv.className += ' field_error';
                        }

                        aps_cvv.focus();

                        return {
                            type: emitResponse.responseTypes.ERROR,
                            message: validateSavedCvvResult.msg,
                        };
                    } else {
                        aps_cvv.className = aps_cvv.className.replaceAll('field_error', '')

                        checkoutData = {};
                        checkoutData.aps_token = '1';
                        checkoutData.aps_token_id = token_id;
                        checkoutData.aps_payment_cvv = aps_cvv.value;
                        checkoutData.aps_payment_token_cc = aps_cc_token_checked.value;
                        if (card_bin) {
                            checkoutData.aps_card_bin = card_bin;
                        }

                        return {
                            type: emitResponse.responseTypes.SUCCESS,
                            meta: {
                                paymentMethodData: checkoutData,
                            },
                        };
                    }
                } else {
                    let hosted_checkout_form = document.getElementById('aps_cc_form');
                    if (hosted_checkout_form) {
                        let card_number = hosted_checkout_form.querySelector('.aps_card_number');
                        let card_holder_name = hosted_checkout_form.querySelector('.aps_card_holder_name');
                        let expiry_month = hosted_checkout_form.querySelector('.aps_expiry_month');
                        let expiry_year = hosted_checkout_form.querySelector('.aps_expiry_year');
                        let card_security_code = hosted_checkout_form.querySelector('.aps_card_security_code');

                        let validateCardResult       = validateCard( card_number.value );
                        let validateHolderNameResult = validateHolderName( card_holder_name.value );
                        let validateCardCVVResult    = validateCVV( card_security_code.value, card_security_code );
                        let validateExpiryResult     = validateCardExpiry( expiry_month.value, expiry_year.value );

                        if (
                            validateCardResult.validity
                            && validateHolderNameResult.validity
                            && validateCardCVVResult.validity
                            && validateExpiryResult.validity
                        ) {
                            let card_expiry_month = ('0' + (parseInt( expiry_month.value) - 1 )).slice( -2 );

                            checkoutData = {};
                            checkoutData.card_number = card_number.value;
                            checkoutData.card_holder_name = card_holder_name.value;
                            checkoutData.expiry_date = expiry_year.value + "" + card_expiry_month;
                            checkoutData.card_security_code = card_security_code.value;

                            return {
                                type: emitResponse.responseTypes.SUCCESS,
                            };
                        }
                    }
                }

                return {
                    type: emitResponse.responseTypes.ERROR,
                    message: __('Enter Credit Card data before placing order!', 'amazon-payment-services'),
                };
            });

            return () => {
                unsubscribe();
            };
        }, [
            emitResponse.responseTypes.ERROR,
            emitResponse.responseTypes.SUCCESS,
            onPaymentSetup
        ]);
    }

    useEffect(() => {
        const unsubscribe = onCheckoutSuccess( (response) => {
            let order_id = response.orderId;
            if (order_id) {
                // disable the order now button and change text to 'Redirecting...'
                setTimeout(() => {
                    let order_now_button = document.getElementsByClassName('wc-block-components-button__text');
                    if (order_now_button[0]) {
                        order_now_button[0].textContent = redirecting;
                    }
                }, 500);

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
                                aps_form_object.insertAdjacentHTML(
                                    'beforeend',
                                    "<input type='hidden' name='" + el + "' value='" + checkoutData[el] + "' />"
                                );
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
    }, [
        '',
        [],
        onCheckoutSuccess,
    ]);

    if (settings.extra_form !== null) {
        const htmlToElem = ( html ) => wp.element.RawHTML( { children: html } );
        return htmlToElem(settings.extra_form);
    }

    return decodeEntities( settings.description || '' );
};


const ApsPayments = {
    name: "aps_cc",
    paymentMethodId: 'aps_cc',
    label: <Label />,
    content: <Content />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
    placeOrderButtonLabel: 'Buy it now!',
};


registerPaymentMethod( ApsPayments );


