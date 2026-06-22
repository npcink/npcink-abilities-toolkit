(function () {
	const output = document.getElementById('npcink-abilities-toolkit-admin-output');
	const root = output ? output.closest('.npcink-abilities-toolkit-admin') : null;
	const checkSummary = document.getElementById('npcink-abilities-toolkit-check-summary');
	const checkSummaryBody = document.getElementById('npcink-abilities-toolkit-check-summary-body');
	const checkEmpty = document.getElementById('npcink-abilities-toolkit-check-empty');
	const nonce = root ? root.getAttribute('data-rest-nonce') : '';
	const adminAjaxUrl = root ? root.getAttribute('data-admin-ajax-url') : '';
	const adminNonce = root ? root.getAttribute('data-admin-nonce') : '';
	const copiedLabel = root ? root.getAttribute('data-copied-label') : 'Copied';
	const requestingLabel = root ? root.getAttribute('data-requesting-label') : 'Requesting';
	const runningLabel = root ? root.getAttribute('data-running-label') : 'Running';
	const summaryLabels = parseSummaryLabels();

	function parseSummaryLabels() {
		const defaults = {
			ability: 'Ability',
			abilities_api: 'Abilities API',
			abilities_routes: 'Abilities routes found',
			available: 'Available',
			block_theme: 'Block theme',
			category_registration: 'Category registration',
			check: 'Check',
			configured: 'Configured',
			debug: 'Debug logging',
			diagnostics_check: 'Diagnostics check',
			disabled: 'Disabled',
			enabled: 'Enabled',
			external_cache: 'External cache active',
			https: 'HTTPS',
			language_timezone: 'Language and timezone',
			needs_attention: 'Needs attention',
			no: 'No',
			not_configured: 'Not configured',
			object_cache: 'Object cache',
			omitted: 'Sensitive data',
			omitted_details: 'Sensitive fields are omitted from this check.',
			passed: 'Passed',
			permalink: 'Permalinks',
			raw_response: 'Raw response for support',
			rest_api: 'REST API',
			routes: 'Routes',
			runtime_cache: 'Runtime cache only',
			site_host: 'Site host',
			site_name: 'Site name',
			site_url: 'Site URL',
			status: 'Status',
			theme: 'Theme',
			unavailable: 'Unavailable',
			wordpress: 'WordPress',
			wordpress_https: 'WordPress HTTPS',
			yes: 'Yes'
		};

		if (!root) {
			return defaults;
		}

		try {
			const labels = JSON.parse(root.getAttribute('data-check-summary-labels') || '{}');
			if (labels && typeof labels === 'object') {
				return Object.assign(defaults, labels);
			}
		} catch (error) {}

		return defaults;
	}

	function summaryLabel(key, fallback) {
		return summaryLabels[key] || fallback || key;
	}

	function textValue(value) {
		if (Array.isArray(value)) {
			return value.join(', ');
		}

		if (value === true) {
			return summaryLabel('yes', 'Yes');
		}

		if (value === false) {
			return summaryLabel('no', 'No');
		}

		if (value === null || typeof value === 'undefined') {
			return '';
		}

		return String(value);
	}

	function setCheckSummary(rows) {
		if (!checkSummary || !checkSummaryBody) {
			return;
		}

		checkSummaryBody.textContent = '';
		if (!rows || !rows.length) {
			checkSummary.hidden = true;
			if (checkEmpty) {
				checkEmpty.hidden = false;
			}
			return;
		}

		rows.forEach(function (row) {
			const tableRow = document.createElement('tr');
			const item = document.createElement('th');
			const result = document.createElement('td');
			const details = document.createElement('td');

			item.scope = 'row';
			item.textContent = textValue(row.item);
			result.textContent = textValue(row.result);
			details.textContent = textValue(row.details);

			tableRow.appendChild(item);
			tableRow.appendChild(result);
			tableRow.appendChild(details);
			checkSummaryBody.appendChild(tableRow);
		});

		checkSummary.hidden = false;
		if (checkEmpty) {
			checkEmpty.hidden = true;
		}
	}

	function compactParts(parts) {
		return parts.filter(function (part) {
			return part !== null && typeof part !== 'undefined' && String(part) !== '';
		}).map(function (part) {
			return String(part);
		}).join(' / ');
	}

	function availability(value) {
		return value ? summaryLabel('available', 'Available') : summaryLabel('unavailable', 'Unavailable');
	}

	function configured(value) {
		return value ? summaryLabel('configured', 'Configured') : summaryLabel('not_configured', 'Not configured');
	}

	function summarizeReadonlyPayload(payload, checkLabel) {
		const body = payload && payload.body && typeof payload.body === 'object' ? payload.body : {};
		const status = payload && typeof payload.status !== 'undefined' ? Number(payload.status) : 0;
		const rows = [
			{
				item: summaryLabel('check', 'Check'),
				result: checkLabel || '',
				details: payload && payload.ability_id ? payload.ability_id : ''
			},
			{
				item: summaryLabel('status', 'Status'),
				result: status > 0 && status < 400 ? summaryLabel('passed', 'Passed') : summaryLabel('needs_attention', 'Needs attention'),
				details: status > 0 ? 'REST ' + status : ''
			}
		];

		if (body.code || body.message) {
			rows.push({
				item: summaryLabel('raw_response', 'Raw response for support'),
				result: summaryLabel('needs_attention', 'Needs attention'),
				details: body.message || body.code
			});
			return rows;
		}

		if (body.name || body.description || body.home_url || body.site_url) {
			rows.push({
				item: summaryLabel('site_name', 'Site name'),
				result: body.name || '',
				details: body.description || ''
			});
			rows.push({
				item: summaryLabel('site_url', 'Site URL'),
				result: body.home_url || body.site_url || '',
				details: body.site_url && body.home_url && body.site_url !== body.home_url ? body.site_url : ''
			});
			rows.push({
				item: summaryLabel('language_timezone', 'Language and timezone'),
				result: compactParts([body.language, body.timezone]),
				details: body.wp_version ? summaryLabel('wordpress', 'WordPress') + ' ' + body.wp_version : ''
			});
			if (body.theme) {
				rows.push({
					item: summaryLabel('theme', 'Theme'),
					result: body.theme,
					details: ''
				});
			}
		}

		if (body.site && typeof body.site === 'object') {
			rows.push({
				item: summaryLabel('site_name', 'Site name'),
				result: body.site.name || '',
				details: body.site.description || ''
			});
			rows.push({
				item: summaryLabel('site_host', 'Site host'),
				result: compactParts([body.site.home_url_host, body.site.site_url_host]),
				details: body.site.permalink_mode ? summaryLabel('permalink', 'Permalinks') + ': ' + body.site.permalink_mode : ''
			});
		}

		if (body.wordpress && typeof body.wordpress === 'object') {
			rows.push({
				item: summaryLabel('wordpress', 'WordPress'),
				result: body.wordpress.version || '',
				details: body.wordpress.environment_type || ''
			});
			if (body.wordpress.debug && typeof body.wordpress.debug === 'object') {
				rows.push({
					item: summaryLabel('debug', 'Debug logging'),
					result: body.wordpress.debug.wp_debug_log ? summaryLabel('enabled', 'Enabled') : summaryLabel('disabled', 'Disabled'),
					details: 'WP_DEBUG: ' + textValue(body.wordpress.debug.wp_debug)
				});
			}
		}

		if (body.theme && typeof body.theme === 'object') {
			rows.push({
				item: summaryLabel('theme', 'Theme'),
				result: compactParts([body.theme.name, body.theme.version]),
				details: summaryLabel('block_theme', 'Block theme') + ': ' + textValue(body.theme.is_block_theme)
			});
		}

		if (body.https && typeof body.https === 'object') {
			rows.push({
				item: summaryLabel('https', 'HTTPS'),
				result: body.https.home_url_https && body.https.site_url_https ? configured(true) : configured(false),
				details: summaryLabel('wordpress_https', 'WordPress HTTPS') + ': ' + textValue(body.https.wp_using_https)
			});
		}

		if (body.rest_api && typeof body.rest_api === 'object') {
			rows.push({
				item: summaryLabel('rest_api', 'REST API'),
				result: availability(body.rest_api.available),
				details: compactParts([
					body.rest_api.route_count ? body.rest_api.route_count + ' ' + summaryLabel('routes', 'Routes') : '',
					body.rest_api.wp_abilities_routes_found ? summaryLabel('abilities_routes', 'Abilities routes found') : ''
				])
			});
		}

		if (body.abilities_api && typeof body.abilities_api === 'object') {
			rows.push({
				item: summaryLabel('abilities_api', 'Abilities API'),
				result: body.abilities_api.register_ability_available ? availability(true) : availability(false),
				details: compactParts([
					body.abilities_api.register_category_available ? summaryLabel('category_registration', 'Category registration') : '',
					body.abilities_api.diagnostics_summary_registered ? summaryLabel('diagnostics_check', 'Diagnostics check') : ''
				])
			});
		}

		if (body.object_cache && typeof body.object_cache === 'object') {
			rows.push({
				item: summaryLabel('object_cache', 'Object cache'),
				result: body.object_cache.external_object_cache ? summaryLabel('external_cache', 'External cache active') : summaryLabel('runtime_cache', 'Runtime cache only'),
				details: body.object_cache.wp_cache_available ? availability(true) : availability(false)
			});
		}

		if (body.rewrite && typeof body.rewrite === 'object') {
			rows.push({
				item: summaryLabel('permalink', 'Permalinks'),
				result: body.rewrite.permalink_mode || '',
				details: body.rewrite.permalink_structure || ''
			});
		}

		if (Array.isArray(body.omitted) && body.omitted.length) {
			rows.push({
				item: summaryLabel('omitted', 'Sensitive data'),
				result: summaryLabel('omitted_details', 'Sensitive fields are omitted from this check.'),
				details: body.omitted.slice(0, 6).join(', ')
			});
		}

		if (rows.length === 2) {
			rows.push({
				item: summaryLabel('raw_response', 'Raw response for support'),
				result: summaryLabel('available', 'Available'),
				details: summaryLabel('omitted_details', 'Sensitive fields are omitted from this check.')
			});
		}

		return rows;
	}

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

	async function runReadonlyCheck(check, checkLabel) {
		if (!output || !adminAjaxUrl) {
			return;
		}

		output.value = runningLabel + ' ' + check + ' ...';
		setCheckSummary([
			{
				item: summaryLabel('check', 'Check'),
				result: checkLabel || check,
				details: runningLabel
			}
		]);
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
				setCheckSummary(summarizeReadonlyPayload(payload, checkLabel || check));
				return;
			}
			output.value = 'HTTP ' + response.status + '\n\n' + text;
			setCheckSummary([
				{
					item: summaryLabel('status', 'Status'),
					result: summaryLabel('needs_attention', 'Needs attention'),
					details: 'HTTP ' + response.status
				}
			]);
		} catch (error) {
			const message = String(error && error.message ? error.message : error);
			output.value = message;
			setCheckSummary([
				{
					item: summaryLabel('status', 'Status'),
					result: summaryLabel('needs_attention', 'Needs attention'),
					details: message
				}
			]);
		}
	}

	document.querySelectorAll('[data-npcink-abilities-toolkit-readonly-check]').forEach(function (button) {
		button.addEventListener('click', function () {
			runReadonlyCheck(button.getAttribute('data-npcink-abilities-toolkit-readonly-check'), button.textContent.trim());
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
