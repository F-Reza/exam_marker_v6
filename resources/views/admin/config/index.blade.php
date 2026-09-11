@extends('layouts.app')


@section('title','System Configuration')


@section('page-title','System Configuration')



@push('styles')

<style>

.panel h3
{
    text-align:center;
    font-size:16px;
    font-weight:700;
    color:#13213f;
}
.panel
{
    background:none;
}


.config-image
{
    border-radius:12px;
    border:1px solid #dce5f2;
    padding:5px;
    background:white;
}


.remove-option
{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:14px;
    color:#dc2626;
    margin-top:10px;
}


</style>

@endpush





@section('content')





<div class="welcome">


<div>


<h1>
System Configuration
</h1>


<p>
Manage website branding, AI, security and platform settings.
</p>


</div>


</div>








<div class="panel" style="padding:25px;">






<form method="POST"
action="{{route('admin.config.update')}}"
enctype="multipart/form-data">


@csrf








{{-- GENERAL SETTINGS --}}



<h3>
⚙️ General Settings
</h3>





<label>

Platform Name


<input
class="input"
name="app_name"
value="{{$settings['app_name'] ?? 'Exam Marker'}}">


</label>






<br>






<label>

Site Title


<input
class="input"
name="site_title"
value="{{$settings['site_title'] ?? 'Exam Marker AI Paper Checker'}}">


</label>







<br>







<label>

Site Logo


<input
class="input"
type="file"
name="site_logo"
accept="image/*">


</label>






@if(!empty($settings['site_logo']))


<br>


<img
class="config-image"
src="{{Storage::url($settings['site_logo'])}}"
width="120">





<label class="remove-option">


<input
type="checkbox"
name="remove_logo"
value="1">


Remove Current Logo


</label>


@endif







<br>







<label>

Favicon


<input
class="input"
type="file"
name="favicon"
accept="image/*">


</label>






@if(!empty($settings['favicon']))


<br>


<img
class="config-image"
src="{{Storage::url($settings['favicon'])}}"
width="50">





<label class="remove-option">


<input
type="checkbox"
name="remove_favicon"
value="1">


Remove Current Favicon


</label>


@endif







<br>








<label>

Meta Description


<textarea
class="input"
name="meta_description"
rows="3">{{$settings['meta_description'] ?? ''}}</textarea>


</label>







<br>







<label>

Meta Keywords


<input
class="input"
name="meta_keywords"
value="{{$settings['meta_keywords'] ?? ''}}">


</label>







<br><br><br>









{{-- AI SETTINGS --}}




<h3>
🤖 AI Processing
</h3>






<label>

AI Processing



<select
class="input"
name="ai_enabled">


<option value="1"
@selected(($settings['ai_enabled'] ?? 1)==1)>

Enabled

</option>



<option value="0"
@selected(($settings['ai_enabled'] ?? 1)==0)>

Disabled

</option>



</select>


</label>






<br>






<label>

AI Confidence Threshold %


<input
class="input"
type="number"
name="ai_confidence"
value="{{$settings['ai_confidence'] ?? 85}}">


</label>








<br><br><br>









{{-- COMMUNICATION --}}





<h3>
📧 Communication
</h3>







<label>

Email Notifications



<select
class="input"
name="email_enabled">



<option value="1"
@selected(($settings['email_enabled'] ?? 1)==1)>

Enabled

</option>



<option value="0"
@selected(($settings['email_enabled'] ?? 1)==0)>

Disabled

</option>



</select>


</label>







<br>







<label>

SMS Notifications



<select
class="input"
name="sms_enabled">



<option value="1"
@selected(($settings['sms_enabled'] ?? 1)==1)>

Enabled

</option>




<option value="0"
@selected(($settings['sms_enabled'] ?? 1)==0)>

Disabled

</option>



</select>


</label>








<br><br><br>









{{-- SECURITY --}}





<h3>
🔒 Security / Maintenance
</h3>








<label>

Maintenance Mode





<select
class="input"
name="maintenance_mode">





<option value="0"
@selected(($settings['maintenance_mode'] ?? 0)==0)>

OFF

</option>





<option value="1"
@selected(($settings['maintenance_mode'] ?? 0)==1)>

ON

</option>





</select>





</label>









<br><br>









<button class="btn">


💾 Save Configuration


</button>







</form>






</div>






@endsection