// ============================================================
// HK Builders CRM: App JS
// ============================================================

// ---- Sidebar (mobile) ---------------------------------------
var hamburger = document.getElementById('hamburger');
var sidebar   = document.querySelector('.sidebar');
var overlay   = document.getElementById('sidebarOverlay');

function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('open');
    hamburger.classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('open');
    hamburger.classList.remove('open');
    document.body.style.overflow = '';
}

if (hamburger) {
    hamburger.addEventListener('click', function() {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
}

if (overlay) {
    overlay.addEventListener('click', closeSidebar);
}

// Close sidebar when a nav link is clicked on mobile
if (sidebar) {
    sidebar.querySelectorAll('a').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) closeSidebar();
        });
    });
}

// Close sidebar on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSidebar();
        // also close modals
        document.querySelectorAll('.modal-overlay.open').forEach(function(m) {
            m.classList.remove('open');
        });
        document.body.style.overflow = '';
    }
});

// ---- Modals -------------------------------------------------
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
        document.body.style.overflow = '';
    }
});

// ---- Phone card tables (.table-cards) -------------------------
// Fills in what a view did not mark by hand:
//   data-label  = the column header (empty header = no label)
//   .tc-title   = first cell, when the row has no title cell
//   .tc-actions = a cell with buttons/links under an empty header
document.querySelectorAll('table.table-cards').forEach(function(table) {
    var heads = Array.prototype.map.call(table.querySelectorAll('thead th'), function(th) {
        return th.textContent.trim();
    });
    if (!heads.length) return;
    table.querySelectorAll('tbody tr').forEach(function(tr) {
        var cells = Array.prototype.slice.call(tr.children);
        if (cells.length === 1) return;   // "no rows" message
        var hasTitle = tr.querySelector('.tc-title');
        var col = 0;
        cells.forEach(function(td) {
            var span = parseInt(td.getAttribute('colspan') || '1', 10);
            var cl = td.classList;
            var marked = cl.contains('tc-title') || cl.contains('tc-actions') || cl.contains('tc-hide');
            if (!marked && !hasTitle) {
                cl.add('tc-title'); hasTitle = true;
            } else if (!marked && !heads[col] && td.querySelector('a, button, form')) {
                cl.add('tc-actions');
            } else if (!marked && span === 1 && heads[col] && !td.hasAttribute('data-label')) {
                td.setAttribute('data-label', heads[col]);
            }
            col += span;
        });
    });
});

// ---- Call button + "how did the call go" popup ---------------
// Tapping Call saves the call, then the phone dialer opens (normal tel: link).
// When the agent comes back to the CRM, the popup asks for the result and
// talk time (pre-filled from the time they were away).
(function() {
    var modal = document.getElementById('callModal');
    var form  = document.getElementById('callForm');
    if (!modal || !form) return;

    var KEY      = 'hkPendingCall';
    var MAX_AGE  = 3 * 60 * 60 * 1000;
    var csrf     = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var endpoint = (document.querySelector('meta[name="call-endpoint"]') || {}).content || '';
    var talkWrap = document.getElementById('callTalkWrap');
    var errBox   = document.getElementById('callError');

    function getPending() {
        try {
            var p = JSON.parse(localStorage.getItem(KEY) || 'null');
            if (p && Date.now() - p.t < MAX_AGE) return p;
        } catch (e) {}
        clearPending();
        return null;
    }
    function setPending(p) { try { localStorage.setItem(KEY, JSON.stringify(p)); } catch (e) {} }
    function clearPending() { try { localStorage.removeItem(KEY); } catch (e) {} }

    function post(data) {
        var fd = new FormData();
        Object.keys(data).forEach(function(k) { fd.append(k, data[k]); });
        return fetch(endpoint, {
            method: 'POST', body: fd, credentials: 'same-origin', keepalive: true,
            headers: { 'X-CSRF-Token': csrf }
        }).then(function(r) { return r.json(); });
    }

    function showPopup() {
        var p = getPending();
        if (!p || modal.classList.contains('open')) return;
        if (!p.id) {                      // start request still on its way
            if ((p.tries || 0) < 10) { p.tries = (p.tries || 0) + 1; setPending(p); setTimeout(showPopup, 500); }
            return;
        }
        form.reset();
        errBox.style.display = 'none';
        talkWrap.style.display = 'none';
        var away = Math.max(0, Math.round((Date.now() - p.t) / 1000));
        document.getElementById('callMin').value = Math.floor(away / 60);
        document.getElementById('callSec').value = away % 60;
        openModal('callModal');
    }

    function closePopup() { closeModal('callModal'); }

    document.querySelectorAll('.js-call').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();          // rows that open the lead on click
            var p = { t: Date.now(), id: null };
            setPending(p);
            post({ action: 'start', lead_id: btn.dataset.lead || 0, client_id: btn.dataset.client || 0 })
                .then(function(d) {
                    var cur = getPending();
                    if (!d.ok) { clearPending(); alert(d.error || 'Could not save the call.'); return; }
                    if (cur && cur.t === p.t) { cur.id = d.id; setPending(cur); }
                })
                .catch(function() {});
            // Desktop or no dialer: the page never hides, so ask after a moment
            setTimeout(function() { if (!document.hidden) showPopup(); }, 2500);
        });
    });

    form.querySelectorAll('input[name="outcome"]').forEach(function(r) {
        r.addEventListener('change', function() {
            talkWrap.style.display = r.value === 'answered' ? '' : 'none';
        });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var p = getPending();
        var outcome = (form.querySelector('input[name="outcome"]:checked') || {}).value;
        if (!p || !p.id) { closePopup(); return; }
        if (!outcome) return;
        var mins = parseInt(document.getElementById('callMin').value, 10) || 0;
        var secs = parseInt(document.getElementById('callSec').value, 10) || 0;
        post({
            action: 'finish', id: p.id, outcome: outcome,
            duration_sec: outcome === 'answered' ? mins * 60 + secs : 0,
            note: document.getElementById('callNote').value
        }).then(function(d) {
            if (!d.ok) { errBox.textContent = d.error || 'Could not save.'; errBox.style.display = ''; return; }
            clearPending();
            closePopup();
            if (document.getElementById('contactHistory')) location.reload();
        }).catch(function() {
            errBox.textContent = 'No internet. Try again.';
            errBox.style.display = '';
        });
    });

    modal.querySelectorAll('[data-call-skip]').forEach(function(b) {
        b.addEventListener('click', function() { clearPending(); closePopup(); });
    });

    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) showPopup();
    });
    // Page reloaded or reopened after the call (phone may have closed the tab)
    if (getPending()) setTimeout(showPopup, 300);
})();

// ---- Agent location (agents and sales managers only) ----------
// While an agent is logged in and the CRM is open, send the position at
// most once a minute: every 3 minutes, or sooner after moving 150 m.
// Location is required: the CRM stays covered until it is on.
// The top bar badge shows whether sharing works.
(function() {
    var meta  = document.querySelector('meta[name="location-endpoint"]');
    var badge = document.getElementById('locBadge');
    if (!meta || !badge) return;

    var endpoint = meta.content;
    var csrf     = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var EVERY_MS = 3 * 60 * 1000, MIN_MS = 60 * 1000, MOVE_M = 150;
    var lastSent = 0, lastPos = null, battery = null;
    var label = badge.querySelector('span');

    var states = {
        waiting: ['loc-wait', 'Allow location', 'Your browser is asking to share your location. Click Allow in the popup, or click the location icon in the address bar and choose Allow.'],
        on:      ['loc-on',  'Location on',  'Your location is shared with admin while you are logged in.'],
        denied:  ['loc-off', 'Location off', 'Location is blocked. Click the lock icon next to the web address, set Location to Allow, then reload the page. On a laptop also turn on Windows Settings > Privacy > Location.'],
        error:   ['loc-off', 'No location',  'Your device could not find your location. Turn on Location (GPS on a phone, Windows location on a laptop) and reload the page.'],
        nosupport: ['loc-off', 'No location', 'This browser cannot share location.']
    };
    var current = null, hasFix = false;
    // Don't flash the notice on every page while an allowed browser finds the
    // position: wait until it says "not allowed yet" or 4 s pass with no fix
    var holdGate = true;
    setTimeout(function() { holdGate = false; if (current === 'waiting') setState('waiting'); }, 4000);

    // Location is required: until the browser gives a position, a full-screen
    // notice covers the CRM. It cannot be closed, only logged out of.
    var logoutLink = document.querySelector('.sidebar-logout');
    var gate = document.createElement('div');
    gate.id = 'locGate';
    gate.className = 'loc-gate';
    gate.innerHTML =
        '<div class="loc-gate-box" role="dialog" aria-modal="true" aria-labelledby="locGateTitle">'
      +   '<div class="loc-gate-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg></div>'
      +   '<h2 id="locGateTitle">Turn on your location</h2>'
      +   '<p>The CRM shares your location with admin while you are logged in. You can use the CRM once location is on.</p>'
      +   '<p class="loc-gate-step"></p>'
      +   '<button type="button" class="btn btn-primary loc-gate-allow">Allow location</button>'
      +   '<button type="button" class="btn btn-secondary loc-gate-reload">I turned it on, reload</button>'
      +   (logoutLink ? '<a href="' + logoutLink.href + '" class="loc-gate-logout">Log out</a>' : '')
      + '</div>';
    document.body.appendChild(gate);

    function setState(s) {
        current = s;
        badge.classList.remove('loc-on', 'loc-off', 'loc-wait');
        badge.classList.add(states[s][0]);
        label.textContent = states[s][1];
        badge.title = states[s][2];
        var blocked = s !== 'on' && !(s === 'waiting' && holdGate);
        gate.classList.toggle('open', blocked);
        document.body.classList.toggle('loc-locked', blocked);
        gate.querySelector('.loc-gate-step').textContent = states[s][2];
        gate.querySelector('.loc-gate-allow').style.display = s === 'denied' || s === 'nosupport' ? 'none' : '';
    }

    // Asking from a button press makes the browser show its permission popup
    function askNow() {
        navigator.geolocation.getCurrentPosition(function(p) { onPosition(p, true); }, onError, opts);
    }
    badge.addEventListener('click', function() { if (current) alert(states[current][2]); });
    gate.querySelector('.loc-gate-allow').addEventListener('click', askNow);
    gate.querySelector('.loc-gate-reload').addEventListener('click', function() { location.reload(); });

    if (!navigator.geolocation) { setState('nosupport'); return; }
    setState('waiting');
    if (navigator.permissions && navigator.permissions.query) {
        navigator.permissions.query({ name: 'geolocation' }).then(function(perm) {
            if (perm.state === 'denied') setState('denied');
            if (perm.state === 'prompt') { holdGate = false; if (current === 'waiting') setState('waiting'); }
            perm.onchange = function() {
                if (perm.state === 'denied') setState('denied');
                if (perm.state === 'granted') askNow();
            };
        }).catch(function() {});
    }
    if (navigator.getBattery) {
        navigator.getBattery().then(function(b) {
            battery = Math.round(b.level * 100);
            b.addEventListener('levelchange', function() { battery = Math.round(b.level * 100); });
        }).catch(function() {});
    }

    function metres(a, b) {
        var r = 6371000, rad = Math.PI / 180;
        var dLat = (b.lat - a.lat) * rad, dLng = (b.lng - a.lng) * rad;
        var h = Math.sin(dLat / 2) * Math.sin(dLat / 2)
              + Math.cos(a.lat * rad) * Math.cos(b.lat * rad) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return 2 * r * Math.asin(Math.min(1, Math.sqrt(h)));
    }

    function onPosition(pos, force) {
        hasFix = true;
        setState('on');
        var p = { lat: pos.coords.latitude, lng: pos.coords.longitude, acc: pos.coords.accuracy };
        var now = Date.now();
        if (now - lastSent < MIN_MS) return;
        if (!force && lastPos && now - lastSent < EVERY_MS && metres(lastPos, p) < MOVE_M) return;
        lastSent = now;
        lastPos = p;
        var fd = new FormData();
        fd.append('lat', p.lat);
        fd.append('lng', p.lng);
        fd.append('accuracy', Math.round(p.acc));
        if (battery !== null) fd.append('battery', battery);
        fetch(endpoint, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf } })
            .catch(function() { lastSent = 0; });   // retry on the next fix
    }
    // Blocked = locked out. A weak signal after a good fix (indoors, lift) is
    // not the agent's fault, so that only locks the CRM if no fix ever came.
    function onError(err) {
        if (err.code === 1) setState('denied');
        else if (!hasFix) setState('error');
    }

    var opts = { enableHighAccuracy: true, maximumAge: 60000, timeout: 30000 };
    navigator.geolocation.watchPosition(function(p) { onPosition(p, false); }, onError, opts);
    // Standing still sends no new fixes, so ask again every 3 minutes
    setInterval(function() {
        navigator.geolocation.getCurrentPosition(function(p) { onPosition(p, true); }, onError, opts);
    }, EVERY_MS);
})();

// ---- Reset password modal -----------------------------------
function openResetModal(agentId, agentName) {
    document.getElementById('resetAgentId').value = agentId;
    document.getElementById('resetAgentName').textContent = agentName;
    openModal('resetPwModal');
}
