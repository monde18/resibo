<?php
include 'config.php';

// Handle form submission for adding/editing
$edit_mode = false;
$edit_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_fee'])) {
        $code = !empty($_POST['code']) ? trim($_POST['code']) : null;
        $account_name = !empty($_POST['account_name']) ? trim($_POST['account_name']) : null;
        $constant_value = !empty($_POST['constant_value']) ? trim($_POST['constant_value']) : null;
        $amount = !empty($_POST['amount']) ? trim($_POST['amount']) : null;

        if (!empty($code) || !empty($account_name) || !empty($constant_value) || !empty($amount)) {
            // Check if we're in edit mode
            if (isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
                $edit_id = $_POST['edit_id'];
                $stmt = $conn->prepare("UPDATE fees SET code=?, account_name=?, constant_value=?, amount=? WHERE id=?");
                $stmt->bind_param("sssdi", $code, $account_name, $constant_value, $amount, $edit_id);
                $action = "updated";
            } else {
                $stmt = $conn->prepare("INSERT INTO fees (code, account_name, constant_value, amount) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssd", $code, $account_name, $constant_value, $amount);
                $action = "added";
            }
            
            if ($stmt->execute()) {
                $success = "Fee $action successfully!";
            } else {
                $error = "Error: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error = "At least one field must be filled.";
        }
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM fees WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success = "Fee deleted successfully!";
    } else {
        $error = "Error deleting fee: " . $conn->error;
    }
    $stmt->close();
}

// Handle edit action - fetch the fee to edit
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM fees WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $edit_fee = $result->fetch_assoc();
        $edit_mode = true;
    }
    $stmt->close();
}

// Pagination setup
$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $results_per_page;

// Get total number of records
$total_result = $conn->query("SELECT COUNT(*) as total FROM fees");
$total_row = $total_result->fetch_assoc();
$total_records = $total_row['total'];
$total_pages = ceil($total_records / $results_per_page);

// Fetch fees with pagination
$fees = [];
$result = $conn->query("SELECT * FROM fees ORDER BY created_at DESC LIMIT $offset, $results_per_page");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fees[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fees</title>
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- Materialize CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
            background-color: #f5f5f5;
        }
        main {
            flex: 1 0 auto;
            padding: 20px 0;
        }
        .container {
            width: 95%;
        }
        .card {
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-flat {
            text-transform: none;
        }
        .pagination li.active {
            background-color: #26a69a;
        }
        .modal {
            border-radius: 8px;
        }
        .action-btn {
            margin: 0 5px;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .edit-confirm {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background-color: #e3f2fd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    
    <main>
        <div class="container">
            <h4 class="teal-text text-darken-2">Manage Fees</h4>

            <!-- Success / Error messages -->
            <?php if (!empty($success)): ?>
                <div class="card-panel teal lighten-2 white-text"><?= $success ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="card-panel red lighten-1 white-text"><?= $error ?></div>
            <?php endif; ?>

            <!-- Add/Edit Fee Card -->
            <div class="card">
                <div class="card-content">
                    <span class="card-title"><?= $edit_mode ? 'Edit Fee' : 'Add New Fee' ?></span>
                    <form method="POST" id="feeForm">
                        <div class="row">
                            <div class="input-field col s12 m6 l3">
                                <input type="text" name="code" id="code" value="<?= $edit_mode ? htmlspecialchars($edit_fee['code']) : '' ?>">
                                <label for="code">Code</label>
                            </div>
                            <div class="input-field col s12 m6 l3">
                                <input type="text" name="account_name" id="account_name" value="<?= $edit_mode ? htmlspecialchars($edit_fee['account_name']) : '' ?>">
                                <label for="account_name">Account Name</label>
                            </div>
                            <div class="input-field col s12 m6 l3">
                                <input type="text" name="constant_value" id="constant_value" value="<?= $edit_mode ? htmlspecialchars($edit_fee['constant_value']) : '' ?>">
                                <label for="constant_value">Constant Value</label>
                            </div>
                            <div class="input-field col s12 m6 l3">
                                <input type="number" step="0.01" name="amount" id="amount" value="<?= $edit_mode ? htmlspecialchars($edit_fee['amount']) : '' ?>">
                                <label for="amount">Amount</label>
                            </div>
                        </div>
                        
                        <?php if ($edit_mode): ?>
                            <input type="hidden" name="edit_id" value="<?= $edit_id ?>">
                            
                            <!-- Edit Confirmation Section -->
                            <div class="edit-confirm" id="editConfirm">
                                <h6>Confirm Changes</h6>
                                <p>Are you sure you want to update this fee record?</p>
                                <div>
                                    <button type="submit" name="save_fee" class="btn teal waves-effect waves-light">
                                        Confirm Update
                                        <i class="material-icons right">check</i>
                                    </button>
                                    <a href="fees1.php" class="btn grey waves-effect waves-light">Cancel</a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-action">
                            <?php if ($edit_mode): ?>
                                <button type="button" id="reviewChanges" class="btn teal waves-effect waves-light">
                                    Review Changes
                                    <i class="material-icons right">visibility</i>
                                </button>
                            <?php else: ?>
                                <button type="submit" name="save_fee" class="btn teal waves-effect waves-light">
                                    Add Fee
                                    <i class="material-icons right">add</i>
                                </button>
                            <?php endif; ?>
                            
                            <?php if ($edit_mode): ?>
                                <a href="fees1.php" class="btn grey waves-effect waves-light">Cancel Edit</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Fees Table Card -->
            <div class="card">
                <div class="card-content">
                    <span class="card-title">Fee Records</span>
                    
                    <!-- Search Box -->
                    <div class="row search-box">
                        <div class="input-field col s12">
                            <i class="material-icons prefix">search</i>
                            <input type="text" id="search" placeholder="Search fees...">
                        </div>
                    </div>
                    
                    <?php if (!empty($fees)): ?>
                    <table class="striped responsive-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Code</th>
                                <th>Account Name</th>
                                <th>Constant Value</th>
                                <th>Amount</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fees as $fee): ?>
                                <tr>
                                    <td><?= $fee['id'] ?></td>
                                    <td><?= htmlspecialchars($fee['code']) ?></td>
                                    <td><?= htmlspecialchars($fee['account_name']) ?></td>
                                    <td><?= htmlspecialchars($fee['constant_value']) ?></td>
                                    <td><?= is_null($fee['amount']) ? '' : number_format($fee['amount'], 2) ?></td>
                                    <td><?= $fee['created_at'] ?></td>
                                    <td>
                                        <a href="?edit=<?= $fee['id'] ?>" class="btn-flat waves-effect waves-teal tooltipped" data-tooltip="Edit">
                                            <i class="material-icons teal-text">edit</i>
                                        </a>
                                        <a href="#delete-modal-<?= $fee['id'] ?>" class="btn-flat waves-effect waves-red tooltipped modal-trigger" data-tooltip="Delete">
                                            <i class="material-icons red-text">delete</i>
                                        </a>
                                        
                                        <!-- Delete Confirmation Modal -->
                                        <div id="delete-modal-<?= $fee['id'] ?>" class="modal">
                                            <div class="modal-content">
                                                <h5>Confirm Deletion</h5>
                                                <p>Are you sure you want to delete this fee record?</p>
                                            </div>
                                            <div class="modal-footer">
                                                <a href="#" class="modal-close waves-effect waves-green btn-flat">Cancel</a>
                                                <a href="?delete=<?= $fee['id'] ?>" class="waves-effect waves-red btn red">Delete</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <ul class="pagination center">
                        <?php if ($page > 1): ?>
                            <li class="waves-effect"><a href="?page=<?= $page - 1 ?>"><i class="material-icons">chevron_left</i></a></li>
                        <?php else: ?>
                            <li class="disabled"><a href="#!"><i class="material-icons">chevron_left</i></a></li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $start_page + 4);
                        
                        if ($end_page - $start_page < 4) {
                            $start_page = max(1, $end_page - 4);
                        }
                        
                        for ($i = $start_page; $i <= $end_page; $i++): 
                        ?>
                            <li class="<?= $i == $page ? 'active' : 'waves-effect' ?>">
                                <a href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="waves-effect"><a href="?page=<?= $page + 1 ?>"><i class="material-icons">chevron_right</i></a></li>
                        <?php else: ?>
                            <li class="disabled"><a href="#!"><i class="material-icons">chevron_right</i></a></li>
                        <?php endif; ?>
                    </ul>
                    <?php endif; ?>
                    
                    <?php else: ?>
                        <p class="center-align">No fees found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Materialize JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        // Initialize Materialize components
        document.addEventListener('DOMContentLoaded', function() {
            var modals = document.querySelectorAll('.modal');
            M.Modal.init(modals);
            
            var tooltips = document.querySelectorAll('.tooltipped');
            M.Tooltip.init(tooltips);
            
            // Simple search functionality
            var searchInput = document.getElementById('search');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    var filter = this.value.toLowerCase();
                    var rows = document.querySelectorAll('tbody tr');
                    
                    rows.forEach(function(row) {
                        var text = row.textContent.toLowerCase();
                        row.style.display = text.indexOf(filter) > -1 ? '' : 'none';
                    });
                });
            }
            
            // Edit confirmation functionality
            var reviewButton = document.getElementById('reviewChanges');
            if (reviewButton) {
                reviewButton.addEventListener('click', function() {
                    document.getElementById('editConfirm').style.display = 'block';
                    this.style.display = 'none';
                });
            }
            
            // Form validation
            var form = document.getElementById('feeForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    var code = document.getElementById('code').value;
                    var accountName = document.getElementById('account_name').value;
                    var constantValue = document.getElementById('constant_value').value;
                    var amount = document.getElementById('amount').value;
                    
                    if (!code && !accountName && !constantValue && !amount) {
                        e.preventDefault();
                        M.toast({html: 'At least one field must be filled', classes: 'red'});
                    }
                });
            }
        });
    </script>
</body>
</html>