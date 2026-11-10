<?php $__env->startSection('title','System Configuration'); ?>


<?php $__env->startSection('page-title','System Configuration'); ?>



<?php $__env->startPush('styles'); ?>

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

<?php $__env->stopPush(); ?>





<?php $__env->startSection('content'); ?>





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
action="<?php echo e(route('admin.config.update')); ?>"
enctype="multipart/form-data">


<?php echo csrf_field(); ?>












<h3>
⚙️ General Settings
</h3>





<label>

Platform Name


<input
class="input"
name="app_name"
value="<?php echo e($settings['app_name'] ?? 'Exam Marker'); ?>">


</label>






<br>






<label>

Site Title


<input
class="input"
name="site_title"
value="<?php echo e($settings['site_title'] ?? 'Exam Marker AI Paper Checker'); ?>">


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






<?php if(!empty($settings['site_logo'])): ?>


<br>


<img
class="config-image"
src="<?php echo e(Storage::url($settings['site_logo'])); ?>"
width="120">





<label class="remove-option">


<input
type="checkbox"
name="remove_logo"
value="1">


Remove Current Logo


</label>


<?php endif; ?>







<br>







<label>

Favicon


<input
class="input"
type="file"
name="favicon"
accept="image/*">


</label>






<?php if(!empty($settings['favicon'])): ?>


<br>


<img
class="config-image"
src="<?php echo e(Storage::url($settings['favicon'])); ?>"
width="50">





<label class="remove-option">


<input
type="checkbox"
name="remove_favicon"
value="1">


Remove Current Favicon


</label>


<?php endif; ?>







<br>








<label>

Meta Description


<textarea
class="input"
name="meta_description"
rows="3"><?php echo e($settings['meta_description'] ?? ''); ?></textarea>


</label>







<br>







<label>

Meta Keywords


<input
class="input"
name="meta_keywords"
value="<?php echo e($settings['meta_keywords'] ?? ''); ?>">


</label>







<br><br><br>














<h3>
🤖 AI Processing
</h3>






<label>

AI Processing



<select
class="input"
name="ai_enabled">


<option value="1"
<?php if(($settings['ai_enabled'] ?? 1)==1): echo 'selected'; endif; ?>>

Enabled

</option>



<option value="0"
<?php if(($settings['ai_enabled'] ?? 1)==0): echo 'selected'; endif; ?>>

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
value="<?php echo e($settings['ai_confidence'] ?? 85); ?>">


</label>








<br><br><br>















<h3>
📧 Communication
</h3>







<label>

Email Notifications



<select
class="input"
name="email_enabled">



<option value="1"
<?php if(($settings['email_enabled'] ?? 1)==1): echo 'selected'; endif; ?>>

Enabled

</option>



<option value="0"
<?php if(($settings['email_enabled'] ?? 1)==0): echo 'selected'; endif; ?>>

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
<?php if(($settings['sms_enabled'] ?? 1)==1): echo 'selected'; endif; ?>>

Enabled

</option>




<option value="0"
<?php if(($settings['sms_enabled'] ?? 1)==0): echo 'selected'; endif; ?>>

Disabled

</option>



</select>


</label>








<br><br><br>















<h3>
🔒 Security / Maintenance
</h3>








<label>

Maintenance Mode





<select
class="input"
name="maintenance_mode">





<option value="0"
<?php if(($settings['maintenance_mode'] ?? 0)==0): echo 'selected'; endif; ?>>

OFF

</option>





<option value="1"
<?php if(($settings['maintenance_mode'] ?? 0)==1): echo 'selected'; endif; ?>>

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






<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/nextdigit/educatalyst.nextdigit.dev/resources/views/admin/config/index.blade.php ENDPATH**/ ?>