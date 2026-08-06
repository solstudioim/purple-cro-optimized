(() => {
	'use strict';

	const config = window.purpleOptimize || {};
	const wishlistKey = 'purpleOptimizeWishlist';
	const escapeHtml = (value) => String(value).replace(/[&<>"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char]));
	const getWishlist = () => {
		try { return JSON.parse(window.localStorage.getItem(wishlistKey)) || {}; } catch (error) { return {}; }
	};
	const saveWishlist = (items) => window.localStorage.setItem(wishlistKey, JSON.stringify(items));

	function setupPromoCode() {
		document.querySelectorAll('.pot-copy-code').forEach((button) => {
			button.addEventListener('click', async () => {
				const copyFallback = () => {
					const field = document.createElement('textarea');
					field.value = button.dataset.code || '';
					field.setAttribute('readonly', '');
					field.style.position = 'fixed';
					field.style.opacity = '0';
					document.body.append(field);
					field.select();
					document.execCommand('copy');
					field.remove();
				};
				try {
					if (navigator.clipboard && window.isSecureContext) await navigator.clipboard.writeText(button.dataset.code || '');
					else copyFallback();
					const original = button.textContent;
					button.textContent = config.copied || 'Copied';
					window.setTimeout(() => { button.textContent = original; }, 1400);
				} catch (error) {
					copyFallback();
				}
			});
		});
	}

	function setupSearch() {
		if (!config.instantSearch) return;
		document.querySelectorAll('input[type="search"]').forEach((input, index) => {
			if (input.dataset.potSearchReady || !input.closest('.wp-block-search, .wp-block-woocommerce-product-search')) return;
			input.dataset.potSearchReady = 'true';
			const results = document.createElement('div');
			results.className = 'pot-search-results';
			results.id = `pot-search-results-${index}`;
			results.hidden = true;
			results.setAttribute('role', 'region');
			results.setAttribute('aria-live', 'polite');
			input.setAttribute('aria-controls', results.id);
			input.closest('.wp-block-search, .wp-block-woocommerce-product-search').append(results);
			let timer;
			let controller;

			input.addEventListener('input', () => {
				window.clearTimeout(timer);
				const term = input.value.trim();
				if (term.length < 2) { results.hidden = true; return; }
				results.hidden = false;
				results.innerHTML = `<p>${escapeHtml(config.searching || 'Searching…')}</p>`;
				timer = window.setTimeout(async () => {
					controller?.abort();
					controller = new AbortController();
					const url = new URL(config.ajaxUrl);
					url.searchParams.set('action', 'pot_search_products');
					url.searchParams.set('nonce', config.nonce);
					url.searchParams.set('term', term);
					if (config.category) url.searchParams.set('category', String(config.category));
					try {
						const response = await fetch(url, { signal: controller.signal, credentials: 'same-origin' });
						const payload = await response.json();
						const items = payload.success ? payload.data : [];
						results.innerHTML = items.length ? `<ul>${items.map((item) => `<li><a href="${escapeHtml(item.url)}"><img src="${escapeHtml(item.image)}" alt=""><span><strong>${escapeHtml(item.name)}</strong><span>${item.price}</span></span></a></li>`).join('')}</ul>` : `<p>${escapeHtml(config.noResults || 'No matching products found.')}</p>`;
					} catch (error) {
						if (error.name !== 'AbortError') results.hidden = true;
					}
				}, 220);
			});

			input.addEventListener('keydown', (event) => {
				const links = [...results.querySelectorAll('a')];
				if (event.key === 'Escape') { results.hidden = true; input.focus(); }
				if (event.key === 'ArrowDown' && links.length) { event.preventDefault(); links[0].focus(); }
			});
			results.addEventListener('keydown', (event) => {
				const links = [...results.querySelectorAll('a')];
				const current = links.indexOf(document.activeElement);
				if (event.key === 'ArrowDown' && current < links.length - 1) { event.preventDefault(); links[current + 1].focus(); }
				if (event.key === 'ArrowUp') { event.preventDefault(); current > 0 ? links[current - 1].focus() : input.focus(); }
				if (event.key === 'Escape') { results.hidden = true; input.focus(); }
			});

			document.addEventListener('click', (event) => {
				if (!results.contains(event.target) && event.target !== input) results.hidden = true;
			});
		});
	}

	function updateWishlistButton(button, items) {
		const saved = Boolean(items[button.dataset.product]);
		button.setAttribute('aria-pressed', saved ? 'true' : 'false');
		button.firstChild.textContent = saved ? '♥ ' : '♡ ';
		const label = button.querySelector('span');
		if (label) label.textContent = saved ? (config.removeWishlist || 'Remove from wishlist') : (config.addWishlist || 'Save to wishlist');
	}

	function setupWishlistButtons() {
		if (!config.wishlist) return;
		const items = getWishlist();
		document.querySelectorAll('.pot-wishlist-button').forEach((button) => {
			updateWishlistButton(button, items);
			button.addEventListener('click', () => {
				const current = getWishlist();
				const id = button.dataset.product;
				if (current[id]) delete current[id];
				else current[id] = { id, title: button.dataset.title, url: button.dataset.url, image: button.dataset.image, price: button.dataset.price };
				saveWishlist(current);
				updateWishlistButton(button, current);
			});
		});
	}

	function renderWishlistPage() {
		const root = document.querySelector('.pot-wishlist-page');
		if (!root) return;
		const render = () => {
			const items = Object.values(getWishlist());
			if (!items.length) { root.innerHTML = `<p>${escapeHtml(root.dataset.empty)}</p>`; return; }
			root.innerHTML = `<div class="pot-wishlist-grid">${items.map((item) => `<article class="pot-wishlist-card"><button type="button" data-remove="${escapeHtml(item.id)}" aria-label="${escapeHtml(config.removeWishlist || 'Remove from wishlist')}">×</button><a href="${escapeHtml(item.url)}"><img src="${escapeHtml(item.image)}" alt=""><h3>${escapeHtml(item.title)}</h3><p>${escapeHtml(item.price)}</p></a></article>`).join('')}</div>`;
			root.querySelectorAll('[data-remove]').forEach((button) => button.addEventListener('click', () => { const current = getWishlist(); delete current[button.dataset.remove]; saveWishlist(current); render(); }));
		};
		render();
	}

	function setupCountdowns() {
		const update = () => document.querySelectorAll('.pot-countdown').forEach((node) => {
			const remaining = Math.max(0, new Date(node.dataset.end).getTime() - Date.now());
			const output = node.querySelector('[data-countdown]');
			if (!remaining) { node.remove(); return; }
			const days = Math.floor(remaining / 86400000);
			const hours = Math.floor((remaining % 86400000) / 3600000);
			const minutes = Math.floor((remaining % 3600000) / 60000);
			output.textContent = `${days}d ${hours}h ${minutes}m`;
		});
		update();
		window.setInterval(update, 60000);
	}

	function setupStickyCart() {
		const sticky = document.querySelector('.pot-sticky-cart');
		const original = document.querySelector('.wp-block-woocommerce-add-to-cart-with-options, form.cart, .single_add_to_cart_button');
		if (!sticky || !original) return;
		const observer = new IntersectionObserver(([entry]) => {
			sticky.classList.toggle('is-visible', !entry.isIntersecting);
			sticky.setAttribute('aria-hidden', entry.isIntersecting ? 'true' : 'false');
		}, { threshold: 0 });
		observer.observe(original);
		sticky.querySelector('button').addEventListener('click', () => {
			const addButton = document.querySelector('.single_add_to_cart_button:not(.disabled), .wp-block-woocommerce-product-button button:not(:disabled)');
			if (sticky.dataset.productType === 'simple' && addButton) addButton.click();
			else original.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
		});
	}

	function moveShippingProgress() {
		const progress = document.querySelector('#pot-shipping-progress');
		const target = document.querySelector('.wp-block-woocommerce-cart, .wc-block-cart, .wp-block-woocommerce-checkout, .wc-block-checkout');
		if (progress && target) target.parentNode.insertBefore(progress, target);
	}

	function moveCheckoutEnhancements() {
		const place = () => {
			const checkout = document.querySelector('.wp-block-woocommerce-checkout, .wc-block-checkout');
			const actions = document.querySelector('.wc-block-checkout__actions_row');
			const inlineOffer = document.querySelector('.pot-inline-offer');
			const trust = document.querySelector('.pot-checkout-trust');
			const accountInvitation = document.querySelector('.pot-account-invitation');
			if (inlineOffer && actions && inlineOffer.nextElementSibling !== actions) actions.parentNode.insertBefore(inlineOffer, actions);
			if (trust && checkout && trust.nextElementSibling !== checkout) checkout.parentNode.insertBefore(trust, checkout);
			if (accountInvitation) {
				const receipt = document.querySelector('main .woocommerce-order, main .wp-block-woocommerce-order-confirmation-status, main');
				if (receipt && accountInvitation.parentNode !== receipt) receipt.append(accountInvitation);
			}
		};
		place();
		new MutationObserver(place).observe(document.body, { childList: true, subtree: true });
	}

	function labelCheckoutFields() {
		const checkout = document.querySelector('.wc-block-checkout, .wp-block-woocommerce-checkout');
		if (!checkout) return;
		const update = () => checkout.querySelectorAll('label').forEach((label) => {
			if (label.querySelector('.pot-field-status') || /\b(optional|required)\b/i.test(label.textContent)) return;
			const id = label.getAttribute('for');
			const field = id ? document.getElementById(id) : label.closest('.wc-block-components-text-input, .wc-block-components-combobox')?.querySelector('input, select, textarea');
			if (!field || ['checkbox', 'radio', 'hidden'].includes(field.type)) return;
			const required = field.required || field.getAttribute('aria-required') === 'true';
			const status = document.createElement('span');
			status.className = 'pot-field-status';
			status.textContent = required ? (config.requiredLabel || 'Required') : (config.optionalLabel || 'Optional');
			label.append(status);
		});
		update();
		new MutationObserver(update).observe(checkout, { childList: true, subtree: true });
	}

	function setupRecentPurchases() {
		const popup = document.querySelector('.pot-social-proof');
		if (!popup) return;
		try {
			if (window.sessionStorage.getItem('potSocialProofDismissed')) return;
		} catch (error) {
			// Session storage is an optional convenience, not required for the feature.
		}

		let events;
		try { events = JSON.parse(popup.dataset.events || '[]'); } catch (error) { events = []; }
		if (!events.length) return;

		const image = popup.querySelector('img');
		const message = popup.querySelector('p');
		const time = popup.querySelector('small');
		let index = 0;
		let hideTimer;
		let nextTimer;
		const show = () => {
			const event = events[index % events.length];
			image.src = event.image;
			image.alt = '';
			message.innerHTML = `<strong>${escapeHtml(event.name)}</strong> purchased <a href="${escapeHtml(event.url)}">${escapeHtml(event.title)}</a>`;
			time.textContent = event.time;
			popup.hidden = false;
			window.requestAnimationFrame(() => popup.classList.add('is-visible'));
			index += 1;
			hideTimer = window.setTimeout(() => {
				popup.classList.remove('is-visible');
				nextTimer = window.setTimeout(show, 10000);
			}, 5000);
		};

		popup.querySelector('button').addEventListener('click', () => {
			window.clearTimeout(hideTimer);
			window.clearTimeout(nextTimer);
			popup.classList.remove('is-visible');
			try { window.sessionStorage.setItem('potSocialProofDismissed', '1'); } catch (error) { /* No-op. */ }
		});
		window.setTimeout(show, 1500);
	}

	function setupOfferCountdown() {
		const timer = document.querySelector('[data-offer-expiry]');
		if (!timer) return;
		const output = timer.querySelector('[data-offer-countdown]');
		const form = document.querySelector('.pot-offer-actions');
		const reject = form?.querySelector('[name="pot_offer_action"][value="reject"]');
		let submitted = false;
		const update = () => {
			const remaining = Math.max(0, Number(timer.dataset.offerExpiry) - Date.now());
			if (!remaining) {
				output.textContent = config.offerExpired || 'Offer expired';
				form?.querySelector('[value="accept"]')?.setAttribute('disabled', 'disabled');
				if (!submitted && form && reject) {
					submitted = true;
					form.requestSubmit(reject);
				}
				return false;
			}
			const hours = Math.floor(remaining / 3600000);
			const minutes = Math.floor((remaining % 3600000) / 60000);
			const seconds = Math.floor((remaining % 60000) / 1000);
			output.textContent = `${hours ? `${hours}:` : ''}${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
			return true;
		};
		if (!update()) return;
		const interval = window.setInterval(() => { if (!update()) window.clearInterval(interval); }, 1000);
	}

	function setupCartEmptyState() {
		const cartRoot = document.querySelector('.wp-block-woocommerce-cart, .wc-block-cart');
		if (!cartRoot) return;
		const syncEmptyCart = () => {
			const empty = Boolean(document.querySelector('.wc-block-cart__empty-cart__title, .wp-block-woocommerce-empty-cart-block'));
			const progress = document.querySelector('#pot-shipping-progress');
			if (progress) progress.hidden = empty;
		};
		syncEmptyCart();
		new MutationObserver(syncEmptyCart).observe(cartRoot, { childList: true, subtree: true });
	}

	document.addEventListener('DOMContentLoaded', () => {
		setupPromoCode();
		setupSearch();
		setupWishlistButtons();
		renderWishlistPage();
		setupCountdowns();
		setupStickyCart();
		moveShippingProgress();
		moveCheckoutEnhancements();
		labelCheckoutFields();
		setupRecentPurchases();
		setupOfferCountdown();
		setupCartEmptyState();
	});
})();
