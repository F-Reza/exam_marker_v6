<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\SupportTicket;
class HelpController extends Controller {
 public function index(Request $request){$tickets=SupportTicket::where('user_id',$request->user()->id)->latest()->get();return view('help.index',compact('tickets'));}
 public function store(Request $request){$data=$request->validate(['subject'=>'required|max:180','category'=>'required|max:50','priority'=>'required|in:low,normal,high','message'=>'required|min:10']);$data['user_id']=$request->user()->id;SupportTicket::create($data);return back()->with('success','Support ticket created.');}
}
