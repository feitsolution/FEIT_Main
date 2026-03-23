<?php
function showAlert($message, $type = 'success') {
    echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
            $message
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
          </div>";
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data));
}

// RBAC Helper Functions
function getUserRoleId() {
    return isset($_SESSION['role_id']) ? (int)$_SESSION['role_id'] : 0;
}

function isAdmin() {
    return getUserRoleId() === 1;
}

function isModerator() {
    return getUserRoleId() === 3;
}

function isUser() {
    return getUserRoleId() === 2;
}

function canManageUsers() {
    return isAdmin();
}

function canViewLogs() {
    return isAdmin();
}

function canApproveRejectInquiries() {
    return isAdmin() || isModerator();
}

function canEditRecords() {
    return isAdmin() || isModerator();
}

function canDeleteRecords() {
    return isAdmin();
}

function canAddCustomers() {
    return isAdmin() || isModerator();
}

function canAddProducts() {
    return isAdmin() || isModerator();
}

function canEditProducts() {
    return isAdmin() || isModerator();
}

function canManagePackages() {
    return isAdmin() || isModerator();
}

function canCreateInvoices() {
    return true; // All roles can create invoices
}

function canCancelInvoices() {
    return isAdmin() || isModerator();
}

// Redirect if user lacks required role, with optional error message
function requireRole($allowedRoles, $redirectPage = 'index.php') {
    if (!in_array(getUserRoleId(), $allowedRoles)) {
        header("Location: $redirectPage");
        exit();
    }
}

// Deny access if not Admin
function requireAdmin($redirectPage = 'index.php') {
    if (!isAdmin()) {
        header("Location: $redirectPage");
        exit();
    }
}
