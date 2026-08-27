<div class="card" style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
    <div>
        Chain integrity:
        <?php if ($chainOk): ?>
            <span class="badge badge-success">Verified — no tampering detected</span>
        <?php else: ?>
            <span class="badge" style="background:#c0392b; color:#fff;">BROKEN — records may have been altered directly in the database</span>
        <?php endif; ?>
    </div>
    <form method="GET" action="<?= base_url('/audit-logs') ?>" style="display:flex; gap:8px;">
        <input type="text" name="action" placeholder="Action e.g. LOGIN_FAILED" value="<?= htmlspecialchars($_GET['action'] ?? '') ?>">
        <input type="text" name="module" placeholder="Module e.g. products" value="<?= htmlspecialchars($_GET['module'] ?? '') ?>">
        <button type="submit" class="btn btn-outline btn-sm">Filter</button>
    </form>
</div>

<div class="card">
    <table>
        <thead><tr><th>Date/Time</th><th>User</th><th>Action</th><th>Module</th><th>Entity</th><th>IP</th><th>Request ID</th></tr></thead>
        <tbody>
        <?php foreach ($logs as $l): ?>
            <tr>
                <td style="font-size:12px;"><?= htmlspecialchars($l['created_at']) ?></td>
                <td><?= htmlspecialchars($l['actor_name'] ?? '—') ?></td>
                <td><span class="badge badge-neutral"><?= htmlspecialchars($l['action']) ?></span></td>
                <td><?= htmlspecialchars($l['module']) ?></td>
                <td style="font-size:12px;"><?= htmlspecialchars($l['entity_type'] ?? '—') ?><?= $l['entity_id'] ? ' #' . (int) $l['entity_id'] : '' ?></td>
                <td style="font-size:12px;"><?= htmlspecialchars($l['ip_address'] ?? '—') ?></td>
                <td style="font-size:11px; color:var(--ink-soft);"><?= htmlspecialchars($l['request_id'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?><tr><td colspan="7">No matching audit records.</td></tr><?php endif; ?>
        </tbody>
    </table>

    <div style="display:flex; gap:8px; justify-content:center; margin-top:16px;">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a class="btn btn-outline btn-sm <?= $i === $page ? 'active' : '' ?>" href="<?= base_url("/audit-logs?page={$i}") ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

<p style="font-size:12px; color:var(--ink-soft); margin-top:12px;">
    This log is append-only. There is no edit or delete route for audit records anywhere in the application —
    corrections are made by recording a new corrective action, never by altering history.
</p>
