<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;


class AdminAuditController extends Controller
{


public function index(Request $request)
{


$query = AuditLog::with('user')
->latest();



if($request->search){

$query->where('action','like','%'.$request->search.'%')
->orWhere('subject_type','like','%'.$request->search.'%');

}



$logs=$query->paginate(20);



return view(
'admin.audit.index',
compact('logs')
);


}

public function destroy(Request $request)
{

$request->validate([

'logs'=>'required|array'

]);


AuditLog::whereIn(
'id',
$request->logs
)->delete();



return back()->with(
'success',
'Audit logs deleted successfully.'
);


}



}