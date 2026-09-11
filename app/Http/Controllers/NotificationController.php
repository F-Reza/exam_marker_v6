<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class NotificationController extends Controller {
 public function index(Request $request){return view('notifications.index',['notifications'=>$request->user()->notifications()->paginate(20)]);}
 public function read(Request $request){$request->user()->unreadNotifications->markAsRead();return back()->with('success','Notifications marked as read.');}
}
