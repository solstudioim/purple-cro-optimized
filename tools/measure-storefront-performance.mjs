const cdpBase = process.env.POT_CDP_URL || 'http://127.0.0.1:9223';
const siteBase = (process.env.POT_SITE_URL || 'https://purple-optimize.test').replace(/\/$/, '');
const routes = ['/', '/shop/', '/product/petal-crew-sweater/', '/cart/', '/checkout/'];

function send(socket, id, method, params = {}) {
	return new Promise((resolve, reject) => {
		const listener = (event) => {
			const message = JSON.parse(event.data);
			if (message.id !== id) return;
			socket.removeEventListener('message', listener);
			message.error ? reject(new Error(message.error.message)) : resolve(message.result);
		};
		socket.addEventListener('message', listener);
		socket.send(JSON.stringify({ id, method, params }));
	});
}

for (const route of routes) {
	const url = `${siteBase}${route}`;
	const target = await fetch(`${cdpBase}/json/new?${encodeURIComponent(url)}`, { method: 'PUT' }).then((response) => response.json());
	const socket = new WebSocket(target.webSocketDebuggerUrl);
	await new Promise((resolve) => socket.addEventListener('open', resolve, { once: true }));
	let id = 0;
	const command = (method, params) => send(socket, ++id, method, params);
	const requests = new Map();
	let finishLoad;
	const loaded = new Promise((resolve) => { finishLoad = resolve; });

	socket.addEventListener('message', (event) => {
		const message = JSON.parse(event.data);
		if (message.method === 'Network.responseReceived') {
			requests.set(message.params.requestId, { type: message.params.type, bytes: 0 });
		}
		if (message.method === 'Network.loadingFinished' && requests.has(message.params.requestId)) {
			requests.get(message.params.requestId).bytes = message.params.encodedDataLength;
		}
		if (message.method === 'Page.loadEventFired') finishLoad();
	});

	await command('Network.enable');
	await command('Page.enable');
	await command('Emulation.setDeviceMetricsOverride', { width: 390, height: 844, deviceScaleFactor: 1, mobile: true });
	await command('Network.emulateNetworkConditions', {
		offline: false,
		latency: 150,
		downloadThroughput: 200000,
		uploadThroughput: 100000,
		connectionType: 'cellular4g',
	});
	await command('Page.navigate', { url });
	await loaded;
	await new Promise((resolve) => setTimeout(resolve, 1500));

	const result = await command('Runtime.evaluate', {
		returnByValue: true,
		expression: `(() => {
			const navigation = performance.getEntriesByType('navigation')[0];
			const resources = performance.getEntriesByType('resource');
			return {
				ttfb: navigation ? navigation.responseStart : 0,
				domContentLoaded: navigation ? navigation.domContentLoadedEventEnd : 0,
				load: navigation ? navigation.loadEventEnd : 0,
				transferBytes: resources.reduce((sum, entry) => sum + (entry.transferSize || 0), navigation?.transferSize || 0),
				resourceCount: resources.length + 1
			};
		})()`,
	});
	const resourceTypes = {};
	for (const request of requests.values()) {
		resourceTypes[request.type] ||= { count: 0, bytes: 0 };
		resourceTypes[request.type].count += 1;
		resourceTypes[request.type].bytes += request.bytes;
	}
	process.stdout.write(`${JSON.stringify({ url, ...result.result.value, resourceTypes })}\n`);
	socket.close();
}
