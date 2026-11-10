 

<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="/assets/backend/css/team-management.css">
<?php $__env->stopPush(); ?>

<?php $__env->startSection('title', 'Teachers & Team'); ?> 
<?php $__env->startSection('page-title', 'Teachers & Team'); ?> 
<?php $__env->startSection('content'); ?>

<div class="welcome">
    <div>
        <h1>Mode 3 Teacher Team</h1>
        <p>Create teacher login accounts and allocate papers to them from the assessment screen.</p>
    </div>
</div>

<div class="admin-grid">
    <section class="panel">
        <div class="panel-head">
            <h2>Teacher accounts</h2>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: center; min-width: 140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $members; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><strong><?php echo e($m->name); ?></strong></td>
                        <td><?php echo e($m->email); ?></td>
                        <td><?php echo e($m->role_title ?? 'Teacher'); ?></td>
                        <td style="text-align: center;">
                            <span class="status-badge <?php echo e($m->account_status === 'active' ? 'active' : ($m->account_status === 'suspended' ? 'suspended' : 'inactive')); ?>">
                                <?php echo e(ucfirst($m->account_status)); ?>

                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div class="action-buttons">
                                <button class="btn small ghost edit-btn" 
                                        data-id="<?php echo e($m->id); ?>"
                                        data-name="<?php echo e($m->name); ?>"
                                        data-email="<?php echo e($m->email); ?>"
                                        data-mobile="<?php echo e($m->mobile); ?>"
                                        data-role-title="<?php echo e($m->role_title); ?>"
                                        data-status="<?php echo e($m->account_status); ?>"
                                        onclick="openEditModal(this)">
                                    ✏️ Edit
                                </button>
                                <form method="post" action="<?php echo e(route('team.destroy', $m)); ?>" onsubmit="return confirm('Are you sure you want to remove this teacher?')">
                                    <?php echo csrf_field(); ?> 
                                    <?php echo method_field('DELETE'); ?>
                                    <button class="btn small ghost danger">🗑️ Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" class="text-center">No teacher accounts yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>

    <aside class="panel">
        <h2>Add teacher</h2>
        <form method="post" action="<?php echo e(route('team.store')); ?>">
            <?php echo csrf_field(); ?>
            
            <div class="form-group">
                <label>Name <span class="required">*</span></label>
                <input class="input" name="name" required placeholder="Enter full name">
            </div>
            
            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input class="input" type="email" name="email" required placeholder="teacher@example.com">
            </div>
            
            <div class="form-group">
                <label>Mobile</label>
                <input class="input" name="mobile" placeholder="+1234567890">
            </div>
            
            <div class="form-group">
                <label>Role title</label>
                <input class="input" name="role_title" placeholder="Physics Teacher">
            </div>
            
            <div class="form-group">
                <label>Temporary password <span class="required">*</span></label>
                <input class="input" type="password" name="password" required placeholder="Min 8 characters">
            </div>
            
            <button class="btn full">Create Teacher Login</button>
        </form>
    </aside>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal" style="display:none;">
    <div class="modal">
        <div class="modal-header">
            <h2>Edit Teacher</h2>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="post" id="editForm" action="">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            
            <input type="hidden" name="id" id="editId">
            
            <div class="form-group">
                <label>Name <span class="required">*</span></label>
                <input class="input" name="name" id="editName" required>
            </div>
            
            <div class="form-group">
                <label>Email <span class="required">*</span></label>
                <input class="input" type="email" name="email" id="editEmail" required>
            </div>
            
            <div class="form-group">
                <label>Mobile</label>
                <input class="input" name="mobile" id="editMobile" placeholder="+1234567890">
            </div>
            
            <div class="form-group">
                <label>Role title</label>
                <input class="input" name="role_title" id="editRoleTitle" placeholder="Physics Teacher">
            </div>
            
            <div class="form-group">
                <label>Status <span class="required">*</span></label>
                <select class="input" name="account_status" id="editStatus">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>New Password <small>(leave blank to keep current)</small></label>
                <input class="input" type="password" name="password" placeholder="Enter new password (min 8 chars)">
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn ghost" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn">Update Teacher</button>
            </div>
        </form>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
function openEditModal(button) {
    // Get data from button attributes
    const id = button.dataset.id;
    const name = button.dataset.name;
    const email = button.dataset.email;
    const mobile = button.dataset.mobile || '';
    const roleTitle = button.dataset.roleTitle || '';
    const status = button.dataset.status || 'active';
    
    // Populate form fields
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editEmail').value = email;
    document.getElementById('editMobile').value = mobile;
    document.getElementById('editRoleTitle').value = roleTitle;
    document.getElementById('editStatus').value = status;
    
    // Set form action
    document.getElementById('editForm').action = '/team/' + id;
    
    // Show modal
    document.getElementById('editModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('editModal');
    if (event.target === modal) {
        closeEditModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeEditModal();
    }
});

// Auto-close alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/team/index.blade.php ENDPATH**/ ?>