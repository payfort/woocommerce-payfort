import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { useEffect } from '@wordpress/element';

const defaultLabel = __(
    'Pay with Apple Pay',
    'amazon-payment-services'
);

const settings = getSetting( 'aps_apple_pay_data', {} );

const label = decodeEntities( settings.title ) || defaultLabel;

const Label = ( props ) => {
    const { PaymentMethodLabel } = props.components;
    return <PaymentMethodLabel text={ label } />;
};

const Content = ( props ) => {
    const { eventRegistration, emitResponse } = props;
    const { onPaymentSetup } = eventRegistration;

    useEffect(() => {
        const unsubscribe = onPaymentSetup(async () => {
            return {
                type: emitResponse.responseTypes.ERROR,
                message: __('Use the Apple Pay button!', 'amazon-payment-services'),
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

    return wp.element.RawHTML({children: settings.apple_pay_button_html});
};


const ApsPayments = {
    name: "aps_apple_pay",
    paymentMethodId: 'aps_apple_pay',
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

if (window.ApplePaySession && window.ApplePaySession.canMakePayments) {
    registerPaymentMethod(ApsPayments);
}