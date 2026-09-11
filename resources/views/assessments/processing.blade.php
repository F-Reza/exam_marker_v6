@extends('layouts.app') @section('title','Checking Paper') @section('page-title','Paper Checking') @section('content')
<div class="processing-shell panel">
 <div class="ai-doc-icon">AI</div><h1>Checking Your Paper...</h1><p>Please wait while the system reads and evaluates the uploaded files.</p>
 <div class="progress-ring" id="progressRing"><span id="progressText">12%</span></div>
 <div class="progress-track"><i id="progressBar"></i></div>
 <ul class="process-list"><li class="active">Reading Question Paper</li><li>Reading Mark Scheme</li><li>Reading Written Answer</li><li>Matching answers to criteria</li><li>Calculating marks and feedback</li><li>Generating report</li></ul>
 <div class="info-note">Do not close this page while processing. You will be redirected automatically.</div>
</div>
<script>
(()=>{let p=12;const text=document.getElementById('progressText'),bar=document.getElementById('progressBar'),ring=document.getElementById('progressRing'),items=[...document.querySelectorAll('.process-list li')];const tick=()=>{p=Math.min(100,p+Math.ceil(Math.random()*8));text.textContent=p+'%';bar.style.width=p+'%';ring.style.setProperty('--value',p);items.forEach((x,i)=>{x.classList.toggle('done',p>(i+1)*15);x.classList.toggle('active',p<= (i+1)*15 && p>(i)*15)});if(p>=100) location.href='{{route('results.show',$assessment)}}';else setTimeout(tick,380)};setTimeout(tick,500)})();
</script>
@endsection
