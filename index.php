<?php
// index.php
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect managers and admins to their dashboard automatically
if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['manager', 'admin'])) {
    header("Location: admin.php");
    exit();
}

// Fetch user data if logged in
$myReports = [];
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM reports WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$_SESSION['user_id']]);
        $myReports = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch reports: " . $e->getMessage());
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if (!isset($_SESSION['user_id'])): ?>
    <!-- ==================== LANDING & AUTH GATEWAY ==================== -->
    <div class="hero">
        <div class="hero-badge">
            <!-- Pulsing Badge Icon -->
            <svg width="12" height="12" viewBox="0 0 24 24" style="fill: currentColor;">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
            </svg>
            End-To-End Encrypted Console
        </div>
        <h1 class="hero-title">Report Incidents Privately. Protect Your Community.</h1>
        <p class="hero-subtitle">SECURELINE provides a secure gateway for citizens to report events to investigative units. Rest assured your records are tracked dynamically, audited, and strictly confidential.</p>
    </div>

    <div class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">
                <svg viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
            </div>
            <h3>Military Grade Hashing</h3>
            <p>Your access credentials are encrypted using one-way cryptographic algorithms (`PASSWORD_BCRYPT`) to protect your identities.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">
                <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
            </div>
            <h3>Track Progress Inline</h3>
            <p>Receive status feedback updates straight from investigators. Monitor incidents from "Pending" through to resolution.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">
                <svg viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            </div>
            <h3>Full Audit Trail</h3>
            <p>Every administrative override, user delegation, and report status change generates an immutable transaction log entry.</p>
        </div>
    </div>

    <!-- Authentication Forms Card -->
    <div class="card" style="max-width: 500px; margin: 0 auto;">
        <div class="auth-tabs">
            <button class="auth-tab-btn active" data-target="loginPanel">Login Session</button>
            <button class="auth-tab-btn" data-target="registerPanel">Create Account</button>
        </div>

        <!-- Login Form Panel -->
        <div class="auth-panel active" id="loginPanel">
            <form action="actions/auth_process.php" method="POST">
                <input type="hidden" name="action" value="login">
                
                <div class="form-group">
                    <label for="loginEmail" class="form-label">Email Address</label>
                    <input type="email" name="email" id="loginEmail" class="form-control" placeholder="citizen@example.com" required>
                </div>
                
                <div class="form-group">
                    <label for="loginPassword" class="form-label">Password</label>
                    <input type="password" name="password" id="loginPassword" class="form-control" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">
                    Access Gateway
                </button>
            </form>
        </div>

        <!-- Register Form Panel -->
        <div class="auth-panel" id="registerPanel">
            <form action="actions/auth_process.php" method="POST" id="registerForm">
                <input type="hidden" name="action" value="register">
                
                <div class="form-group">
                    <label for="regName" class="form-label">Full Name</label>
                    <input type="text" name="fullname" id="regName" class="form-control" placeholder="Jane Doe" required>
                </div>
                
                <div class="form-group">
                    <label for="regEmail" class="form-label">Email Address</label>
                    <input type="email" name="email" id="regEmail" class="form-control" placeholder="jane.doe@example.com" required>
                </div>
                
                <div class="form-group">
                    <label for="regPassword" class="form-label">Password</label>
                    <input type="password" name="password" id="regPassword" class="form-control" placeholder="Min. 8 characters" minlength="8" required>
                </div>
                
                <div class="form-group">
                    <label for="regConfirmPassword" class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" id="regConfirmPassword" class="form-control" placeholder="Re-enter password" required>
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">
                    Register Account
                </button>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- ==================== CITIZEN DASHBOARD LAYOUT ==================== -->
    <div class="dashboard-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-title">Citizen Portal</div>
            <nav class="sidebar-menu">
                <button class="sidebar-btn active" data-tab="intake-panel">
                    <svg viewBox="0 0 24 24"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                    Submit Report
                </button>
                <button class="sidebar-btn" data-tab="history-panel">
                    <svg viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                    My Submissions
                </button>
                <button class="sidebar-btn" data-tab="profile-panel">
                    <svg viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    Secure Settings
                </button>
            </nav>
        </aside>

        <!-- Main Workspace Panels -->
        <div class="workspace">
            
            <!-- Panel 1: Intake Engine -->
            <div class="tab-panel active" id="intake-panel">
                <div class="card">
                    <h2 class="card-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" style="fill: var(--color-primary);"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                        Incident Intake Engine
                    </h2>
                    <p style="color: var(--color-text-muted); margin-bottom: 20px; font-size: 0.95rem;">
                        Provide the specifics of the incident below. High precision in Location and Descriptions enables investigators to expedite procedures.
                    </p>
                    
                    <form action="actions/report_process.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-group">
                            <label for="title" class="form-label">Incident Title / Brief Summary</label>
                            <input type="text" name="title" id="title" class="form-control" placeholder="e.g. Theft at Central Depot" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="location" class="form-label">Specific Location / Landmark</label>
                            <input type="text" name="location" id="location" class="form-control" placeholder="e.g. 5th Avenue, Metro Terminal" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">Comprehensive Incident Details</label>
                            <textarea name="description" id="description" class="form-control" placeholder="Describe what occurred, who was involved, dates/times, and distinct details..." required></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Evidence Documentation (Optional)</label>
                            <div class="file-upload-wrapper">
                                <input type="file" name="evidence" id="evidence" class="file-upload-input" accept=".jpg,.jpeg,.png,.pdf">
                                <div class="file-upload-text" id="fileUploadText">
                                    <!-- Upload Icon -->
                                    <svg width="24" height="24" viewBox="0 0 24 24" style="fill: var(--color-text-muted); margin-bottom: 6px;">
                                        <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/>
                                    </svg>
                                    <br>Drag & drop or browse evidence document (JPG, PNG, PDF | Max 5MB)
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn">
                            <!-- Sending Icon -->
                            <svg width="18" height="18" viewBox="0 0 24 24" style="fill: currentColor;"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                            Transmit Incident Report
                        </button>
                    </form>
                </div>
            </div>

            <!-- Panel 2: Report History -->
            <div class="tab-panel" id="history-panel">
                <div class="card">
                    <h2 class="card-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" style="fill: var(--color-accent);"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                        My Submissions Log
                    </h2>
                    
                    <?php if (empty($myReports)): ?>
                        <div style="text-align: center; padding: 40px 0; color: var(--color-text-muted);">
                            <svg width="48" height="48" viewBox="0 0 24 24" style="fill: var(--color-text-dark); margin-bottom: 12px;">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                            </svg>
                            <p>No reports registered. Your transmission logs are currently empty.</p>
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
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($myReports as $report): ?>
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
                                            <td>
                                                <button class="btn btn-secondary btn-sm" onclick="viewReportDetails(
                                                    '<?= $report['id'] ?>',
                                                    '<?= addslashes(htmlspecialchars($report['title'])) ?>',
                                                    '<?= addslashes(htmlspecialchars($report['location'])) ?>',
                                                    '<?= addslashes(htmlspecialchars($report['description'])) ?>',
                                                    '<?= $report['evidence_path'] ?>',
                                                    '<?= $report['status'] ?>',
                                                    '<?= addslashes(htmlspecialchars($report['feedback'])) ?>'
                                                )">
                                                    Inspect
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Panel 3: Profile Settings -->
            <div class="tab-panel" id="profile-panel">
                <div class="card">
                    <h2 class="card-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" style="fill: var(--color-accent);"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        Secure Profile Settings
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

        </div>
    </div>

    <!-- ==================== REPORT DETAILS MODAL ==================== -->
    <div class="modal-overlay" id="reportDetailModal">
        <div class="modal-content" style="max-width: 600px;">
            <button class="modal-close" onclick="document.getElementById('reportDetailModal').classList.remove('active')">&times;</button>
            <h2 id="modalDetailTitle" style="margin-bottom: 8px;">Incident Title</h2>
            
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <span class="user-badge" id="modalDetailRef">REF #0</span>
                <span class="badge" id="modalDetailBadge">
                    <span class="badge-indicator"></span>
                    <span id="modalDetailStatusText">Pending</span>
                </span>
            </div>

            <div class="form-group">
                <strong class="form-label">Location / Landmark</strong>
                <p id="modalDetailLocation" style="color: #fff; background: rgba(0,0,0,0.15); padding: 10px 14px; border-radius: 6px;"></p>
            </div>

            <div class="form-group">
                <strong class="form-label">Comprehensive Incident Details</strong>
                <p id="modalDetailDescription" style="color: var(--color-text-muted); background: rgba(0,0,0,0.15); padding: 12px 14px; border-radius: 6px; white-space: pre-wrap; font-size: 0.95rem;"></p>
            </div>

            <div class="form-group" id="modalDetailEvidenceGroup">
                <strong class="form-label">Attached Evidence</strong>
                <div id="modalDetailEvidenceContainer"></div>
            </div>

            <div class="form-group" id="modalDetailFeedbackGroup">
                <strong class="form-label" style="color: var(--color-primary);">Investigator Feedback Notes</strong>
                <div class="feedback-text" id="modalDetailFeedbackText">No feedback notes provided yet.</div>
            </div>
        </div>
    </div>

    <!-- Modal trigger javascript helper -->
    <script>
        function viewReportDetails(id, title, location, description, evidencePath, status, feedback) {
            document.getElementById('modalDetailTitle').textContent = title;
            document.getElementById('modalDetailRef').textContent = 'REF #' + id;
            document.getElementById('modalDetailLocation').textContent = location;
            document.getElementById('modalDetailDescription').textContent = description;
            
            // Set status badge style class
            const badge = document.getElementById('modalDetailBadge');
            badge.className = 'badge badge-' + status.replace(' ', '_');
            document.getElementById('modalDetailStatusText').textContent = status;
            
            // Handle evidence path rendering
            const evidenceContainer = document.getElementById('modalDetailEvidenceContainer');
            const evidenceGroup = document.getElementById('modalDetailEvidenceGroup');
            if (evidencePath && evidencePath.trim() !== '') {
                evidenceGroup.style.display = 'block';
                evidenceContainer.innerHTML = `
                    <a href="${evidencePath}" target="_blank" class="evidence-link">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/></svg>
                        Download Evidence Document (${evidencePath.split('.').pop().toUpperCase()})
                    </a>`;
            } else {
                evidenceGroup.style.display = 'none';
                evidenceContainer.innerHTML = '';
            }
            
            // Handle feedback rendering
            const feedbackText = document.getElementById('modalDetailFeedbackText');
            if (feedback && feedback.trim() !== '') {
                feedbackText.textContent = feedback;
            } else {
                feedbackText.textContent = 'No investigator notes registered yet. The case status is ' + status + '.';
            }
            
            document.getElementById('reportDetailModal').classList.add('active');
        }
    </script>

<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
