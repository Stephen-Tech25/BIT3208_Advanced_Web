<?php
// admin.php
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Access Control check: Logged in and has manager/admin role
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Access denied. Please log in first.";
    header("Location: index.php");
    exit();
}

if (!in_array($_SESSION['user_role'], ['manager', 'admin'])) {
    $_SESSION['error'] = "Access denied. Unauthorized area.";
    header("Location: index.php");
    exit();
}

$userRole = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];

// --- Retrieve Core Datasets ---
try {
    // 1. Global Reports (Investigation panel)
    $stmtGlobal = $pdo->prepare("
        SELECT r.*, u.fullname AS reporter_name, u.email AS reporter_email 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC
    ");
    $stmtGlobal->execute();
    $globalReports = $stmtGlobal->fetchAll();
    
    // 2. Personal Reports (Baseline inheritance)
    $stmtPersonal = $pdo->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY created_at DESC");
    $stmtPersonal->execute([$userId]);
    $personalReports = $stmtPersonal->fetchAll();
    
    // 3. Admin-Exclusive Datasets
    $systemUsers = [];
    $auditLogs = [];
    $stats = [];
    
    if ($userRole === 'admin') {
        // Fetch all accounts
        $stmtUsers = $pdo->prepare("SELECT id, fullname, email, role, created_at FROM users ORDER BY created_at DESC");
        $stmtUsers->execute();
        $systemUsers = $stmtUsers->fetchAll();
        
        // Fetch system audit logs (limit to 100 for performance)
        $stmtLogs = $pdo->prepare("
            SELECT al.*, u.fullname AS actor_name, u.role AS actor_role 
            FROM audit_logs al 
            LEFT JOIN users u ON al.actor_id = u.id 
            ORDER BY al.timestamp DESC 
            LIMIT 100
        ");
        $stmtLogs->execute();
        $auditLogs = $stmtLogs->fetchAll();
        
        // Calculate statistics
        $stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['total_reports'] = $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
        $stats['pending_reports'] = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Pending'")->fetchColumn();
        $stats['investigation_reports'] = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Under Investigation'")->fetchColumn();
        $stats['resolved_reports'] = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Resolved'")->fetchColumn();
        $stats['dismissed_reports'] = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'Dismissed'")->fetchColumn();
    }
} catch (PDOException $e) {
    error_log("Admin page data load error: " . $e->getMessage());
    $_SESSION['error'] = "A database query error occurred while generating reports dashboards.";
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard-layout">
    <!-- Navigation Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-title">Management Console</div>
        <nav class="sidebar-menu">
            <!-- Investigator baseline tabs -->
            <button class="sidebar-btn active" data-tab="investigation-panel">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                Investigation Panel
            </button>
            <button class="sidebar-btn" data-tab="create-panel">
                <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Create Incident Report
            </button>
            <button class="sidebar-btn" data-tab="personal-panel">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                My Incident Logs
            </button>
            <button class="sidebar-btn" data-tab="profile-panel">
                <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                Personal Profile
            </button>
            
            <?php if ($userRole === 'admin'): ?>
                <!-- Admin-exclusive tabs -->
                <div class="sidebar-title" style="margin-top: 20px;">System Oversight</div>
                <button class="sidebar-btn" data-tab="users-panel">
                    <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                    System User Matrix
                </button>
                <button class="sidebar-btn" data-tab="audit-panel">
                    <svg viewBox="0 0 24 24"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
                    Overview & Audits
                </button>
            <?php endif; ?>
        </nav>
    </aside>

    <!-- Main Content Workspace Panels -->
    <div class="workspace">

        <!-- ==================== 1. GLOBAL INVESTIGATION CONTROL PANEL ==================== -->
        <div class="tab-panel active" id="investigation-panel">
            <div class="card">
                <h2 class="card-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" style="fill: var(--color-primary);"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
                    Global Incident Control Matrix
                </h2>
                <p style="color: var(--color-text-muted); margin-bottom: 25px; font-size: 0.95rem;">
                    Examine citizens' incident submissions, assign case statuses, and log review feedback below. Action overrides generate automated transaction records in the audit directory.
                </p>

                <?php if (empty($globalReports)): ?>
                    <div style="text-align: center; padding: 40px 0; color: var(--color-text-muted);">
                        <p>No crime incident submissions are registered on the system database.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Ref</th>
                                    <th>Reporter</th>
                                    <th>Incident Title</th>
                                    <th>Location</th>
                                    <th>Submitted Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($globalReports as $report): ?>
                                    <?php $safeStatus = str_replace(' ', '_', $report['status']); ?>
                                    <tr>
                                        <td>#<?= $report['id'] ?></td>
                                        <td>
                                            <div style="font-weight:600; color:#fff;"><?= htmlspecialchars($report['reporter_name']) ?></div>
                                            <div style="font-size:0.8rem; color:var(--color-text-muted);"><?= htmlspecialchars($report['reporter_email']) ?></div>
                                        </td>
                                        <td style="font-weight: 500; color: #fff;"><?= htmlspecialchars($report['title']) ?></td>
                                        <td><?= htmlspecialchars($report['location']) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($report['created_at'])) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $safeStatus ?>">
                                                <span class="badge-indicator"></span>
                                                <?= htmlspecialchars($report['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display:flex; gap:6px;">
                                                <button class="btn btn-secondary btn-sm" onclick="openFeedbackModal(
                                                    '<?= $report['id'] ?>',
                                                    '<?= $report['status'] ?>',
                                                    '<?= addslashes(htmlspecialchars($report['feedback'])) ?>'
                                                )">
                                                    Update
                                                </button>
                                                
                                                <?php if ($report['evidence_path']): ?>
                                                    <a href="<?= htmlspecialchars($report['evidence_path']) ?>" target="_blank" class="btn btn-secondary btn-sm" style="box-shadow:none;">
                                                        Evidence
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ==================== 2. MANAGER PERSONAL INTAKE ENGINE ==================== -->
        <div class="tab-panel" id="create-panel">
            <div class="card">
                <h2 class="card-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" style="fill: var(--color-primary);"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    Incident Intake Engine (Internal Submission)
                </h2>
                
                <form action="actions/report_process.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="form-group">
                        <label for="title" class="form-label">Incident Title / Brief Summary</label>
                        <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Vandalism on Section D" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="location" class="form-label">Specific Location / Landmark</label>
                        <input type="text" name="location" id="location" class="form-control" placeholder="e.g. North Sector Warehouse" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Comprehensive Incident Details</label>
                        <textarea name="description" id="description" class="form-control" placeholder="Describe the incident, names involved, timestamps, and findings..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Evidence Documentation (Optional)</label>
                        <div class="file-upload-wrapper">
                            <input type="file" name="evidence" id="evidence" class="file-upload-input" accept=".jpg,.jpeg,.png,.pdf">
                            <div class="file-upload-text" id="fileUploadText">
                                <svg width="24" height="24" viewBox="0 0 24 24" style="fill: var(--color-text-muted); margin-bottom: 6px;"><path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
                                <br>Drag & drop or browse evidence document (JPG, PNG, PDF | Max 5MB)
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn">
                        Submit Incident Report
                    </button>
                </form>
            </div>
        </div>

        <!-- ==================== 3. MANAGER PERSONAL REPORT HISTORY ==================== -->
        <div class="tab-panel" id="personal-panel">
            <div class="card">
                <h2 class="card-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" style="fill: var(--color-accent);"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                    My Internal Reports Log
                </h2>
                
                <?php if (empty($personalReports)): ?>
                    <div style="text-align: center; padding: 40px 0; color: var(--color-text-muted);">
                        <p>You have not registered any internal reports.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Ref ID</th>
                                    <th>Title</th>
                                    <th>Location</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Feedback</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($personalReports as $report): ?>
                                    <?php $safeStatus = str_replace(' ', '_', $report['status']); ?>
                                    <tr>
                                        <td>#<?= $report['id'] ?></td>
                                        <td style="font-weight: 600; color: #fff;"><?= htmlspecialchars($report['title']) ?></td>
                                        <td><?= htmlspecialchars($report['location']) ?></td>
                                        <td><?= date('M d, Y H:i', strtotime($report['created_at'])) ?></td>
                                        <td>
                                            <span class="badge badge-<?= $safeStatus ?>">
                                                <span class="badge-indicator"></span>
                                                <?= htmlspecialchars($report['status']) ?>
                                            </span>
                                        </td>
                                        <td style="font-size:0.9rem; color:var(--color-text-muted);">
                                            <?= $report['feedback'] ? htmlspecialchars($report['feedback']) : '<em>No feedback</em>' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ==================== 4. PERSONAL SETTINGS ==================== -->
        <div class="tab-panel" id="profile-panel">
            <div class="card">
                <h2 class="card-title">
                    <svg width="24" height="24" viewBox="0 0 24 24" style="fill: var(--color-accent);"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    Profile Settings
                </h2>
                
                <form action="actions/user_crud.php" method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="profName" class="form-label">Full Name</label>
                        <input type="text" name="fullname" id="profName" class="form-control" value="<?= htmlspecialchars($_SESSION['user_fullname']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="profEmail" class="form-label">Email Address</label>
                        <input type="email" name="email" id="profEmail" class="form-control" value="<?= htmlspecialchars($_SESSION['user_email']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="profPassword" class="form-label">Update Password (Leave empty to keep current)</label>
                        <input type="password" name="password" id="profPassword" class="form-control" placeholder="••••••••" minlength="8">
                    </div>
                    
                    <button type="submit" class="btn">
                        Update Credentials
                    </button>
                </form>
            </div>
        </div>

        <?php if ($userRole === 'admin'): ?>
            <!-- ==================== 5. ADMIN EXCLUSIVE: SYSTEM USER DIRECTORY ==================== -->
            <div class="tab-panel" id="users-panel">
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--border-light); padding-bottom: 15px; margin-bottom: 20px;">
                        <h2 style="display:flex; align-items:center; gap:10px; border:none; padding:0; margin:0;">
                            <svg width="24" height="24" viewBox="0 0 24 24" style="fill: var(--color-primary);"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                            Master User Matrix
                        </h2>
                        <button class="btn btn-sm" onclick="openAddUserModal()">+ Add Account</button>
                    </div>
                    <p style="color: var(--color-text-muted); margin-bottom: 20px; font-size: 0.95rem;">
                        View and manage permissions for all application users. System policies block demoting or deleting your active administrator profile.
                    </p>

                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Email Address</th>
                                    <th>Security Role</th>
                                    <th>Registered Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($systemUsers as $user): ?>
                                    <tr>
                                        <td>#<?= $user['id'] ?></td>
                                        <td style="font-weight:600; color:#fff;"><?= htmlspecialchars($user['fullname']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="role-badge role-badge-<?= $user['role'] ?>">
                                                <?= htmlspecialchars($user['role']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <div style="display:flex; gap:6px;">
                                                <button class="btn btn-secondary btn-sm" onclick="openEditUserModal(
                                                    '<?= $user['id'] ?>',
                                                    '<?= addslashes(htmlspecialchars($user['fullname'])) ?>',
                                                    '<?= addslashes(htmlspecialchars($user['email'])) ?>',
                                                    '<?= $user['role'] ?>'
                                                )">
                                                    Modify
                                                </button>
                                                
                                                <?php if ($user['id'] !== (int)$_SESSION['user_id']): ?>
                                                    <form action="actions/user_crud.php" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this user account? All reports created by this user will also be purged.');" style="margin:0;">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">Purge</button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary btn-sm" disabled style="opacity:0.3; cursor:not-allowed;">Active</button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ==================== 6. ADMIN EXCLUSIVE: OVERVIEW STATS & AUDIT LOGS ==================== -->
            <div class="tab-panel" id="audit-panel">
                <!-- Analytics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Registered Accounts</div>
                        <div class="stat-number"><?= $stats['total_users'] ?></div>
                    </div>
                    <div class="stat-card" style="border-bottom: 2px solid var(--color-primary);">
                        <div class="stat-label">Total Crime Reports</div>
                        <div class="stat-number"><?= $stats['total_reports'] ?></div>
                    </div>
                    <div class="stat-card" style="border-bottom: 2px solid var(--status-pending);">
                        <div class="stat-label">Pending Reviews</div>
                        <div class="stat-number"><?= $stats['pending_reports'] ?></div>
                    </div>
                    <div class="stat-card" style="border-bottom: 2px solid var(--status-investigating);">
                        <div class="stat-label">Under Investigation</div>
                        <div class="stat-number"><?= $stats['investigation_reports'] ?></div>
                    </div>
                    <div class="stat-card" style="border-bottom: 2px solid var(--status-resolved);">
                        <div class="stat-label">Resolved Cases</div>
                        <div class="stat-number"><?= $stats['resolved_reports'] ?></div>
                    </div>
                </div>

                <!-- Audit Log Directory -->
                <div class="card">
                    <h2 class="card-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" style="fill: var(--color-accent);"><path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
                        Immutable Audit Logs Directory
                    </h2>
                    <p style="color: var(--color-text-muted); margin-bottom: 20px; font-size: 0.95rem;">
                        Automated transaction trail tracking high-level status overrides, access privileges modifications, and authorization sequences.
                    </p>

                    <?php if (empty($auditLogs)): ?>
                        <div style="text-align: center; padding: 40px 0; color: var(--color-text-muted);">
                            <p>No transactions registered in system audit logs.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table" style="font-size:0.9rem;">
                                <thead>
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Actor Profile</th>
                                        <th>Level</th>
                                        <th>Action Description</th>
                                        <th>Target ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($auditLogs as $log): ?>
                                        <tr>
                                            <td><?= date('Y-m-d H:i:s', strtotime($log['timestamp'])) ?></td>
                                            <td>
                                                <?php if ($log['actor_name']): ?>
                                                    <strong><?= htmlspecialchars($log['actor_name']) ?></strong>
                                                <?php else: ?>
                                                    <span style="color:var(--color-text-dark);">System (Purged Account)</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($log['actor_role']): ?>
                                                    <span class="role-badge role-badge-<?= $log['actor_role'] ?>" style="padding: 2px 6px; font-size: 0.75rem;">
                                                        <?= htmlspecialchars($log['actor_role']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td style="color:#fff;"><?= htmlspecialchars($log['action_performed']) ?></td>
                                            <td><?= $log['target_id'] ? '#'.$log['target_id'] : '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- ==================== FEEDBACK / STATUS MODAL (MANAGER + ADMIN) ==================== -->
<div class="modal-overlay" id="feedbackModal">
    <div class="modal-content">
        <button class="modal-close">&times;</button>
        <h2>Update Case Status</h2>
        <p style="color: var(--color-text-muted); font-size: 0.85rem; margin-bottom: 20px;">
            Changing case status sends real-time updates and visible feedback to the reporter.
        </p>
        
        <form action="actions/report_process.php" method="POST">
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="report_id" id="modalReportId">
            
            <div class="form-group">
                <label for="modalStatus" class="form-label">Review Status State</label>
                <select name="status" id="modalStatus" class="form-control" style="background:#000;">
                    <option value="Pending">Pending Review</option>
                    <option value="Under Investigation">Under Investigation</option>
                    <option value="Resolved">Resolved (Completed)</option>
                    <option value="Dismissed">Dismissed (No Action)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="modalFeedback" class="form-label">Case Feedback / Investigator Notes</label>
                <textarea name="feedback" id="modalFeedback" class="form-control" placeholder="Provide details regarding the case resolution or status check..."></textarea>
            </div>
            
            <button type="submit" class="btn" style="width:100%;">Commit Adjustments</button>
        </form>
    </div>
</div>

<?php if ($userRole === 'admin'): ?>
    <!-- ==================== ADD USER MODAL (ADMIN ONLY) ==================== -->
    <div class="modal-overlay" id="addUserModal">
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            <h2>Create New Security Profile</h2>
            <p style="color: var(--color-text-muted); font-size: 0.85rem; margin-bottom: 20px;">
                Directly registers a credentials block onto the server. User password will be hashed with BCRYPT.
            </p>
            
            <form action="actions/user_crud.php" method="POST">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="addFullname" class="form-label">Full Name</label>
                    <input type="text" name="fullname" id="addFullname" class="form-control" placeholder="e.g. Officer Davis" required>
                </div>
                
                <div class="form-group">
                    <label for="addEmail" class="form-label">Email Address</label>
                    <input type="email" name="email" id="addEmail" class="form-control" placeholder="davis@secureline.gov" required>
                </div>
                
                <div class="form-group">
                    <label for="addPassword" class="form-label">Temporary Password</label>
                    <input type="password" name="password" id="addPassword" class="form-control" placeholder="••••••••" minlength="8" required>
                </div>
                
                <div class="form-group">
                    <label for="addRole" class="form-label">Authorization Role</label>
                    <select name="role" id="addRole" class="form-control" style="background:#000;">
                        <option value="user">User (Citizen)</option>
                        <option value="manager">Manager (Investigator)</option>
                        <option value="admin">Super Admin</option>
                    </select>
                </div>
                
                <button type="submit" class="btn" style="width:100%;">Create Account</button>
            </form>
        </div>
    </div>

    <!-- ==================== EDIT USER MODAL (ADMIN ONLY) ==================== -->
    <div class="modal-overlay" id="editUserModal">
        <div class="modal-content">
            <button class="modal-close">&times;</button>
            <h2>Modify User Profile</h2>
            <p style="color: var(--color-text-muted); font-size: 0.85rem; margin-bottom: 20px;">
                Alter access keys, email indexes, or security level designations.
            </p>
            
            <form action="actions/user_crud.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="form-group">
                    <label for="editFullname" class="form-label">Full Name</label>
                    <input type="text" name="fullname" id="editFullname" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="editEmail" class="form-label">Email Address</label>
                    <input type="email" name="email" id="editEmail" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="editPassword" class="form-label">Reset Password (Leave blank to keep current)</label>
                    <input type="password" name="password" id="editPassword" class="form-control" placeholder="••••••••" minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="editRole" class="form-label">Authorization Role</label>
                    <select name="role" id="editRole" class="form-control" style="background:#000;">
                        <option value="user">User (Citizen)</option>
                        <option value="manager">Manager (Investigator)</option>
                        <option value="admin">Super Admin</option>
                    </select>
                </div>
                
                <button type="submit" class="btn" style="width:100%;">Commit Profile Changes</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
