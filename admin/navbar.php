<?php
// Start session and setup
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF token generation
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// Database connection
require_once 'db_connection.php';

// User data retrieval
$user = null;

if (isset($_SESSION['user_id'])) {
    // Get user with role in a single query
    $stmt = $conn->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?
    ");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
}

// Helper function for profile image
function getProfileImage($user) {
    if (isset($user['profile_image']) && !empty($user['profile_image'])) {
        return "uploads/profiles/" . htmlspecialchars($user['profile_image']);
    }
    return "https://static.vecteezy.com/system/resources/previews/009/267/048/non_2x/user-icon-design-free-png.png";
}
// Get user initials for avatar fallback
function getUserInitials($name) {
    $parts = explode(' ', trim($name ?? ''));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper($part[0] ?? '');
    }
    return $initials ?: 'U';
}
?>

<style>
    .navbar-feit {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding: 0 1rem;
        height: 60px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
        font-family: 'Inter', sans-serif;
    }

    .navbar-feit .navbar-brand {
    display: flex;
    align-items: center;   /* ensures vertical centering */
    justify-content: center;
    height: 100%;
    margin-right: 1rem;
}

.navbar-feit .navbar-brand img {
    height: 55px;   /* increased from 42px */
    width: auto;
    display: block;
}

    .navbar-feit .navbar-brand:hover img {
        opacity: 0.85;
    }

    .sidebar-toggle-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    width: 36px;
    height: 36px;

    background: transparent;
    border: none;
    border-radius: 6px;

    color: rgba(255, 255, 255, 0.7);
    cursor: pointer;

    transition: background 0.2s ease, color 0.2s ease;
    }

    .sidebar-toggle-btn:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }

    .sidebar-toggle-btn:active {
        background: rgba(255, 255, 255, 0.12);
    }

    .sidebar-toggle-btn:focus-visible {
        outline: none;
        background: rgba(255, 255, 255, 0.12);
    }

    .navbar-user-section {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
        padding: 6px 12px;
        border-radius: 10px;
        transition: background 0.2s;
        text-decoration: none;
    }

    /* Hide Bootstrap Default Caret */
    .navbar-user-section::after {
        display: none !important;
    }

    .navbar-user-section:hover {
        background: rgba(255, 255, 255, 0.08);
    }

    .navbar-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.2);
        transition: border-color 0.2s;
    }

    .navbar-user-section:hover .navbar-avatar {
        border-color: rgba(255, 255, 255, 0.4);
    }

    .navbar-avatar-initials {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #4a6cf7, #6366f1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 600;
        color: #fff;
        letter-spacing: 0.5px;
        border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .navbar-user-info {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        line-height: 1.3;
    }

    .navbar-user-name {
        font-size: 13px;
        font-weight: 600;
        color: #fff;
        white-space: nowrap;
    }

    .navbar-user-role {
        font-size: 11px;
        font-weight: 500;
        opacity: 0.7;
        color: #fff;
    }

    .navbar-chevron {
        font-size: 10px;
        color: rgba(255, 255, 255, 0.4);
        margin-left: 2px;
        transition: transform 0.2s;
    }

    .navbar-user-section[aria-expanded="true"] .navbar-chevron {
        transform: rotate(180deg);
    }

    .dropdown-menu-feit {
        background: #1e2a3a;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        padding: 0;
        overflow: hidden;
        margin-top: 8px !important;
    }

    .dropdown-menu-feit::before {
        display: none;
    }

    .dropdown-item-feit {
        padding: 10px 16px;
        color: rgba(255, 255, 255, 0.75);
        font-size: 13px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.15s ease;
    }

    .dropdown-item-feit:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
    }

    .dropdown-item-feit:hover .item-icon {
        background: rgba(255, 255, 255, 0.12);
    }

    .dropdown-item-feit.text-danger {
        color: #f87171;
    }

    .dropdown-item-feit.text-danger:hover {
        background: rgba(248, 113, 113, 0.1);
        color: #fca5a5;
    }

    .dropdown-item-feit.text-danger .item-icon {
        background: rgba(248, 113, 113, 0.1);
    }

    .dropdown-item-feit.text-danger:hover .item-icon {
        background: rgba(248, 113, 113, 0.2);
    }

    @media (max-width: 991.98px) {
        .navbar-user-info {
            display: none;
        }
        .navbar-chevron {
            display: none;
        }
    }

    .dropdown-item-feit {
    background: transparent;
    }

    .dropdown-item-feit:hover,
    .dropdown-item-feit:focus,
    .dropdown-item-feit:active {
        background: rgba(255, 255, 255, 0.08) !important;
        color: #fff;
    }

    .dropdown-item-feit.text-danger:hover,
    .dropdown-item-feit.text-danger:focus,
    .dropdown-item-feit.text-danger:active {
        background: rgba(248, 113, 113, 0.1) !important;
        color: #fca5a5 !important;
    }

    .item-icon {
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.06);
    font-size: 13px;
    }
</style>

<?php include 'loader.php'; ?>

<nav class="sb-topnav navbar navbar-expand navbar-dark navbar-feit">
    <!-- Navbar Brand with Logo -->
    <a class="navbar-brand" href="index.php">
        <img src="img/system/FEIT.png" alt="FEIT" class="navbar-brand-logo">
    </a>

    <!-- Sidebar Toggle -->
    <button class="sidebar-toggle-btn order-1 order-lg-0 me-3" id="sidebarToggle" href="#!">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Spacer -->
    <div class="flex-grow-1"></div>

    <?php if($user): ?>
    <!-- User Dropdown -->
    <div class="dropdown">
        <a class="navbar-user-section dropdown-toggle" href="#" role="button"
           id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="navbar-user-info d-none d-lg-flex">
                <span class="navbar-user-name"><?= htmlspecialchars($user['name'] ?? 'User') ?></span>
                <span class="navbar-user-role"><?= htmlspecialchars($user['role_name'] ?? 'Staff') ?></span>
            </div>
            <?php
            $hasImage = isset($user['profile_image']) && !empty($user['profile_image']);
            if ($hasImage):
            ?>
                <img src="<?= getProfileImage($user) ?>" alt="Profile" class="navbar-avatar">
            <?php else: ?>
                <div class="navbar-avatar-initials"><?= getUserInitials($user['name']) ?></div>
            <?php endif; ?>
            <i class="fas fa-chevron-down navbar-chevron"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-feit" aria-labelledby="userDropdown">
            <li>
                <a class="dropdown-item dropdown-item-feit text-danger" href="logout.php">
                    <span class="item-icon"><i class="fas fa-sign-out-alt"></i></span>
                    Sign Out
                </a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
</nav>