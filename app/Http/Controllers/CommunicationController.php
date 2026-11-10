<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\ParentCommunication;
class CommunicationController extends Controller {
 public function index(Request $request){
  $items=ParentCommunication::whereHas('assessment',fn($q)=>$q->whereIn('id',$request->user()->accessibleAssessments()->select('assessments.id')))->with(['assessment','student'])->latest()->paginate(20);
  return view('communications.index',compact('items'));
 }
}
