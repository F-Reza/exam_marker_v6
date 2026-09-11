@extends('layouts.app')
@section('title','Parent Communication') @section('page-title','Parent Communication')
@section('content')
<div class="welcome">
    <div><span class="eyebrow"> Notify the Parent</span>
        <h1>Parent Communication</h1>
        <p>Track every result sent to a parent.</p>
    </div>
</div>
<div class="panel table-panel">
    <div class="panel-head">
        <h2>Delivery log</h2><span class="badge">{{$items->total()}} messages</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Assessment</th>
                    <th>Recipient</th>
                    <th>Channel</th>
                    <th>Status</th>
                    <th>Sent</th>
                </tr>
            </thead>
            <tbody>@forelse($items as $item)<tr>
                    <td>{{$item->student?->name??'—'}}</td>
                    <td><a href="{{route('results.show',$item->assessment)}}"><b>{{$item->assessment->title}}</b></a>
                    </td>
                    <td>{{$item->recipient}}</td>
                    <td>{{strtoupper($item->channel)}}</td>
                    <td><span class="badge {{$item->status==='sent'?'green':''}}">{{ucfirst($item->status)}}</span></td>
                    <td>{{$item->sent_at?->format('d M Y, h:i A')??'Pending'}}</td>
                </tr>@empty<tr>
                    <td colspan="6" class="empty">No parent messages have been sent.</td>
                </tr>@endforelse</tbody>
        </table>
    </div>
    <div class="pagination">{{$items->links()}}</div>
</div>
@endsection