import { sprintf, __ } from '@wordpress/i18n';
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { decodeEntities } from '@wordpress/html-entities';
import { getSetting } from '@woocommerce/settings';
import { useEffect } from '@wordpress/element'

const defaultLabel = __(
    'STC PAY',
    'amazon-payment-services'
);

const settings = getSetting( 'aps_stc_pay_data', {} );
const label = decodeEntities( settings.title ) || defaultLabel;

const icons = [];
for (let el in settings.icons) {
    icons.push({
        id: 'aps-icon-' + el,
        src: settings.icons[el],
        alt: label,
    });
}
const Label = ( props ) => {
    const { PaymentMethodLabel, PaymentMethodIcons } = props.components;
    return [
        <PaymentMethodLabel text={ label } />,
        <PaymentMethodIcons icons={ icons } />
    ];
};

const Content = ( props ) => {
    const { eventRegistration, emitResponse } = props;
    const { onCheckoutSuccess } = eventRegistration;

    useEffect(() => {
        const unsubscribe = onCheckoutSuccess( (response) => {
            var order_id = response.orderId;
            if (order_id) {
                // disable the order now button and change text to 'Redirecting...'
                setTimeout(() => {
                    var order_now_button = document.getElementsByClassName('wc-block-components-button__text');
                    if (order_now_button[0]) {
                        order_now_button[0].textContent = 'Redirecting...';
                    }
                }, 500)

                let aps_form = response.processingResponse && response.processingResponse.paymentDetails && response.processingResponse.paymentDetails.form ? response.processingResponse.paymentDetails.form : '';
                if (aps_form) {
                    document.body.insertAdjacentHTML('beforeend', aps_form);
                    let aps_form_object = document.getElementById('aps_payment_form');
                    if (aps_form_object) {
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

    return decodeEntities( settings.description || '' );
};

const ApsPayments = {
    name: "aps_stc_pay",
    paymentMethodId: 'aps_stc_pay',
    label: <Label />,
    content: <Content />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
    placeOrderButtonLabel: 'Buy it now with STC Pay!',
};

registerPaymentMethod( ApsPayments );


