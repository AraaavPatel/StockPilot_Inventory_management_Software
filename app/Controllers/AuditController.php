<?php

namespace App\Controllers;

use App\Audit\AuditLogger;
use App\Core\Controller;
use App\Core\Database;

/**
 * AuditController — AdminOnly (route middleware). Read-only by design:
 * there is intentionally no store()/update()/destroy() here and no
 * route in routes/web.php that would allow one. See SECURITY_AUDIT.md.
 */
class AuditController extends Controller
{
    public function index(): void
    {
        $page = max(1, (int) $this->input('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        $action = trim((string) $this->input('action', ''));
        if ($action !== '') {
            $where[] = 'action = :action';
            $params['action'] = $action;
        }
        $module = trim((string) $this->input('module', ''));
        if ($module !== '') {
            $where[] = 'module = :module';
            $params['module'] = $module;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $db = Database::connection();
        $stmt = $db->prepare(
            "SELECT * FROM audit_logs {$whereSql} ORDER BY id DESC LIMIT :lim OFFSET :off"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue(":{$k}", $v);
        }
        $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll();

        $countStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM audit_logs {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['cnt'];

        $this->view('audit.index', [
            'pageTitle' => 'Security & Audit Logs',
            'logs' => $logs,
            'page' => $page,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
            'chainOk' => AuditLogger::verifyChain() === null,
        ]);
    }
}
