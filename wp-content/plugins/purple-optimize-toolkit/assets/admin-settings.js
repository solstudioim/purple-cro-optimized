/* global jQuery */
(function () {
	'use strict';
	// Warn on unsaved edits in either independently saved form. Offer enable/disable
	// has its own immediate-save feedback and is intentionally excluded.
	const forms = document.querySelectorAll('#pot-section-settings, #pot-checkout-settings');
	const dirty = new Set();
	for (const form of forms) {
		form.addEventListener('change', (event) => {
			if (event.target.id !== 'pot-offer-enabled') dirty.add(form);
		});
		form.addEventListener('input', (event) => {
			if (event.target.id !== 'pot-offer-enabled') dirty.add(form);
		});
		form.addEventListener('submit', (event) => {
			queueMicrotask(() => { if (!event.defaultPrevented) dirty.delete(form); });
		});
	}
	if (typeof jQuery !== 'undefined' && jQuery.fn.wpColorPicker) {
		jQuery('.pot-color-picker').each(function () {
			const input = this;
			jQuery(input).wpColorPicker({
				change: () => { if (input.form) dirty.add(input.form); },
				clear: () => { if (input.form) dirty.add(input.form); },
			});
		});
	}
	// WooCommerce's searchable product control emits jQuery events.
	if (typeof jQuery !== 'undefined') {
		jQuery('.pot-admin-workspace .wc-product-search').on('change', function () {
			if (this.form) dirty.add(this.form);
		});
	}
	window.addEventListener('beforeunload', (event) => {
		if (!dirty.size) return;
		event.preventDefault();
		event.returnValue = '';
	});
})();
