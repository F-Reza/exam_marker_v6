@extends('layouts.app') 
@section('title','Check New Paper') 
@section('page-title','Check New Paper') 
@section('content')

<div class="wizard-head">
    <div>
        <h1>Check a new paper</h1>
        <p>Complete the guided flow: details, QP + MS, then WA and confirmation.</p>
    </div>
    <span class="plan-chip">{{auth()->user()->plan?->name}}</span>
</div>

<!-- FIXED: Added action="{{ route('assessments.store') }}" -->
<form method="post" action="{{ route('assessments.store') }}" enctype="multipart/form-data" class="upload-layout" id="paperWizard">
    @csrf
    <div class="panel upload-main">
        <b class="mobile-step-label" id="mobileStepLabel">Step 1 of 3</b>
        <div class="stepper wizard-stepper">
            <span class="active" data-step-dot="1">1<small>Details</small></span><i></i>
            <span data-step-dot="2">2<small>QP & MS</small></span><i></i>
            <span data-step-dot="3">3<small>WA & Confirm</small></span>
        </div>

        <section class="wizard-pane active" data-step="1">
            <h2>Assessment details</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>Assessment title <span class="required">*</span></label>
                    <input class="input" name="title" value="{{old('title')}}" placeholder="e.g. Algebra Test" required>
                </div>
                <div class="form-group">
                    <label>Subject <span class="required">*</span></label>
                    <input class="input" name="subject" value="{{old('subject')}}" placeholder="Mathematics" required>
                </div>
                <div class="form-group">
                    <label>Grade/Class</label>
                    <input class="input" name="grade" value="{{old('grade')}}" placeholder="Grade 10">
                </div>
                <div class="form-group">
                    <label>Exam board</label>
                    <input class="input" name="exam_board" value="{{old('exam_board')}}" placeholder="Cambridge / IB / ISC">
                </div>
                <div class="form-group">
                    <label>Total marks <span class="required">*</span></label>
                    <input class="input" type="number" step="0.5" name="total_marks" value="{{old('total_marks',30)}}" required>
                </div>
                <div class="form-group">
                    <label>Assessment date</label>
                    <input class="input" type="date" name="assessment_date" value="{{old('assessment_date')}}">
                </div>

                @if(auth()->user()->user_type !== 'student')
                    <div class="form-group span-2">
                        <label>Student</label>
                        <select class="input" name="student_id">
                            <option value="">Individual paper / no student selected</option>
                            @foreach($students as $s)
                                <option value="{{$s->id}}" @selected(old('student_id')==$s->id)>
                                    {{$s->name}} {{$s->roll_number?'('.$s->roll_number.')':''}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if(auth()->user()->hasFeature('teacher_assignment') && auth()->user()->user_type !== 'student' && isset($teachers) && $teachers->count() > 0)
                    <div class="form-group span-2">
                        <label>Assign teacher</label>
                        <select class="input" name="assigned_teacher_id">
                            <option value="">Not assigned</option>
                            @foreach($teachers as $teacher)
                                <option value="{{$teacher->id}}" @selected(old('assigned_teacher_id')==$teacher->id)>
                                    {{$teacher->name}} {{$teacher->role_title?'— '.$teacher->role_title:''}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        </section>

        <section class="wizard-pane" data-step="2">
            <h2>Upload Question Paper and Mark Scheme</h2>
            <p class="muted">QP and MS are combined on this page. MS is optional. For English, Humanities and source-based papers, upload one or more Inserts/Source Booklets separately.</p>
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
                    <b>Mark Scheme (optional)</b>
                    <small>Optional · PDF, DOC or DOCX · Max 50 MB</small>
                    <input type="file" name="mark_scheme" accept=".pdf,.doc,.docx">
                    <em>Browse MS</em>
                    <output class="file-name">No file selected</output>
                </label>
            </div>
            <label class="dropzone wide insert-zone">
                <span class="file-icon insert">IN</span>
                <b>Insert / Source Booklet (optional)</b>
                <small>Upload up to 5 PDF, DOC or DOCX files — useful for English Checkpoint, comprehension, case studies and source papers.</small>
                <input type="file" name="inserts[]" accept=".pdf,.doc,.docx" multiple>
                <em>Browse Insert(s)</em>
                <output class="file-name">No insert selected</output>
            </label>
            <div class="info-note">
                <span class="info-icon">ⓘ</span>
                <span>No MS? The system will generate a suggested marking structure and flag the result for manual verification.</span>
            </div>
        </section>

        <section class="wizard-pane" data-step="3">
            <h2>Upload Written Answer Paper</h2>
            <label class="dropzone wide">
                <span class="file-icon wa">WA</span>
                <b>Written Answer Paper (WA)</b>
                <small>Compulsory · PDF, DOC or DOCX · Max 50 MB</small>
                <input type="file" name="written_answer" accept=".pdf,.doc,.docx" required>
                <em>Browse WA</em>
                <output class="file-name">No file selected</output>
            </label>
            <div class="confirm-grid">
                <article>
                    <b>🔒 Secure processing</b>
                    <p>Files are stored privately and can be opened only by the account owner.</p>
                </article>
                <article>
                    <b>👨‍🏫 Human review</b>
                    <p>AI marks are suggestions. Mode 2 and Mode 3 users can edit and finalise them.</p>
                </article>
            </div>
        </section>

        <div class="wizard-actions">
            <button type="button" class="btn ghost" id="wizardBack" disabled>← Back</button>
            <button type="button" class="btn" id="wizardNext">Next →</button>
            <button class="btn primary" id="wizardSubmit" hidden>Upload & Continue</button>
        </div>
    </div>

    <aside>
        <div class="panel requirement-card">
            <h3>📋 Upload checklist</h3>
            <ul>
                <li id="checkDetails">○ Assessment details</li>
                <li id="checkQp">○ Question Paper</li>
                <li id="checkMs">○ Mark Scheme (optional)</li>
                <li id="checkWa">○ Written Answer</li>
            </ul>
        </div>
        <div class="panel tip-card">
            <h3>💡 For better accuracy</h3>
            <p>Use clear, complete files. Keep question numbers visible and avoid rotated or cropped pages.</p>
        </div>
    </aside>
</form>

@push('scripts')
<script>
    (function() {
        let step = 1;
        const panes = [...document.querySelectorAll('.wizard-pane')],
            dots = [...document.querySelectorAll('[data-step-dot]')],
            back = document.getElementById('wizardBack'),
            next = document.getElementById('wizardNext'),
            submit = document.getElementById('wizardSubmit'),
            label = document.getElementById('mobileStepLabel');

        const show = () => {
            panes.forEach(p => p.classList.toggle('active', +p.dataset.step === step));
            dots.forEach(d => {
                d.classList.toggle('active', +d.dataset.stepDot === step);
                d.classList.toggle('done', +d.dataset.stepDot < step)
            });
            back.disabled = step === 1;
            next.hidden = step === 3;
            submit.hidden = step !== 3;
            label.textContent = `Step ${step} of 3`;
        };

        const validateStep = () => {
            const pane = document.querySelector(`[data-step="${step}"]`);
            const required = pane.querySelectorAll('[required]');
            let valid = true;
            
            required.forEach(el => {
                if (!el.value) {
                    el.reportValidity();
                    valid = false;
                }
            });
            
            return valid;
        };

        next.onclick = () => {
            if (validateStep()) {
                step++;
                show();
            }
        };
        
        back.onclick = () => {
            step--;
            show();
        };

        // File input display
        document.querySelectorAll('.dropzone input[type="file"]').forEach(input => {
            input.addEventListener('change', function() {
                const dropzone = this.closest('.dropzone');
                const output = dropzone.querySelector('.file-name');
                
                if (this.files.length > 0) {
                    // Check if this is the Insert section (has name="inserts[]")
                    const isInsert = this.getAttribute('name') === 'inserts[]';
                    
                    if (isInsert) {
                        // For inserts, show the actual file name(s)
                        if (this.files.length === 1) {
                            output.textContent = this.files[0].name;
                        } else {
                            // If multiple, list them or show count
                            const names = Array.from(this.files).map(f => f.name).join(', ');
                            output.textContent = names.length > 50 ? `${this.files.length} files selected` : names;
                        }
                    } else {
                        // For single files (QP, MS, WA), show the name
                        output.textContent = this.files[0].name;
                    }
                    output.style.color = '#16a34a'; // Green
                } else {
                    output.textContent = this.hasAttribute('multiple') ? 'No insert selected' : 'No file selected';
                    output.style.color = '#94a3b8'; // Grey
                }
                
                // Update checklist
                if (this.name === 'question_paper') {
                    document.getElementById('checkQp').textContent = 
                        (this.files.length ? '✓ ' : '○ ') + 'Question Paper';
                } else if (this.name === 'mark_scheme') {
                    document.getElementById('checkMs').textContent = 
                        (this.files.length ? '✓ ' : '○ ') + 'Mark Scheme (optional)';
                } else if (this.name === 'written_answer') {
                    document.getElementById('checkWa').textContent = 
                        (this.files.length ? '✓ ' : '○ ') + 'Written Answer';
                }
            });
        });

        // Assessment details validation
        const titleInput = document.querySelector('[name="title"]');
        const subjectInput = document.querySelector('[name="subject"]');
        const marksInput = document.querySelector('[name="total_marks"]');
        
        const updateDetailsCheck = () => {
            const check = document.getElementById('checkDetails');
            const title = titleInput?.value || '';
            const subject = subjectInput?.value || '';
            const marks = marksInput?.value || '';
            
            check.textContent = (title && subject && marks) ? '✓ ' : '○ ';
            check.textContent += 'Assessment details';
        };
        
        [titleInput, subjectInput, marksInput].forEach(el => {
            if (el) el.addEventListener('input', updateDetailsCheck);
        });
        
        show();
    })();
</script>
@endpush

@endsection