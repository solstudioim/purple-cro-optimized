/* global potOfferToggle */
(function () {
	'use strict';
	const checkbox = document.getElementById('pot-offer-enabled');
	const status = document.getElementById('pot-offer-toggle-status');
	if (!checkbox || !status) return;
	let saved = checkbox.checked;
	let pending = false;
	// Keep the control in form serialization while preventing overlapping writes.
	checkbox.addEventListener('click', (event) => { if (pending) event.preventDefault(); });
	checkbox.form.addEventListener('submit', (event) => { if (pending) event.preventDefault(); });
	checkbox.addEventListener('change', async () => {
		if (pending) return;
		pending = true;
		checkbox.setAttribute('aria-disabled', 'true');
		status.textContent = potOfferToggle.saving;
		try {
			const response = await fetch(potOfferToggle.url, {
				method: 'POST',
				credentials: 'same-origin',
				body: new URLSearchParams({ action: 'pot_set_offer_enabled', nonce: potOfferToggle.nonce, enabled: checkbox.checked ? '1' : '0' }),
			});
			const result = await response.json();
			if (!response.ok || !result.success || typeof result.data?.enabled !== 'boolean') throw new Error('Save failed');
			saved = result.data.enabled;
			checkbox.checked = saved;
			status.textContent = saved ? potOfferToggle.enabled : potOfferToggle.disabled;
		} catch (error) {
			checkbox.checked = saved;
			status.textContent = potOfferToggle.error;
		} finally {
			pending = false;
			checkbox.removeAttribute('aria-disabled');
		}
	});
})();
