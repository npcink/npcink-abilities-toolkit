(function () {
	const output = document.getElementById('npcink-abilities-toolkit-admin-output');
	const root = output ? output.closest('.npcink-abilities-toolkit-admin') : null;
	const nonce = root ? root.getAttribute('data-rest-nonce') : '';
	const copiedLabel = root ? root.getAttribute('data-copied-label') : 'Copied';

	async function runRequest(url) {
		if (!output) {
			return;
		}

		output.value = 'Requesting ' + url + ' ...';
		try {
			const response = await fetch(url, {
				credentials: 'same-origin',
				headers: {
					'X-WP-Nonce': nonce,
					'Accept': 'application/json'
				}
			});
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

	document.querySelectorAll('[data-npcink-abilities-toolkit-copy]').forEach(function (button) {
		button.addEventListener('click', async function () {
			const target = document.getElementById(button.getAttribute('data-npcink-abilities-toolkit-copy'));
			if (!target) {
				return;
			}

			try {
				await navigator.clipboard.writeText(target.value || target.textContent || '');
				button.textContent = copiedLabel;
			} catch (error) {
				target.focus();
				target.select();
			}
		});
	});
})();
