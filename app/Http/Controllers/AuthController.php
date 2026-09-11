<?php
namespace App\Http\Controllers;
use App\Models\{User,Plan};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
class AuthController extends Controller {
 public function showRegister(){ return view('auth.register'); }
 public function showLogin(){ return view('auth.login'); }
 public function register(Request $request){
   $data=$request->validate(['name'=>'required|string|max:100','email'=>'required|email|unique:users','mobile'=>'required|string|max:20|unique:users','user_type'=>'required|in:student,teacher,coaching','password'=>['required','confirmed',Password::min(8)]]);
   $data['plan_id']=Plan::where('slug','free')->value('id');
   $user=User::create($data); Auth::login($user); $request->session()->regenerate();
   return redirect()->route('dashboard')->with('success','Welcome to Exam Marker. Your free paper check is ready.');
 }
 public function login(Request $request){ $credentials=$request->validate(['email'=>'required|email','password'=>'required']); if(Auth::attempt($credentials,$request->boolean('remember'))){ if(auth()->user()->account_status==='suspended'){ Auth::logout(); return back()->withErrors(['email'=>'This account is suspended. Contact support.']); } $request->session()->regenerate();return redirect()->intended(route('dashboard'));} return back()->withErrors(['email'=>'Invalid login details.'])->onlyInput('email'); }
 public function logout(Request $request){ Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect('/'); }
}
