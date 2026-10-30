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
	const builder = document.querySelector('#pot-reviews-configurator');
	if (builder && window.potReviewBuilder) {
		const labels = window.potReviewBuilder;
		const source = builder.elements.source;
		const status = builder.querySelector('#pot-reviews-status');
		const result = builder.querySelector('#pot-reviews-result');
		const preview = builder.querySelector('#pot-reviews-preview');
		const output = builder.querySelector('#pot-reviews-shortcode');
		const generate = builder.querySelector('#pot-generate-reviews');
		let request;
		let revision = 0;
		const update = () => {
			revision++;
			request?.abort();
			builder.querySelectorAll('[data-review-source]').forEach((row) => { row.hidden = row.dataset.reviewSource !== source.value; });
			builder.querySelector('[data-review-rotation]').hidden = builder.elements.layout.value !== 'rotator';
			result.hidden = true;
			preview.replaceChildren();
			output.value = '';
			generate.disabled = false;
			status.textContent = labels.changed;
		};
		builder.addEventListener('input', update);
		builder.addEventListener('change', update);
		if (typeof jQuery !== 'undefined') {
			let previous = [];
			jQuery('#pot-reviews-product-search').on('change', function () {
				const selected = jQuery(this).val() || [];
				const manual = builder.elements.products.value.split(/[\s,]+/).filter((id) => /^\d+$/.test(id) && !previous.includes(id));
				builder.elements.products.value = [...new Set([...manual, ...selected])].join(',');
				previous = selected;
				update();
			});
		}
		builder.addEventListener('submit', async (event) => {
			event.preventDefault();
			request?.abort();
			request = new AbortController();
			const current = ++revision;
			const body = new URLSearchParams({ action: 'pot_preview_reviews', nonce: labels.nonce });
			for (const [key, value] of new FormData(builder)) {
				if (!['pot-reviews-shortcode'].includes(key)) body.set(`config[${key}]`, value);
			}
			generate.disabled = true;
			status.textContent = labels.loading;
			result.hidden = true;
			try {
				const response = await fetch(labels.url, { method: 'POST', credentials: 'same-origin', body, signal: request.signal });
				const data = await response.json();
				if (current !== revision) return;
				if (!response.ok || !data.success) throw new Error(data.data?.message || labels.error);
				output.value = data.data.shortcode;
				preview.innerHTML = data.data.html;
				status.textContent = data.data.message;
				result.hidden = false;
			} catch (error) {
				if (current === revision && error.name !== 'AbortError') status.textContent = error.message || labels.error;
			} finally {
				if (current === revision) generate.disabled = false;
			}
		});
		builder.querySelector('#pot-copy-reviews').addEventListener('click', async () => {
			try {
				await navigator.clipboard.writeText(output.value);
				status.textContent = labels.copied;
			} catch (error) {
				output.focus(); output.select();
				status.textContent = labels.copyHelp;
			}
		});
	}
	window.addEventListener('beforeunload', (event) => {
		if (!dirty.size) return;
		event.preventDefault();
		event.returnValue = '';
	});
})();
