<?php

namespace App\Http\Controllers;

use App\Models\{User, Plan};
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;


class AdminUserController extends Controller
{


    protected AuditService $audit;



    public function __construct(AuditService $audit)
    {
        $this->audit = $audit;
    }






    /*
    |--------------------------------------------------------------------------
    | User List
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {


        $query = User::with('plan');



        if($request->filled('search'))
        {

            $search = $request->search;


            $query->where(function($q) use($search){

                $q->where('name','LIKE','%'.$search.'%')
                ->orWhere('email','LIKE','%'.$search.'%')
                ->orWhere('mobile','LIKE','%'.$search.'%');

            });


        }




        $users = $query

        // Platform Admin always last
        ->orderByRaw("
            CASE
                WHEN is_admin = 1
                OR user_type='platform_admin'
                OR role_title='platform_admin'
                THEN 1
                ELSE 0
            END ASC
        ")

        ->latest()

        ->paginate(25)

        ->withQueryString();




        return view(
            'admin.users.index',
            [
                'users'=>$users,
                'plans'=>Plan::all()
            ]
        );

    }









    /*
    |--------------------------------------------------------------------------
    | AJAX Search
    |--------------------------------------------------------------------------
    */

    public function search(Request $request)
    {


        $search = $request->search;



        $query = User::with('plan');



        if(!empty($search))
        {


            $query->where(function($q) use($search){


                $q->where('name','LIKE','%'.$search.'%')

                ->orWhere('email','LIKE','%'.$search.'%')

                ->orWhere('mobile','LIKE','%'.$search.'%');


            });


        }




        $users = $query

        // Keep Platform Admin at bottom
        ->orderByRaw("
            CASE
                WHEN is_admin = 1
                OR user_type='platform_admin'
                OR role_title='platform_admin'
                THEN 1
                ELSE 0
            END ASC
        ")

        ->latest()

        ->get();




        $plans = Plan::all();



        return view(
            'admin.users.partials.table',
            compact(
                'users',
                'plans'
            )
        );


    }









    /*
    |--------------------------------------------------------------------------
    | Create User
    |--------------------------------------------------------------------------
    */


    public function store(Request $r)
    {


        $d = $r->validate([


            'name'=>'required|string|max:255',


            'email'=>'required|email|unique:users,email',


            'mobile'=>'nullable|unique:users,mobile',


            'user_type'=>'required|in:student,teacher,coaching',


            'plan_id'=>'nullable|exists:plans,id',


            'password'=>[
                'required',
                Password::min(8)
            ]


        ]);





        // Normal user only
        $d['is_admin'] = false;



        $user = User::create($d);






        // Audit

        $this->audit->log(

            $user,

            'USER_CREATED',

            [

                'name'=>$user->name,

                'email'=>$user->email,

                'user_type'=>$user->user_type

            ]

        );





        return back()

        ->with(
            'success',
            'User account created successfully.'
        );


    }









    /*
    |--------------------------------------------------------------------------
    | Update User
    |--------------------------------------------------------------------------
    */


    public function update(Request $r, User $user)
    {



        // Protect Platform Admin

        if($user->isPlatformAdmin())
        {


            return back()

            ->with(
                'error',
                'Platform Admin account cannot be modified.'
            );


        }






        $d = $r->validate([


            'plan_id'=>'nullable|exists:plans,id',


            'account_status'=>'required|in:active,suspended',


            'is_admin'=>'nullable|boolean'


        ]);







        $oldData = [

            'plan_id'=>$user->plan_id,

            'account_status'=>$user->account_status,

            'is_admin'=>$user->is_admin

        ];






        $user->update([


            'plan_id'=>$d['plan_id'] ?? null,


            'account_status'=>$d['account_status'],


            'is_admin'=>$r->boolean('is_admin')


        ]);








        // Audit

        $this->audit->log(

            $user,

            'USER_UPDATED',

            [

                'before'=>$oldData,

                'after'=>[

                    'plan_id'=>$user->plan_id,

                    'account_status'=>$user->account_status,

                    'is_admin'=>$user->is_admin

                ]

            ]

        );







        return back()

        ->with(
            'success',
            'User account updated successfully.'
        );


    }



}