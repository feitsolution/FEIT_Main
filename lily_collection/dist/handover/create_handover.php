<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: /lily_collection/dist/pages/login.php");
    exit();
}

// Include the database connection file
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/connection/db_connection.php');

// Check if user has admin role (role_id = 1)
if (!isset($_SESSION['user_id'])) {
    header("Location: /lily_collection/dist/pages/login.php");
    exit();
}

// Get user's role from database
$user_id = $_SESSION['user_id'];
$role_check_sql = "SELECT u.role_id, r.name as role_name 
                   FROM users u 
                   LEFT JOIN roles r ON u.role_id = r.id 
                   WHERE u.id = ? AND u.status = 'active'";
$role_stmt = $conn->prepare($role_check_sql);
$role_stmt->bind_param("i", $user_id);
$role_stmt->execute();
$role_result = $role_stmt->get_result();

if ($role_result->num_rows === 0) {
    // User not found or inactive
    session_destroy();
    header("Location: /lily_collection/dist/pages/login.php");
    exit();
}

$user_role = $role_result->fetch_assoc();

// Check if user is admin (role_id = 1)
if ($user_role['role_id'] != 1) {
    // User is not admin, redirect to dashboard
    header("Location: /lily_collection/dist/dashboard/index.php");
    exit();
}

// Step Detection Logic
$isStep2 = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['handover_to']) && !isset($_POST['action_type']));
$step = $isStep2 ? 2 : 1;

$handover_date = date('Y-m-d');
$handover_to_id = intval($_POST['handover_to'] ?? 0);
$notes = $_POST['notes'] ?? '';
$recipientName = "";

if ($step === 2) {
    // Fetch recipient name for summary
    $userQuery = $conn->prepare("SELECT name FROM users WHERE id = ?");
    $userQuery->bind_param("i", $handover_to_id);
    $userQuery->execute();
    $userResult = $userQuery->get_result();
    if ($userResult && $userResult->num_rows > 0) {
        $recipientName = $userResult->fetch_assoc()['name'];
    }
    $userQuery->close();
}

// Fetch data needed for both steps or specifically Step 2
// Fetch active users for Step 1 dropdown
$usersQuery = "SELECT id, name FROM users WHERE status = 'active' ORDER BY name ASC";
$usersResult = $conn->query($usersQuery);
$users = [];
if ($usersResult && $usersResult->num_rows > 0) {
    while ($u = $usersResult->fetch_assoc()) {
        $users[] = $u;
    }
}

// Fetch products for Step 2 dropdown
$productsQuery = "SELECT DISTINCT p.id, p.name, p.product_code 
                  FROM products p 
                  INNER JOIN Product_materials pm ON p.id = pm.product_id 
                  WHERE p.status = 'active' 
                  ORDER BY p.name ASC";
$productsResult = $conn->query($productsQuery);
$products = [];
if ($productsResult && $productsResult->num_rows > 0) {
    while ($p = $productsResult->fetch_assoc()) {
        $products[] = $p;
    }
}

// Preload BOM and Stock for Step 2 JS
$allBomData = [];
$availableBalances = [];
if ($step === 2) {
    foreach ($products as $p) {
        $bomStmt = $conn->prepare("SELECT pm.material_id, pm.quantity_required, m.name as material_name, m.material_code 
                                    FROM Product_materials pm 
                                    LEFT JOIN Material m ON pm.material_id = m.id 
                                    WHERE pm.product_id = ?");
        $bomStmt->bind_param("i", $p['id']);
        $bomStmt->execute();
        $bomResult = $bomStmt->get_result();
        $bom = [];
        while ($b = $bomResult->fetch_assoc()) {
            $bom[] = $b;
        }
        $allBomData[$p['id']] = $bom;
        $bomStmt->close();
    }

    $stockQuery = "SELECT id, stock_quantity FROM Material WHERE status = 'active'";
    $stockResult = $conn->query($stockQuery);
    if ($stockResult && $stockResult->num_rows > 0) {
        while ($s = $stockResult->fetch_assoc()) {
            $availableBalances[$s['id']] = (int)$s['stock_quantity'];
        }
    }
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/navbar.php');
include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/sidebar.php');
?>

<!doctype html>
<html lang="en" data-pc-preset="preset-1" data-pc-sidebar-caption="true" data-pc-direction="ltr" dir="ltr" data-pc-theme="light">
<head>
    <title>Material Handover</title>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/head.php'); ?>
    <link rel="stylesheet" href="../assets/css/style.css" id="main-style-link" />
    <link rel="stylesheet" href="../assets/css/products.css" />
    <style>
        .bom-section { margin-top: 1.5rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef; }
        .bom-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .bom-table th { background: #e9ecef; padding: 8px; text-align: left; border-bottom: 2px solid #dee2e6; }
        .bom-table td { padding: 8px; border-bottom: 1px solid #dee2e6; }
        .badge-sufficient { padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; background: #d4edda; color: #155724; }
        .badge-shortfall { padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; background: #f8d7da; color: #721c24; }
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); display: none; justify-content: center; align-items: center; z-index: 10000; color: white; }
        .spinner { width: 50px; height: 50px; border: 5px solid rgba(255,255,255,0.3); border-top: 5px solid #fff; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 15px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .summary-box { background: #e7f3ff; border-left: 5px solid #007bff; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
    </style>
</head>
<body>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/loader.php'); ?>
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="spinner"></div>
            <h5>Processing...</h5>
        </div>
    </div>

    <div class="pc-container">
        <div class="pc-content">
            <div class="page-header">
                <div class="page-block">
                    <div class="page-header-title">
                        <h5 class="mb-0 font-medium">Material Handover</h5>
                    </div>
                </div>
            </div>

            <div class="main-container">
                <?php if ($step === 1): ?>
                    <!-- Handover Information -->
                    <form method="POST" id="step1Form" class="product-form">
                        <div class="form-section">
                            <div class="section-content">
                                <div class="form-row">
                                    <div class="product-form-group">
                                        <label for="handover_date" class="form-label">
                                            <i class="fas fa-calendar-alt"></i> Handover Date<span class="required">*</span>
                                        </label>
                                        <input type="date" class="form-control" id="handover_date" name="handover_date" 
                                               value="<?php echo htmlspecialchars($handover_date); ?>" required readonly
                                               style="background-color: #e9ecef; cursor: not-allowed;">
                                        <div class="error-feedback" id="handover_date-error"></div>
                                    </div>
                                    <div class="product-form-group">
                                        <label for="handover_to" class="form-label">
                                            <i class="fas fa-user"></i> Handover To (Recipient)<span class="required">*</span>
                                        </label>
                                        <select class="form-select" id="handover_to" name="handover_to" required>
                                            <option value="">-- Select Recipient --</option>
                                            <?php foreach ($users as $u): ?>
                                                <option value="<?php echo $u['id']; ?>" <?php echo ($handover_to_id == $u['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($u['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="error-feedback" id="handover_to-error"></div>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="product-form-group" style="width: 100%;">
                                        <label for="handover_notes" class="form-label">
                                            <i class="fas fa-sticky-note"></i> Handover Note <span class="text-muted">(Max 100 characters)</span>
                                        </label>
                                        <textarea class="form-control" id="handover_notes" name="notes" rows="2" maxlength="100" placeholder="Optional notes..."><?php echo htmlspecialchars($notes); ?></textarea>
                                    </div>
                                </div>
                                <div class="row mt-4">
                                    <div class="col-12 text-right">
                                        <button type="submit" class="btn btn-primary ms-2">
                                            Next <i class="fas fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                <?php else: ?>
                    <!-- Step 2: Product & Quantity -->
                    <div class="summary-box">
                        <h6><strong>Handover Details</strong></h6>
                        <div class="row">
                            <div class="col-md-4"><strong>Date:</strong> <?php echo htmlspecialchars($handover_date); ?></div>
                            <div class="col-md-4"><strong>Recipient:</strong> <?php echo htmlspecialchars($recipientName); ?></div>
                            <div class="col-md-4"><strong>Notes:</strong> <?php echo htmlspecialchars($notes ?: 'None'); ?></div>
                        </div>
                    </div>

                    <form method="POST" id="handoverForm" class="product-form">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="handover_date" value="<?php echo htmlspecialchars($handover_date); ?>">
                        <input type="hidden" name="handover_to" value="<?php echo $handover_to_id; ?>">
                        <input type="hidden" name="notes" value="<?php echo htmlspecialchars($notes); ?>">
                        <input type="hidden" name="action_type" value="save">

                        <div class="form-section">
                            <div class="section-content">
                                <div class="form-row">
                                    <div class="product-form-group">
                                        <label for="product_id" class="form-label">
                                            <i class="fas fa-box"></i> Select Product<span class="required">*</span>
                                        </label>
                                        <select class="form-select" id="product_id" name="product_id" required>
                                            <option value="">-- Select Product --</option>
                                            <?php foreach ($products as $p): ?>
                                                <option value="<?php echo $p['id']; ?>">
                                                    <?php echo htmlspecialchars($p['name']); ?> (<?php echo htmlspecialchars($p['product_code']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="product-form-group">
                                        <label for="quantity_to_produce" class="form-label">
                                            <i class="fas fa-industry"></i> Quantity to Produce<span class="required">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="quantity_to_produce" name="quantity_to_produce" required min="1">
                                    </div>
                                </div>

                                <div id="bomRequirements" class="bom-section" style="display: none;">
                                    <h6><i class="fas fa-clipboard-list"></i> Material Requirements</h6>
                                    <table class="bom-table">
                                        <thead>
                                            <tr>
                                                <th>Material</th>
                                                <th>Per Unit</th>
                                                <th>Total Required</th>
                                                <th>Available</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="bomRequirementsBody"></tbody>
                                    </table>
                                    <div id="insufficientWarning" class="alert alert-danger mt-3" style="display: none;">
                                        <i class="fas fa-exclamation-triangle"></i> Insufficient material stock.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="submit-container mt-4">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn" disabled>
                                <i class="fas fa-industry"></i> Complete Handover
                            </button>
                            <button type="button" class="btn btn-secondary ms-2" onclick="location.href='create_handover.php'">
                                <i class="fas fa-arrow-left"></i> Back to Step 1
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/footer.php'); ?>
    <?php include($_SERVER['DOCUMENT_ROOT'] . '/lily_collection/dist/include/scripts.php'); ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        <?php if ($step === 2): ?>
        const allBomData = <?php echo json_encode($allBomData); ?>;
        const availableBalances = <?php echo json_encode($availableBalances); ?>;

        $(document).ready(function() {
            $('#product_id, #quantity_to_produce').on('change input', function() {
                calculateRequirements();
            });

            $('#handoverForm').on('submit', function(e) {
                e.preventDefault();
                submitFormAjax();
            });
        });

        function calculateRequirements() {
            const productId = $('#product_id').val();
            const qty = parseInt($('#quantity_to_produce').val()) || 0;
            const $reqDiv = $('#bomRequirements');
            const $tbody = $('#bomRequirementsBody');
            const $warning = $('#insufficientWarning');
            const $btn = $('#submitBtn');

            if (!productId || qty <= 0) {
                $reqDiv.hide();
                $btn.prop('disabled', true);
                return;
            }

            const bom = allBomData[productId] || [];
            if (bom.length === 0) {
                $reqDiv.hide();
                return;
            }

            $reqDiv.show();
            $tbody.empty();
            let allSufficient = true;

            bom.forEach(item => {
                const total = item.quantity_required * qty;
                const available = availableBalances[item.material_id] || 0;
                const sufficient = available >= total;
                if (!sufficient) allSufficient = false;

                $tbody.append(`
                    <tr>
                        <td>${item.material_name}</td>
                        <td>${item.quantity_required}</td>
                        <td>${total}</td>
                        <td style="color: ${sufficient ? 'green' : 'red'}">${available}</td>
                        <td>${sufficient ? '<span class="badge-sufficient">Sufficient</span>' : '<span class="badge-shortfall">Insufficient</span>'}</td>
                    </tr>
                `);
            });

            if (allSufficient) {
                $warning.hide();
                $btn.prop('disabled', false);
            } else {
                $warning.show();
                $btn.prop('disabled', true);
            }
        }

        function submitFormAjax() {
            $('#loadingOverlay').css('display', 'flex');
            $.ajax({
                url: 'save_handover.php',
                type: 'POST',
                data: new FormData($('#handoverForm')[0]),
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    $('#loadingOverlay').hide();
                    if (res.success) {
                        alert(res.message);
                        window.location.href = 'handover_list.php';
                    } else {
                        alert(res.message);
                    }
                },
                error: function() {
                    $('#loadingOverlay').hide();
                    alert('An error occurred.');
                }
            });
        }
        <?php endif; ?>
    </script>
</body>
</html>
