<?php
// admin/departments.php

$page_title = "Departments";
require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Handle Add/Edit/Delete operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        set_message('error', 'Invalid request');
        redirect('admin/departments.php');
    }

    $action = $_POST['action'];

    if ($action === 'add' || $action === 'edit') {
        $department_name = clean_input($_POST['department_name']);
        $department_code = clean_input($_POST['department_code']);
        $description = clean_input($_POST['description']);
        $status = clean_input($_POST['status']);

        if (empty($department_name) || empty($department_code)) {
            set_message('error', 'Department name and code are required');
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $conn->prepare("INSERT INTO departments (department_name, department_code, description, status)
                                           VALUES (?, ?, ?, ?)");
                    $stmt->execute([$department_name, $department_code, $description, $status]);
                    set_message('success', 'Department added successfully');
                } else {
                    $id = intval($_POST['id']);
                    $stmt = $conn->prepare("UPDATE departments SET department_name = ?, department_code = ?,
                                           description = ?, status = ? WHERE id = ?");
                    $stmt->execute([$department_name, $department_code, $description, $status, $id]);
                    set_message('success', 'Department updated successfully');
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    set_message('error', 'Department code already exists');
                } else {
                    set_message('error', 'Error: ' . $e->getMessage());
                }
            }
        }
        redirect('admin/departments.php');
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        try {
            $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->execute([$id]);
            set_message('success', 'Department deleted successfully');
        } catch (PDOException $e) {
            set_message('error', 'Cannot delete department. It may have associated courses.');
        }
        redirect('admin/departments.php');
    }
}

// Fetch all departments
$stmt = $conn->query("SELECT * FROM departments ORDER BY id DESC");
$departments = $stmt->fetchAll();

require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Departments</h2>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">
        <i class="bi bi-plus-circle me-2"></i>Add Department
    </button>
</div>

<?php display_message(); ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Department Name</th>
                        <th>Department Code</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($departments) > 0): ?>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td><?php echo $dept['id']; ?></td>
                                <td><strong><?php echo $dept['department_name']; ?></strong></td>
                                <td><span class="badge bg-secondary"><?php echo $dept['department_code']; ?></span></td>
                                <td><?php echo $dept['description']; ?></td>
                                <td>
                                    <span class="badge <?php echo $dept['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo ucfirst($dept['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo format_date($dept['created_at']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info edit-btn"
                                            data-id="<?php echo $dept['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($dept['department_name']); ?>"
                                            data-code="<?php echo $dept['department_code']; ?>"
                                            data-description="<?php echo htmlspecialchars($dept['description']); ?>"
                                            data-status="<?php echo $dept['status']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $dept['id']; ?>">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No departments found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Department Modal -->
<div class="modal fade" id="addDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="add">

                    <div class="mb-3">
                        <label class="form-label">Department Name *</label>
                        <input type="text" class="form-control" name="department_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Department Code *</label>
                        <input type="text" class="form-control" name="department_code" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Department Modal -->
<div class="modal fade" id="editDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">

                    <div class="mb-3">
                        <label class="form-label">Department Name *</label>
                        <input type="text" class="form-control" name="department_name" id="edit_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Department Code *</label>
                        <input type="text" class="form-control" name="department_code" id="edit_code" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" id="edit_status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteDepartmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <p>Are you sure you want to delete this department?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit button click
document.querySelectorAll('.edit-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('edit_id').value = this.dataset.id;
        document.getElementById('edit_name').value = this.dataset.name;
        document.getElementById('edit_code').value = this.dataset.code;
        document.getElementById('edit_description').value = this.dataset.description;
        document.getElementById('edit_status').value = this.dataset.status;
        new bootstrap.Modal(document.getElementById('editDepartmentModal')).show();
    });
});

// Delete button click
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('delete_id').value = this.dataset.id;
        new bootstrap.Modal(document.getElementById('deleteDepartmentModal')).show();
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin-footer.php'; ?>