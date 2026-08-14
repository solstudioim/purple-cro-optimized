(() => {
	'use strict';

	const config = window.purpleOptimize || {};
	const features = config.features || {};
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
		const countdowns = [...document.querySelectorAll('.pot-countdown')];
		if (!countdowns.length) return;

		const renderTimer = (output, remaining) => {
			const parts = [
				{ label: config.daysLabel || 'Days', value: Math.floor(remaining / 86400000) },
				{ label: config.hoursLabel || 'Hours', value: Math.floor((remaining % 86400000) / 3600000) },
				{ label: config.minutesLabel || 'Minutes', value: Math.floor((remaining % 3600000) / 60000) },
				{ label: config.secondsLabel || 'Seconds', value: Math.floor((remaining % 60000) / 1000) },
			];

			if (!output.querySelector('.pot-timer-unit')) {
				output.classList.add('pot-timer-grid');
				output.innerHTML = parts.map((part, index) => `<span class="pot-timer-unit${index === 3 ? ' pot-timer-seconds' : ''}"><span class="pot-timer-value"></span><span class="pot-timer-label">${escapeHtml(part.label)}</span></span>`).join('<span class="pot-timer-separator" aria-hidden="true">:</span>');
			}

			output.querySelectorAll('.pot-timer-value').forEach((value, index) => {
				value.textContent = String(parts[index].value).padStart(2, '0');
			});
			output.setAttribute('aria-label', parts.map((part) => `${part.value} ${part.label.toLowerCase()}`).join(', '));
		};

		const update = () => {
			let active = 0;
			countdowns.forEach((node) => {
				if (!node.isConnected) return;
				const remaining = Math.max(0, new Date(node.dataset.end).getTime() - Date.now());
				const output = node.querySelector('[data-countdown]');
				if (!remaining) { node.remove(); return; }
				active += 1;
				renderTimer(output, remaining);
			});
			return active > 0;
		};
		if (!update()) return;
		const interval = window.setInterval(() => {
			if (!update()) window.clearInterval(interval);
		}, 1000);
	}

	function setupStickyCart() {
		const sticky = document.querySelector('.pot-sticky-cart');
		const original = document.querySelector('.wp-block-woocommerce-add-to-cart-with-options, form.cart, .single_add_to_cart_button');
		if (!sticky || !original) return;
		const mobile = window.matchMedia('(max-width: 782px)');
		if (!mobile.matches) return;
		const button = sticky.querySelector('button');
		const label = sticky.querySelector('.pot-sticky-cart-label');
		const status = sticky.querySelector('.pot-sticky-cart-status');
		const initialLabel = label?.textContent || '';
		let resetTimer;

		const animateToCart = () => {
			if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
			const image = sticky.querySelector('img');
			const target = document.querySelector('.wc-block-mini-cart__button, .wp-block-woocommerce-mini-cart, .wc-block-mini-cart');
			if (!image || !target) return;
			const start = image.getBoundingClientRect();
			const end = target.getBoundingClientRect();
			const flyer = image.cloneNode();
			flyer.className = 'pot-cart-flyer';
			flyer.setAttribute('aria-hidden', 'true');
			flyer.style.left = `${start.left}px`;
			flyer.style.top = `${start.top}px`;
			document.body.append(flyer);
			window.requestAnimationFrame(() => {
				flyer.style.transform = `translate(${end.left + (end.width / 2) - start.left}px, ${end.top + (end.height / 2) - start.top}px) scale(.2)`;
				flyer.style.opacity = '0';
			});
			window.setTimeout(() => flyer.remove(), 600);
		};

		const showAdded = () => {
			window.clearTimeout(resetTimer);
			animateToCart();
			sticky.classList.add('is-added');
			if (label) label.textContent = config.addedToCart || 'Added to cart ✓';
			if (status) status.textContent = config.addedToCart || 'Item added to cart';
			resetTimer = window.setTimeout(() => {
				sticky.classList.remove('is-added');
				if (label) label.textContent = initialLabel;
			}, 1800);
		};
		const observer = new IntersectionObserver(([entry]) => {
			sticky.classList.toggle('is-visible', !entry.isIntersecting);
			sticky.setAttribute('aria-hidden', entry.isIntersecting ? 'true' : 'false');
		}, { threshold: 0 });
		observer.observe(original);
		button.addEventListener('click', () => {
			const addButton = document.querySelector('.single_add_to_cart_button:not(.disabled), .wp-block-woocommerce-product-button button:not(:disabled)');
			if (sticky.dataset.productType === 'simple' && addButton) addButton.click();
			else original.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' });
		});
		document.body.addEventListener('wc-blocks_added_to_cart', showAdded);
		if (window.jQuery) window.jQuery(document.body).on('added_to_cart', showAdded);
	}

	function setupSingleProductViewCart() {
		if (!document.body.classList.contains('single-product') || !config.cartUrl) return;

		const reveal = () => {
			const addButton = document.querySelector('.single_add_to_cart_button') || document.querySelector('.wp-block-woocommerce-product-button button');
			if (!addButton) return;
			const container = addButton.closest('form.cart, .wp-block-woocommerce-product-button, .wp-block-woocommerce-add-to-cart-with-options') || addButton.parentElement;
			if (!container || container.querySelector('.pot-view-cart-link')) return;
			const link = document.createElement('a');
			link.className = 'pot-view-cart-link';
			link.href = config.cartUrl;
			link.textContent = config.viewCart || 'View cart';
			container.append(link);
		};

		document.body.addEventListener('wc-blocks_added_to_cart', reveal);
		if (window.jQuery) window.jQuery(document.body).on('added_to_cart', reveal);
		if (document.querySelector('.woocommerce-message .wc-forward, .woocommerce-notices-wrapper .wc-forward')) reveal();
	}

	function moveShippingProgress() {
		const progress = document.querySelector('#pot-shipping-progress');
		const target = document.querySelector('.wp-block-woocommerce-cart, .wc-block-cart, .wp-block-woocommerce-checkout, .wc-block-checkout');
		if (progress && target) target.parentNode.insertBefore(progress, target);
	}

	function moveCheckoutEnhancements() {
		const inlineOffer = document.querySelector('.pot-inline-offer');
		const trust = document.querySelector('.pot-checkout-trust');
		const accountInvitation = document.querySelector('.pot-account-invitation');
		if (!inlineOffer && !trust && !accountInvitation) return;

		let observer;
		const place = () => {
			let pending = false;
			const cart = document.querySelector('.wp-block-woocommerce-cart, .wc-block-cart');
			const actions = document.querySelector('.wc-block-checkout__actions_row');
			if (inlineOffer) {
				if (actions && inlineOffer.nextElementSibling !== actions) actions.parentNode.insertBefore(inlineOffer, actions);
				else if (!actions) pending = true;
			}
			if (trust) {
				if (cart && trust.nextElementSibling !== cart) cart.parentNode.insertBefore(trust, cart);
				else if (!cart) pending = true;
			}
			if (accountInvitation) {
				const receipt = document.querySelector('main .woocommerce-order, main .wp-block-woocommerce-order-confirmation-status, main');
				if (receipt && accountInvitation.parentNode !== receipt) receipt.append(accountInvitation);
				else if (!receipt) pending = true;
			}
			if (!pending) observer?.disconnect();
			return pending;
		};
		if (place()) {
			observer = new MutationObserver(place);
			observer.observe(document.body, { childList: true, subtree: true });
		}
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
			const parts = [
				{ label: config.daysLabel || 'Days', value: Math.floor(remaining / 86400000) },
				{ label: config.hoursLabel || 'Hours', value: Math.floor((remaining % 86400000) / 3600000) },
				{ label: config.minutesLabel || 'Minutes', value: Math.floor((remaining % 3600000) / 60000) },
				{ label: config.secondsLabel || 'Seconds', value: Math.floor((remaining % 60000) / 1000) },
			];
			if (!output.querySelector('.pot-timer-unit')) {
				output.classList.add('pot-timer-grid');
				output.innerHTML = parts.map((part, index) => `<span class="pot-timer-unit${index === 3 ? ' pot-timer-seconds' : ''}"><span class="pot-timer-value"></span><span class="pot-timer-label">${escapeHtml(part.label)}</span></span>`).join('<span class="pot-timer-separator" aria-hidden="true">:</span>');
			}
			output.querySelectorAll('.pot-timer-value').forEach((value, index) => {
				value.textContent = String(parts[index].value).padStart(2, '0');
			});
			output.setAttribute('aria-label', parts.map((part) => `${part.value} ${part.label.toLowerCase()}`).join(', '));
			return true;
		};
		if (!update()) return;
		const interval = window.setInterval(() => { if (!update()) window.clearInterval(interval); }, 1000);
	}

	function setupShippingProgress() {
		const cartRoot = document.querySelector('.wp-block-woocommerce-cart, .wc-block-cart');
		const progress = document.querySelector('#pot-shipping-progress');
		if (!cartRoot || !progress) return;
		const data = window.wp?.data;
		const message = progress.querySelector('p');
		const bar = progress.querySelector('[role="progressbar"]');
		const fill = bar?.querySelector('span');
		const threshold = Number(progress.dataset.target || config.freeShipping || 0);
		if (!data?.select || !data?.subscribe || !message || !bar || !fill || threshold <= 0) return;

		let lastSubtotal;
		let scheduled = false;
		const formatAmount = (amount, totals) => {
			const decimals = Number(totals.currency_minor_unit ?? 2);
			const decimal = totals.currency_decimal_separator || '.';
			const numeric = amount.toFixed(decimals).replace('.', decimal);
			return `${totals.currency_prefix || config.currencySymbol || ''}${numeric}${totals.currency_suffix || ''}`;
		};
		const update = () => {
			scheduled = false;
			const totals = data.select('wc/store/cart')?.getCartTotals?.();
			if (!totals || totals.total_items === '') return;
			const decimals = Number(totals.currency_minor_unit ?? 2);
			const subtotal = Number(totals.total_items) / (10 ** decimals);
			if (!Number.isFinite(subtotal) || subtotal === lastSubtotal) return;
			lastSubtotal = subtotal;
			progress.hidden = subtotal <= 0;
			const remaining = Math.max(0, threshold - subtotal);
			const percentage = Math.min(100, (subtotal / threshold) * 100);
			message.textContent = remaining > 0
				? (config.freeShippingRemaining || 'Add %s more to reach the configured free-shipping threshold.').replace('%s', formatAmount(remaining, totals))
				: (config.freeShippingReached || 'You reached the configured free-shipping threshold.');
			bar.setAttribute('aria-valuenow', String(Math.round(percentage)));
			fill.style.width = `${percentage}%`;
		};
		const schedule = () => {
			if (scheduled) return;
			scheduled = true;
			window.requestAnimationFrame(update);
		};
		update();
		data.subscribe(schedule);
	}

	function setupReturnToBuyBox() {
		const buyBox = document.querySelector('#pot-product-buy-box');
		if (!buyBox) return;
		document.querySelectorAll('.pot-return-to-buy-box').forEach((link) => {
			link.addEventListener('click', (event) => {
				event.preventDefault();
				buyBox.scrollIntoView({
					behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
					block: 'center',
				});
				window.setTimeout(() => buyBox.focus({ preventScroll: true }), 250);
			});
		});
	}

	document.addEventListener('DOMContentLoaded', () => {
		if (features.promo) setupPromoCode();
		if (features.search) setupSearch();
		if (features.wishlist) {
			setupWishlistButtons();
			renderWishlistPage();
		}
		if (features.countdowns) setupCountdowns();
		if (features.product) {
			setupStickyCart();
			setupSingleProductViewCart();
			setupReturnToBuyBox();
		}
		if (features.commerce) {
			moveShippingProgress();
			moveCheckoutEnhancements();
		}
		if (features.checkout) labelCheckoutFields();
		if (features.recentPurchases) setupRecentPurchases();
		if (features.offer) setupOfferCountdown();
		if (features.cart) setupShippingProgress();
	});
})();
