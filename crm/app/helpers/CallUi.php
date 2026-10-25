<?php
require_once __DIR__ . '/../models/ContactLog.php';

// Call button, the "how did the call go" popup, and the Contact History card.
// The button is a normal tel: link, so the phone dialer opens even without JS;
// js/app.js saves the call and shows the popup when the agent comes back.
class CallUi {

    private const ICON = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>';

    // "0321-2727270" -> "03212727270", keeps a leading +
    public static function telHref(string $phone): string {
        $p = preg_replace('/[^\d+]/', '', $phone);
        return 'tel:' . (str_starts_with($p, '+') ? '+' . str_replace('+', '', $p) : str_replace('+', '', $p));
    }

    public static function button(?string $phone, int $leadId = 0, int $clientId = 0, string $label = 'Call', string $extraClass = 'btn-sm'): string {
        if (!$phone || !preg_match('/\d{5,}/', preg_replace('/\D/', '', $phone))) return '';
        return '<a href="' . Security::e(self::telHref($phone)) . '" class="btn btn-call js-call ' . $extraClass . '"'
             . ' data-lead="' . $leadId . '" data-client="' . $clientId . '"'
             . ' title="Call ' . Security::e($phone) . '">' . self::ICON . '<span>' . Security::e($label) . '</span></a>';
    }

    // Printed once by the layout
    public static function modal(): string {
        $pills = '';
        foreach (ContactLog::OUTCOMES as $key => $label) {
            $pills .= '<label class="call-pill"><input type="radio" name="outcome" value="' . $key . '" required><span>' . $label . '</span></label>';
        }
        return <<<HTML
<div class="modal-overlay" id="callModal">
    <div class="modal" style="max-width:460px">
        <div class="modal-header">
            <h3>How did the call go?</h3>
            <button type="button" class="modal-close" data-call-skip>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="callForm">
            <div class="modal-body">
                <div class="form-group">
                    <label>Result *</label>
                    <div class="call-pills">{$pills}</div>
                </div>
                <div class="form-group" id="callTalkWrap" style="display:none">
                    <label>Talk time</label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="number" id="callMin" min="0" max="360" inputmode="numeric" style="width:90px"> <span style="font-size:12px;color:var(--text-muted)">min</span>
                        <input type="number" id="callSec" min="0" max="59" inputmode="numeric" style="width:90px"> <span style="font-size:12px;color:var(--text-muted)">sec</span>
                    </div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px">Filled from the time you were away. Correct it if needed. The Android app will record exact time.</div>
                </div>
                <div class="form-group">
                    <label>Note (optional)</label>
                    <input type="text" id="callNote" maxlength="500" placeholder="e.g. Wants site visit on Saturday">
                </div>
                <div id="callError" class="alert alert-error" style="display:none;margin:0"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-call-skip>Skip</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>
HTML;
    }

    // Contact History card for a lead or client page
    public static function history(array $rows): string {
        $sum = ContactLog::summarize($rows);
        ob_start(); ?>
<div class="form-card" id="contactHistory" style="margin-bottom:16px">
    <div class="form-card-header">
        <?= self::ICON ?>
        <h3>Contact History</h3>
    </div>
    <div class="form-card-body">
        <div class="chip-row" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:<?= $rows ? '14px' : '0' ?>">
            <?php foreach ([
                ['Calls',     (string)$sum['calls'],                          'var(--navy)'],
                ['Answered',  (string)$sum['answered'],                       '#16a34a'],
                ['Talk Time', ContactLog::fmtDuration($sum['talk_sec']),      'var(--gold)'],
                ['Last Call', $sum['last'] ? date('d M, h:i A', strtotime($sum['last'])) : 'Never', 'var(--text)'],
            ] as [$label, $value, $color]): ?>
            <div style="background:var(--bg);border:1px solid var(--border-light);border-radius:8px;padding:8px 14px;min-width:110px">
                <div style="font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px"><?= $label ?></div>
                <div style="font-size:15px;font-weight:700;color:<?= $color ?>"><?= Security::e($value) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (!$rows): ?>
            <div style="font-size:13px;color:var(--text-muted);margin-top:10px">No calls yet. Tap Call to make the first one.</div>
        <?php else: ?>
        <div style="overflow-x:auto">
            <table class="data-table table-cards">
                <thead><tr><th>Date &amp; Time</th><th>Agent</th><th>Result</th><th>Talk Time</th><th>Note</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td class="tc-title" data-label="Date &amp; Time" style="white-space:nowrap"><?= date('d M Y, h:i A', strtotime($r['started_at'])) ?></td>
                        <td data-label="Agent"><?= Security::e($r['agent_name']) ?></td>
                        <td data-label="Result"><?= self::outcomePill($r['outcome']) ?><?= self::direction($r) ?></td>
                        <td data-label="Talk Time" style="font-weight:600"><?= self::talkTime($r) ?></td>
                        <td class="tc-full" data-label="Note" style="font-size:12px;color:var(--text-muted)"><?= Security::e($r['note'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
        <?php return ob_get_clean();
    }

    // "Incoming" / "Missed" under the result; outgoing is the normal case
    public static function direction(array $r): string {
        $d = $r['direction'] ?? 'outgoing';
        if ($d === 'outgoing') return '';
        return '<div class="lead-sub">' . ($d === 'missed' ? 'Missed call' : 'Incoming') . '</div>';
    }

    // Exact when it came from the phone's call log, otherwise approximate
    public static function talkTime(array $r): string {
        if ($r['outcome'] !== 'answered' || $r['duration_sec'] === null) return '-';
        $t = ContactLog::fmtDuration((int)$r['duration_sec']);
        return ($r['source'] ?? '') === 'phone_log'
            ? $t . ' <span class="lead-sub" title="From the phone\'s call log">exact</span>'
            : $t . ' <span class="lead-sub" title="Entered after the call">approx.</span>';
    }

    public static function outcomePill(?string $outcome): string {
        if ($outcome === null || !isset(ContactLog::OUTCOMES[$outcome])) {
            return '<span class="status-pill" style="background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb">Not updated</span>';
        }
        $c = ContactLog::OUTCOME_COLORS[$outcome];
        return '<span class="status-pill" style="background:' . $c . '1a;color:' . $c . ';border:1px solid ' . $c . '44">' . ContactLog::OUTCOMES[$outcome] . '</span>';
    }
}
