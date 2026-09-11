<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class SettingsController extends Controller {
 public function index(Request $request){return view('settings.index',['user'=>$request->user()]);}
 public function updateProfile(Request $request){
  $data=$request->validate(['name'=>'required|string|max:120','mobile'=>'required|string|max:30|unique:users,mobile,'.$request->user()->id,'organisation_name'=>'nullable|string|max:180']);
  $request->user()->update($data); return back()->with('success','Profile updated.');
 }
 public function updatePassword(Request $request){
  $data=$request->validate(['current_password'=>'required|current_password','password'=>'required|min:8|confirmed']);
  $request->user()->update(['password'=>Hash::make($data['password'])]); return back()->with('success','Password changed.');
 }
 public function updateNotifications(Request $request){
  $request->user()->update(['notification_preferences'=>[
   'processing'=>(bool)$request->boolean('processing'),'reports'=>(bool)$request->boolean('reports'),'parent_delivery'=>(bool)$request->boolean('parent_delivery'),'billing'=>(bool)$request->boolean('billing')
  ]]); return back()->with('success','Notification preferences saved.');
 }
}
