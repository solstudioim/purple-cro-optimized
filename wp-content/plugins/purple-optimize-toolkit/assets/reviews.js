(() => {
 'use strict';
 const instances = new Map();
 const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
 const setup = (root) => {
  if (instances.has(root)) return;
  const cards = [...root.querySelectorAll('.pot-testimonial')];
  const controls = root.querySelector('.pot-testimonials-controls');
  if (cards.length < 2 || !controls) return;
  const abort = new AbortController();
  const options = { signal: abort.signal };
  const position = controls.querySelector('.pot-testimonials-position');
  const play = controls.querySelector('[data-review-play]');
  const seconds = Math.max(5, Math.min(30, Number(root.dataset.autoplay) || 0));
  let wantsPlay = Number(root.dataset.autoplay) > 0 && !reduced.matches;
  let index = 0;
  let hovered = false;
  let timer;
  const stop = () => { window.clearInterval(timer); timer = undefined; };
  const show = (next, automatic = false) => {
   index = (next + cards.length) % cards.length;
   cards.forEach((card, i) => { card.hidden = i !== index; });
   position.setAttribute('aria-live', automatic ? 'off' : 'polite');
   position.textContent = (position.dataset.positionTemplate || '%1$d of %2$d').replace('%1$d', String(index + 1)).replace('%2$d', String(cards.length));
  };
  const schedule = () => {
   stop();
   if (reduced.matches) wantsPlay = false;
   if (play) {
    play.disabled = reduced.matches;
    play.setAttribute('aria-label', wantsPlay ? play.dataset.pauseLabel : play.dataset.playLabel);
    play.title = wantsPlay ? play.dataset.pauseLabel : play.dataset.playLabel;
    play.querySelector('span').textContent = wantsPlay ? 'Ⅱ' : '▶';
    play.setAttribute('aria-pressed', String(wantsPlay));
   }
   if (root.isConnected && wantsPlay && !reduced.matches && !hovered && !root.contains(document.activeElement) && !document.hidden) {
    timer = window.setInterval(() => show(index + 1, true), seconds * 1000);
   }
  };
  controls.querySelector('[data-review-previous]').addEventListener('click', () => { show(index - 1); schedule(); }, options);
  controls.querySelector('[data-review-next]').addEventListener('click', () => { show(index + 1); schedule(); }, options);
  play?.addEventListener('click', () => { wantsPlay = !wantsPlay; schedule(); }, options);
  root.addEventListener('mouseenter', () => { hovered = true; schedule(); }, options);
  root.addEventListener('mouseleave', () => { hovered = false; schedule(); }, options);
  root.addEventListener('focusin', schedule, options);
  root.addEventListener('focusout', () => window.setTimeout(schedule, 0), options);
  document.addEventListener('visibilitychange', schedule, options);
  reduced.addEventListener('change', () => { if (reduced.matches) wantsPlay = false; schedule(); }, options);
  root.setAttribute('data-pot-review-ready', '');
  controls.hidden = false;
  show(0);
  schedule();
  instances.set(root, () => { stop(); abort.abort(); });
 };
 const scan = () => {
  for (const [root, dispose] of instances) {
   if (!root.isConnected) { dispose(); instances.delete(root); }
  }
  document.querySelectorAll('[data-pot-review-rotator]').forEach(setup);
 };
 const start = () => { scan(); new MutationObserver(scan).observe(document.body, { childList: true, subtree: true }); };
 if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
 else start();
})();
