/* global wc, wp */
(function () {
	'use strict';
	const { createElement: el, useState } = wp.element;
	const { useSelect } = wp.data;
	const { __, sprintf } = wp.i18n;
	const { registerCheckoutBlock, extensionCartUpdate } = wc.blocksCheckout;
	const { CART_STORE_KEY } = wc.wcBlocksData;
	const domain = 'purple-optimize-toolkit';

	function useCheckoutExtras() {
		return useSelect((select) => select(CART_STORE_KEY).getCartData().extensions?.['purple-checkout'] || {}, []);
	}

	function CheckoutUpsells() {
		const { offers = [] } = useCheckoutExtras();
		const [pending, setPending] = useState(false);
		const [error, setError] = useState('');
		const [message, setMessage] = useState('');
		const cartBusy = useSelect((select) => select(CART_STORE_KEY).isCartDataStale(), []);
		if (!offers.length) return null;

		async function toggle(offer) {
			if (pending || (offer.in_cart && !offer.added)) return;
			setPending(true);
			setError('');
			setMessage('');
			try {
				await extensionCartUpdate({ namespace: 'purple-checkout', data: { id: offer.id, selected: !offer.added }, overwriteDirtyCustomerData: false });
				setMessage(sprintf(offer.added ? __('Removed %s from your order.', domain) : __('Added %s to your order.', domain), offer.title));
			} catch (failure) {
				setError(failure.message || __('Unable to update your order. Please try again.', domain));
			} finally {
				setPending(false);
			}
		}

		return el('section', { className: 'pot-checkout-upsells', 'aria-label': __('Optional checkout add-ons', domain), 'aria-busy': pending },
			el('h3', null, __('Complete your order', domain)),
			el('p', { className: 'pot-checkout-upsells-intro' }, __('Optional extras — only added when you choose them.', domain)),
			...offers.map((offer) => {
				const alreadyInCart = offer.in_cart && !offer.added;
				const disabled = pending || cartBusy || alreadyInCart;
				return el('article', { key: offer.id, className: 'pot-checkout-upsell' + (offer.in_cart ? ' is-selected' : '') },
					el('div', { className: 'pot-checkout-upsell-heading' },
						el('label', null,
							el('input', { type: 'checkbox', checked: offer.in_cart, disabled, onChange: () => toggle(offer) }),
							el('strong', null, offer.title)),
						el('span', { className: 'pot-checkout-upsell-price', dangerouslySetInnerHTML: { __html: offer.price } })),
					el('div', { className: 'pot-checkout-upsell-details' },
						el('div', { className: 'pot-checkout-upsell-image', dangerouslySetInnerHTML: { __html: offer.image } }),
						el('p', null, offer.description)),
					el('button', { type: 'button', disabled, onClick: () => toggle(offer), 'aria-label': sprintf(offer.added ? __('Remove %s', domain) : __('Add %s to my order', domain), offer.title) },
						alreadyInCart ? __('Already in your order', domain) : offer.added ? __('Remove from my order', domain) : __('Yes! Add to my order', domain)));
			}),
			el('p', { role: 'status', className: 'pot-checkout-extra-status' }, message),
			error ? el('p', { role: 'alert', className: 'pot-checkout-extra-error' }, error) : null);
	}

	function CheckoutContent() {
		const { content = '' } = useCheckoutExtras();
		return content ? el('aside', { className: 'pot-checkout-content', 'aria-label': __('Helpful checkout information', domain), dangerouslySetInnerHTML: { __html: content } }) : null;
	}

	registerCheckoutBlock({ metadata: { name: 'purple-optimize/checkout-upsells', parent: ['woocommerce/checkout-fields-block'] }, component: CheckoutUpsells });
	registerCheckoutBlock({ metadata: { name: 'purple-optimize/checkout-content', parent: ['woocommerce/checkout-totals-block'] }, component: CheckoutContent });
})();
