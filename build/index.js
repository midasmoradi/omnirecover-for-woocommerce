/**
 * Minimal admin UI (prebuilt). Run `npm run build` to replace with the bundle from src/.
 */
(function () {
	const root = document.getElementById('omnirecover-root');
	const cfg = window.omnirecoverAdmin;
	if (!root || !cfg) {
		return;
	}

	function api(path, opts) {
		opts = opts || {};
		const headers = Object.assign({ 'X-WP-Nonce': cfg.nonce }, opts.headers || {});
		let body = opts.body;
		if (body && typeof body === 'object') {
			headers['Content-Type'] = 'application/json';
			body = JSON.stringify(body);
		}
		return fetch(cfg.restRoot + path, {
			method: opts.method || 'GET',
			headers: headers,
			credentials: 'same-origin',
			body: body,
		}).then(function (r) {
			return r.json().then(function (j) {
				if (!r.ok) {
					throw new Error(j.message || r.statusText || 'Request failed');
				}
				return j;
			});
		});
	}

	let state = {};

	function collectSettings() {
		const s = {};
		const channel = document.getElementById('omnirecover-channel');
		const delay = document.getElementById('omnirecover-delay');
		if (channel) {
			s.active_channel = channel.value;
		}
		if (delay) {
			s.abandon_delay_minutes = parseInt(delay.value, 10) || 120;
		}
		[
			'email_subject',
			'email_body',
			'whatsapp_body',
			'telegram_body',
			'sms_body',
			'ultramsg_instance',
			'ultramsg_token',
			'telegram_bot_token',
			'twilio_sid',
			'twilio_token',
			'twilio_from',
		].forEach(function (id) {
			const el = document.getElementById('omnirecover-' + id);
			if (el) {
				s[id] = el.value;
			}
		});
		return s;
	}

	function render(settings) {
		state = settings;
		root.innerHTML = '';
		const wrap = document.createElement('div');
		wrap.className = 'omnirecover-admin';

		const h = document.createElement('h2');
		h.textContent = 'OmniRecover';
		wrap.appendChild(h);

		const stats = document.createElement('div');
		stats.className = 'omnirecover-stats';
		wrap.appendChild(stats);

		api('omnirecover/v1/analytics')
			.then(function (a) {
				stats.innerHTML =
					'<p>Carts recovered: ' +
					(a.carts_recovered || 0) +
					'</p><p>Messages sent: ' +
					(a.messages_sent || 0) +
					'</p><p>Revenue: ' +
					(a.revenue_total || 0) +
					'</p>';
			})
			.catch(function () {
				stats.innerHTML = '<p>Analytics unavailable</p>';
			});

		const form = document.createElement('div');
		form.className = 'omnirecover-form';

		const lab1 = document.createElement('label');
		lab1.htmlFor = 'omnirecover-channel';
		lab1.textContent = 'Active channel';
		form.appendChild(lab1);
		const sel = document.createElement('select');
		sel.id = 'omnirecover-channel';
		['email', 'whatsapp', 'telegram', 'sms'].forEach(function (v) {
			const o = document.createElement('option');
			o.value = v;
			o.textContent = v;
			sel.appendChild(o);
		});
		sel.value = settings.active_channel || 'email';
		form.appendChild(sel);

		const lab2 = document.createElement('label');
		lab2.htmlFor = 'omnirecover-delay';
		lab2.textContent = 'Abandon delay (minutes)';
		form.appendChild(lab2);
		const delay = document.createElement('input');
		delay.type = 'number';
		delay.id = 'omnirecover-delay';
		delay.value = String(settings.abandon_delay_minutes || 120);
		form.appendChild(delay);

		function addField(id, label, multiline) {
			const lab = document.createElement('label');
			lab.htmlFor = 'omnirecover-' + id;
			lab.textContent = label;
			form.appendChild(lab);
			const inp = multiline ? document.createElement('textarea') : document.createElement('input');
			inp.id = 'omnirecover-' + id;
			if (multiline) {
				inp.rows = 4;
			} else {
				inp.type = 'text';
			}
			inp.style.width = '100%';
			inp.style.maxWidth = '640px';
			inp.value = settings[id] || '';
			form.appendChild(inp);
		}

		addField('email_subject', 'Email subject', false);
		addField('email_body', 'Email body', true);
		addField('whatsapp_body', 'WhatsApp body', true);
		addField('telegram_body', 'Telegram body', true);
		addField('sms_body', 'SMS body', true);
		addField('ultramsg_instance', 'UltraMsg instance', false);
		addField('ultramsg_token', 'UltraMsg token', false);
		addField('telegram_bot_token', 'Telegram bot token', false);
		addField('twilio_sid', 'Twilio SID', false);
		addField('twilio_token', 'Twilio token', false);
		addField('twilio_from', 'Twilio From', false);

		wrap.appendChild(form);

		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'button button-primary';
		btn.textContent = 'Save settings';
		btn.addEventListener('click', function () {
			btn.disabled = true;
			const merged = Object.assign({}, state, collectSettings());
			api('omnirecover/v1/settings', { method: 'POST', body: merged })
				.then(function (s) {
					btn.disabled = false;
					window.alert('Saved');
					render(s);
				})
				.catch(function (e) {
					btn.disabled = false;
					window.alert(e.message || String(e));
				});
		});
		wrap.appendChild(btn);

		root.appendChild(wrap);
	}

	api('omnirecover/v1/settings')
		.then(function (s) {
			render(s);
		})
		.catch(function () {
			root.innerHTML = '<p class="notice notice-error">Could not load settings.</p>';
		});
})();
