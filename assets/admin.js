(function () {
	const output = document.getElementById('npcink-abilities-toolkit-admin-output');
	const root = output ? output.closest('.npcink-abilities-toolkit-admin') : null;
	const nonce = root ? root.getAttribute('data-rest-nonce') : '';
	const adminAjaxUrl = root ? root.getAttribute('data-admin-ajax-url') : '';
	const adminNonce = root ? root.getAttribute('data-admin-nonce') : '';
	const copiedLabel = root ? root.getAttribute('data-copied-label') : 'Copied';
	const requestingLabel = root ? root.getAttribute('data-requesting-label') : 'Requesting';
	const runningLabel = root ? root.getAttribute('data-running-label') : 'Running';

	async function runRequest(url, options) {
		if (!output) {
			return;
		}

		options = options || {};
		output.value = requestingLabel + ' ' + url + ' ...';
		try {
			const headers = {
				'X-WP-Nonce': nonce,
				'Accept': 'application/json'
			};
			const fetchOptions = {
				credentials: 'same-origin',
				headers: headers,
				method: options.method || 'GET'
			};
			if (options.body) {
				headers['Content-Type'] = 'application/json';
				fetchOptions.body = JSON.stringify(options.body);
			}
			const response = await fetch(url, fetchOptions);
			const text = await response.text();
			let body = text;
			try {
				body = JSON.stringify(JSON.parse(text), null, 2);
			} catch (error) {}
			output.value = 'HTTP ' + response.status + '\n\n' + body;
		} catch (error) {
			output.value = String(error && error.message ? error.message : error);
		}
	}

	document.querySelectorAll('[data-npcink-abilities-toolkit-fetch]').forEach(function (button) {
		button.addEventListener('click', function () {
			runRequest(button.getAttribute('data-npcink-abilities-toolkit-fetch'));
		});
	});

	async function runReadonlyCheck(check) {
		if (!output || !adminAjaxUrl) {
			return;
		}

		output.value = runningLabel + ' ' + check + ' ...';
		const form = new URLSearchParams();
		form.append('action', 'npcink_abilities_toolkit_readonly_check');
		form.append('nonce', adminNonce);
		form.append('check', check);

		try {
			const response = await fetch(adminAjaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				body: form.toString()
			});
			const text = await response.text();
			let payload = null;
			try {
				payload = JSON.parse(text);
			} catch (error) {}
			if (payload && typeof payload === 'object' && 'status' in payload) {
				output.value = 'REST ' + payload.status + '\n\n' + JSON.stringify(payload.body, null, 2);
				return;
			}
			output.value = 'HTTP ' + response.status + '\n\n' + text;
		} catch (error) {
			output.value = String(error && error.message ? error.message : error);
		}
	}

	document.querySelectorAll('[data-npcink-abilities-toolkit-readonly-check]').forEach(function (button) {
		button.addEventListener('click', function () {
			runReadonlyCheck(button.getAttribute('data-npcink-abilities-toolkit-readonly-check'));
		});
	});

	document.querySelectorAll('[data-npcink-abilities-toolkit-copy]').forEach(function (button) {
		button.addEventListener('click', async function () {
			const target = document.getElementById(button.getAttribute('data-npcink-abilities-toolkit-copy'));
			if (!target) {
				return;
			}

			const value = target.value || target.textContent || '';
			try {
				await navigator.clipboard.writeText(value);
				button.textContent = copiedLabel;
			} catch (error) {
				if (typeof target.focus === 'function') {
					target.focus();
				}
				if (typeof target.select === 'function') {
					target.select();
				} else if (output) {
					output.value = value;
				}
			}
		});
	});
})();
