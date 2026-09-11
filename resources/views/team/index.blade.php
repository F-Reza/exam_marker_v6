@extends('layouts.app') 

@push('styles')
    <link rel="stylesheet" href="/assets/backend/css/team-management.css">
@endpush

@section('title', 'Teachers & Team') 
@section('page-title', 'Teachers & Team') 
@section('content')

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
                @forelse($members as $m)
                    <tr>
                        <td><strong>{{$m->name}}</strong></td>
                        <td>{{$m->email}}</td>
                        <td>{{$m->role_title ?? 'Teacher'}}</td>
                        <td style="text-align: center;">
                            <span class="status-badge {{$m->account_status === 'active' ? 'active' : ($m->account_status === 'suspended' ? 'suspended' : 'inactive')}}">
                                {{ucfirst($m->account_status)}}
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div class="action-buttons">
                                <button class="btn small ghost edit-btn" 
                                        data-id="{{$m->id}}"
                                        data-name="{{$m->name}}"
                                        data-email="{{$m->email}}"
                                        data-mobile="{{$m->mobile}}"
                                        data-role-title="{{$m->role_title}}"
                                        data-status="{{$m->account_status}}"
                                        onclick="openEditModal(this)">
                                    ✏️ Edit
                                </button>
                                <form method="post" action="{{route('team.destroy', $m)}}" onsubmit="return confirm('Are you sure you want to remove this teacher?')">
                                    @csrf 
                                    @method('DELETE')
                                    <button class="btn small ghost danger">🗑️ Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No teacher accounts yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <aside class="panel">
        <h2>Add teacher</h2>
        <form method="post" action="{{route('team.store')}}">
            @csrf
            
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
            @csrf
            @method('PUT')
            
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

@endsection

@push('scripts')
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
@endpush