<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class ReportController extends Controller {
 public function index(Request $request){
  $reports=$request->user()->assessments()->with('student')->whereIn('status',['reviewed','finalised','reported','sent_to_parent'])->latest()->paginate(12);
  return view('reports.index',compact('reports'));
 }
}
