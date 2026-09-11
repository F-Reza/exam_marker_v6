@extends('layouts.app')


@section('title','Payment Transactions')


@section('page-title','Payment Transactions')



@section('content')



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

value="{{request('search')}}"

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


@include(
'admin.transactions.partials.table',
[
'transactions'=>$transactions
]
)


</tbody>



</table>



</div>







<div class="pagination"
id="transactionPagination">


{{$transactions->links()}}


</div>





</div>





@endsection







@push('scripts')


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
"{{route('admin.transactions.search')}}?search="
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


@endpush