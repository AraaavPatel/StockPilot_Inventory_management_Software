<div style="display:grid; grid-template-columns: 1fr 340px; gap:20px; align-items:start;">
    <div class="card">
        <div class="card-header"><h3>All Users</h3></div>
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th style="min-width:260px;">Update</th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['name']) ?><?php if ($u['id'] == ($authUser['id'] ?? null)): ?> <span class="badge badge-neutral">you</span><?php endif; ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge badge-neutral"><?= htmlspecialchars($u['role']) ?></span></td>
                    <td><span class="badge <?= $u['status'] === 'active' ? 'badge-success' : 'badge-neutral' ?>"><?= htmlspecialchars($u['status']) ?></span></td>
                    <td style="font-size:12px; color:var(--ink-soft);"><?= htmlspecialchars($u['last_login_at'] ?? 'Never') ?></td>
                    <td>
                        <?php if ($u['id'] == ($authUser['id'] ?? null)): ?>
                            <span style="font-size:12px; color:var(--ink-soft);">Ask another admin to change your role/status.</span>
                        <?php else: ?>
                        <form method="POST" action="<?= base_url("/users/{$u['id']}/update") ?>" style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="name" value="<?= htmlspecialchars($u['name']) ?>">
                            <select name="role" style="width:100px;">
                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="manager" <?= $u['role'] === 'manager' ? 'selected' : '' ?>>Manager</option>
                                <option value="cashier" <?= $u['role'] === 'cashier' ? 'selected' : '' ?>>Cashier</option>
                            </select>
                            <select name="status" style="width:90px;">
                                <option value="active" <?= $u['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $u['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                            <button type="submit" class="btn btn-outline btn-sm">Save</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card">
        <div class="card-header"><h3>Add User</h3></div>
        <form method="POST" action="<?= base_url('/users') ?>">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone">
            </div>
            <div class="form-group">
                <label for="password">Temporary Password</label>
                <input type="password" id="password" name="password" required minlength="8" placeholder="At least 8 characters">
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="cashier">Cashier</option>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Create User</button>
        </form>
        <p style="font-size:12px; color:var(--ink-soft); margin-top:12px;">
            Creating an admin sends a security-alert email to the configured admin address (MAIL_ADMIN_ADDRESS) if SMTP is configured.
        </p>
    </div>
</div>
