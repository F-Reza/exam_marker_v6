<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SupportController extends Controller
{


public function index(Request $request)
{

$tab = $request->get('tab','inbox');


return view('admin.support.index',compact('tab'));

}



}