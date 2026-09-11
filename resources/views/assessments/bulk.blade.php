@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="/assets/backend/css/bulk-upload-check.css">
@endpush

@section('title','Bulk Check Papers')
@section('page-title','Bulk Check Papers')

@section('content')


@push('styles')

<style>

.bulk-wrapper
{
    display:flex;
    flex-direction:column;
    gap:25px;
}



.panel
{
    background:#fff;
    border-radius:18px;
    padding:28px;
    box-shadow:0 8px 25px rgba(0,0,0,.05);
}



.welcome
{
    margin-bottom:25px;
}



.welcome h1
{
    margin-bottom:8px;
}



.welcome p
{
    color:#64748b;
}




.form-grid
{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:18px;
    margin-bottom:30px;
}



label
{
    font-weight:600;
    color:#334155;
}



.input
{
    width:100%;
    margin-top:8px;
    padding:12px 14px;
    border-radius:10px;
    border:1px solid #dbe3ef;
}



.input:focus
{
    outline:none;
    border-color:#2563eb;
}






h2
{
    color:#13213f;
    font-size:20px;
    margin:30px 0 18px;
}





.file-grid
{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:20px;
}




.dropzone
{
    border:2.3px dashed #cbd5e1;
    border-radius:12px;
    padding:25px;
    text-align:center;
    cursor:pointer;
    background:#f8fafc;
    transition:.2s;
}



.dropzone:hover
{
    border-color:#2563eb;
    background:#eff6ff;
}



.dropzone input[type=file]
{
    margin-top:15px;
    width:100%;
}



.dropzone b
{
    display:block;
    margin-top:10px;
    font-size:16px;
}



.dropzone small
{
    display:block;
    margin-top:8px;
    color:#64748b;
}



.dropzone em
{
    display:inline-block;
    margin-top:15px;
    color:#2563eb;
    font-style:normal;
    font-weight:600;
}



.file-icon
{
    display:inline-flex;
    width:55px;
    height:55px;
    align-items:center;
    justify-content:center;
    border-radius:14px;
    font-weight:800;
    color: #e22b50;
    background: #ffe9ef;
}



.file-icon.ms
{
    background:#16a34a;
}



.file-icon.insert
{
    background:#9333ea;
}



.wide
{
    width:100%;
    margin-top:20px;
}




.info-note
{
    background:#eff6ff;
    color:#1e40af;
    padding:15px;
    border-radius:12px;
    margin-bottom:20px;
}




.bulk-row
{
    display:grid;
    grid-template-columns:2fr 1fr auto;
    gap:15px;
    align-items:center;
    background:#f8fafc;
    padding:18px;
    border-radius:14px;
    margin-bottom:15px;
}



.student-select
{
    margin-bottom:10px;
}



.remove-row
{
    white-space:nowrap;
}



.wizard-actions
{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:20px;
    margin-top:25px;
}



.wizard-actions label
{
    display:flex;
    align-items:center;
    gap:8px;
}




.btn.ghost
{
    background:#f1f5f9;
    color:#334155;
}





@media(max-width:1000px)
{


.form-grid
{
    grid-template-columns:repeat(2,1fr);
}



.bulk-row
{
    grid-template-columns:1fr;
}


}



@media(max-width:650px)
{


.form-grid,
.file-grid
{
    grid-template-columns:1fr;
}



.wizard-actions
{
    flex-direction:column;
    align-items:stretch;
}



}


</style>


@endpush



<div class="welcome">
    <div>
        <h1>📚 Bulk WA Upload</h1>
        <p>Mode 2 and Mode 3: upload one QP, optional MS and written answers for multiple students.</p>
    </div>
</div>

<form method="POST" enctype="multipart/form-data" class="panel" action="{{route('assessments.bulk.store')}}">
    @csrf

    <div class="form-grid">
        <label>
            Assessment Title <span class="required">*</span>
            <input class="input" name="title" placeholder="e.g. Physics Mid Term Examination" required>
        </label>

        <label>
            Subject <span class="required">*</span>
            <input class="input" name="subject" placeholder="e.g. Physics, Mathematics, Biology" required>
        </label>

        <label>
            Grade/Class
            <input class="input" name="grade" placeholder="e.g. Grade 10, MYP 5, IB DP1">
        </label>

        <label>
            Total Marks <span class="required">*</span>
            <input class="input" type="number" step="0.5" name="total_marks" value="30" placeholder="e.g. 30" required>
        </label>
    </div>

    <h2>📄 Shared Question Paper and Mark Scheme</h2>

    <div class="file-grid">
        <label class="dropzone">
            <span class="file-icon qp">QP</span>
            <b>Question Paper</b>
            <small>Compulsory · PDF, DOC or DOCX · Max 50 MB</small>
            <input type="file" name="question_paper" accept=".pdf,.doc,.docx" required>
            <em>Browse QP</em>
            <output class="file-name">No file selected</output>
        </label>

        <label class="dropzone">
            <span class="file-icon ms">MS</span>
            <b>Mark Scheme (Optional)</b>
            <small>Optional · PDF, DOC or DOCX · Max 50 MB</small>
            <input type="file" name="mark_scheme" accept=".pdf,.doc,.docx">
            <em>Browse MS</em>
            <output class="file-name">No file selected</output>
        </label>
    </div>

    <label class="dropzone wide">
        <span class="file-icon insert">IN</span>
        <b>Insert / Source Booklet (Optional)</b>
        <small>Upload source files for English, comprehension or source-based papers. Max 5 files.</small>
        <input type="file" name="inserts[]" multiple accept=".pdf,.doc,.docx">
        <em>Browse Insert(s)</em>
        <output class="file-name">No insert selected</output>
    </label>

    <h2>👨‍🎓 Students and Written Answers</h2>

    <div class="info-note">
        <span class="info-icon">ℹ️</span>
        <span>Mode 2: Select existing students or type new student names. Each WA remains linked to that student.</span>
    </div>

    <div id="bulkRows">
        <div class="bulk-row">
            <div>
                <select class="input student-select" name="student_ids[]">
                    <option value="">Select existing student</option>
                    @foreach($students as $s)
                        <option value="{{$s->id}}">
                            {{$s->name}} {{$s->roll_number ? '('.$s->roll_number.')' : ''}}
                        </option>
                    @endforeach
                </select>

                <input class="input student-name" name="student_names[]" placeholder="Or type student name" required>
            </div>

            <div>
                <input class="input" type="file" name="written_answers[]" accept=".pdf,.doc,.docx" required>
                <small class="file-helper">PDF, DOC or DOCX</small>
            </div>

            <button type="button" class="btn ghost danger remove-row">✕ Remove</button>
        </div>
    </div>

    <div class="wizard-actions">
        <button type="button" class="btn ghost" id="addBulkRow">＋ Add Another Student</button>

        <div class="bulk-submit-options">
            <label class="checkbox-label">
                <input type="checkbox" name="start_now" value="1">
                Start checking immediately
            </label>

            <button class="btn primary">Upload Bulk Papers</button>
        </div>
    </div>
</form>

<template id="bulkTemplate">
    <div class="bulk-row">
        <div>
            <select class="input student-select" name="student_ids[]">
                <option value="">Select existing student</option>
                @foreach($students as $s)
                    <option value="{{$s->id}}">
                        {{$s->name}} {{$s->roll_number ? '('.$s->roll_number.')' : ''}}
                    </option>
                @endforeach
            </select>

            <input class="input student-name" name="student_names[]" placeholder="Or type student name" required>
        </div>

        <div>
            <input class="input" type="file" name="written_answers[]" accept=".pdf,.doc,.docx" required>
            <small class="file-helper">PDF, DOC or DOCX</small>
        </div>

        <button type="button" class="btn ghost danger remove-row">✕ Remove</button>
    </div>
</template>

@endsection

@push('scripts')
<script>
    (function() {
        const rows = document.getElementById('bulkRows');
        const template = document.getElementById('bulkTemplate');

        // Add new row
        document.getElementById('addBulkRow').onclick = function() {
            const clone = template.content.cloneNode(true);
            rows.appendChild(clone);
            updateRemoveButtons();
            
            // Initialize file input display for new row
            clone.querySelectorAll('input[type="file"]').forEach(input => {
                input.addEventListener('change', handleFileChange);
            });
        };

        // Handle file change
        function handleFileChange(e) {
            const input = e.target;
            // Find the closest container that holds the output/helper
            const container = input.closest('.dropzone') || input.closest('.bulk-row');
            
            // Prioritize finding the specific <output> element
            let output = container.querySelector('output.file-name');
            if (!output) {
                output = container.querySelector('.file-helper');
            }
            
            if (input.files.length > 0) {
                if (output) {
                    // Check if this is the Insert section (has name="inserts[]")
                    const isInsert = input.getAttribute('name') === 'inserts[]';
                    
                    if (isInsert) {
                        // For inserts, show the actual file name(s)
                        if (input.files.length === 1) {
                            output.textContent = input.files[0].name;
                        } else {
                            // If multiple, list them or show count
                            const names = Array.from(input.files).map(f => f.name).join(', ');
                            output.textContent = names.length > 50 ? `${input.files.length} files selected` : names;
                        }
                    } else {
                        // For single files (QP, MS, WA), show the name
                        output.textContent = input.files[0].name;
                    }
                    output.style.color = '#16a34a'; // Green
                }
            } else {
                if (output) {
                    output.textContent = input.hasAttribute('multiple') ? 'No insert selected' : 'No file selected';
                    output.style.color = '#94a3b8'; // Grey
                }
            }
        }

        // Remove row
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-row')) {
                const row = e.target.closest('.bulk-row');
                if (rows.children.length > 1) {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(-20px)';
                    setTimeout(function() {
                        row.remove();
                        updateRemoveButtons();
                    }, 300);
                } else {
                    alert('You need at least one student.');
                }
            }
        });

        // Auto-fill student name when selecting from dropdown
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('student-select') && e.target.value) {
                const opt = e.target.options[e.target.selectedIndex];
                const name = opt.text.replace(/\s*\([^)]*\)\s*$/, '').trim();
                const row = e.target.closest('.bulk-row');
                const nameInput = row.querySelector('.student-name');
                if (nameInput) {
                    nameInput.value = name;
                }
            }
        });

        // Update remove buttons visibility
        function updateRemoveButtons() {
            const btns = rows.querySelectorAll('.remove-row');
            btns.forEach(function(btn) {
                if (rows.children.length <= 1) {
                    btn.style.display = 'none';
                } else {
                    btn.style.display = 'inline-flex';
                }
            });
        }

        // Initialize file inputs on page load
        document.querySelectorAll('.dropzone input[type="file"], .bulk-row input[type="file"]').forEach(input => {
            input.addEventListener('change', handleFileChange);
        });

        // Initial update
        updateRemoveButtons();
    })();
</script>
@endpush