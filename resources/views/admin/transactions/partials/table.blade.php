@forelse($transactions as $transaction)


<tr>

<td>
<b>
{{ $transactions->firstItem() + $loop->index }}
</b>
</td>


<td>


{{$transaction->transaction_id ?? 'N/A'}}

</td>


<td>


<b>
{{$transaction->user?->name ?? 'Guest'}}

<small>
{{$transaction->user?->email ?? ''}}
</small>


</td>





<td>

{{$transaction->plan?->name ?? 'N/A'}}

</td>





<td>


{{$transaction->currency}}

{{number_format($transaction->amount,2)}}


</td>





<td>


{{ucfirst($transaction->gateway ?? 'Manual')}}


</td>





<td>


<span class="status status-{{strtolower($transaction->status)}}">

{{ucfirst($transaction->status)}}

</span>


</td>





<td>


{{$transaction->created_at?->format('d M Y')}}


</td>





<td>


<a

class="btn btn-sm btn-primary"

href="{{route('admin.transactions.show',$transaction->id)}}">

View

</a>





<form

method="POST"

action="{{route('admin.transactions.destroy',$transaction->id)}}"

style="display:inline;"

>


@csrf

@method('DELETE')



<button

type="submit"

class="btn btn-sm btn-danger"

onclick="return confirm('Are you sure you want to delete this transaction?')"

>

Delete

</button>


</form>



</td>



</tr>




@empty



<tr>

<td colspan="8" class="empty">


No transactions found.


</td>


</tr>


@endforelse