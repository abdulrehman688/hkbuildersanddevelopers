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

// ---- Reset password modal -----------------------------------
function openResetModal(agentId, agentName) {
    document.getElementById('resetAgentId').value = agentId;
    document.getElementById('resetAgentName').textContent = agentName;
    openModal('resetPwModal');
}
