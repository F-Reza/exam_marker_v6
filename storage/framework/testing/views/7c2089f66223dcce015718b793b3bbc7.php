<?php $__env->startSection('title','Payment Transactions'); ?>


<?php $__env->startSection('page-title','Payment Transactions'); ?>



<?php $__env->startSection('content'); ?>



<div class="welcome">

<div>

<h1>
💳 Payment Transactions
</h1>


<p>
Manage subscriptions, payments and transaction history.
</p>


</div>

</div>







<div class="panel table-panel">





<div class="panel-head">


<div>

<h2>
All Transactions
</h2>


<p>
Complete payment activity
</p>


</div>





<div>

<input

class="input"

id="transactionSearch"

name="search"

value="<?php echo e(request('search')); ?>"

placeholder="Search transaction ID, user..."

autocomplete="off"


>

</div>



</div>









<div class="table-wrap">


<table>


<thead>

<tr>

<th>
#
</th>

<th>
Transaction ID
</th>


<th>
User
</th>


<th>
Plan
</th>


<th>
Amount
</th>


<th>
Gateway
</th>


<th>
Status
</th>


<th>
Date
</th>


<th style="text-align: center;">
Action
</th>


</tr>


</thead>





<tbody id="transactionTable">


<?php echo $__env->make(
'admin.transactions.partials.table',
[
'transactions'=>$transactions
]
, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>


</tbody>



</table>



</div>







<div class="pagination"
id="transactionPagination">


<?php echo e($transactions->links()); ?>



</div>





</div>





<?php $__env->stopSection(); ?>







<?php $__env->startPush('scripts'); ?>


<script>


document.addEventListener(
"DOMContentLoaded",
function(){



let searchTimer;



const searchBox =
document.getElementById(
'transactionSearch'
);




if(!searchBox)
{
    return;
}






searchBox.addEventListener(
'keyup',
function(){



clearTimeout(searchTimer);




searchTimer=setTimeout(
()=>{



let keyword=this.value.trim();





fetch(
"<?php echo e(route('admin.transactions.search')); ?>?search="
+
encodeURIComponent(keyword),
{

headers:{

'X-Requested-With':'XMLHttpRequest',

'Accept':'text/html'

}

}

)



.then(response=>{


if(!response.ok)
{

throw new Error(
"Search request failed"
);

}


return response.text();


})



.then(html=>{



document.getElementById(
'transactionTable'
)
.innerHTML=html;




document.getElementById(
'transactionPagination'
)
.innerHTML='';



})



.catch(error=>{


console.error(
error
);


});





},
400
);




});




});


</script>


<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\My App\exam_marker_v6\resources\views/admin/transactions/index.blade.php ENDPATH**/ ?>