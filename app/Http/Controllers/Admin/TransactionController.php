<?php

namespace App\Http\Controllers\Admin;


use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;



class TransactionController extends Controller
{


    /*
    |--------------------------------------------------------------------------
    | Transaction List
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {


        $transactions = $this->transactionQuery($request)
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();



        return view(
            'admin.transactions.index',
            compact('transactions')
        );


    }







    /*
    |--------------------------------------------------------------------------
    | AJAX Search
    |--------------------------------------------------------------------------
    */

    public function search(Request $request)
    {


        $request->validate([

            'search'=>'nullable|string|max:100'

        ]);



        $transactions = $this->transactionQuery($request)
            ->latest('created_at')
            ->limit(20)
            ->get();




        return view(
            'admin.transactions.partials.table',
            compact('transactions')
        );


    }








    /*
    |--------------------------------------------------------------------------
    | Common Transaction Query
    |--------------------------------------------------------------------------
    */

    private function transactionQuery(Request $request)
    {


        $query = Transaction::with([

            'user',

            'plan'

        ]);





        if($request->filled('search'))
        {


            $search = trim($request->search);



            $query->where(function($q) use($search){



                // Transaction ID

                $q->where(

                    'transaction_id',

                    'like',

                    "%{$search}%"

                );





                // Reference (remove if column does not exist)

                $q->orWhere(

                    'reference',

                    'like',

                    "%{$search}%"

                );





                // Payment gateway

                $q->orWhere(

                    'gateway',

                    'like',

                    "%{$search}%"

                );





                // Payment status

                $q->orWhere(

                    'status',

                    'like',

                    "%{$search}%"

                );







                // User search

                $q->orWhereHas(

                    'user',

                    function($user) use($search){


                        $user->where(

                            'name',

                            'like',

                            "%{$search}%"

                        )

                        ->orWhere(

                            'email',

                            'like',

                            "%{$search}%"

                        );


                    }

                );




            });



        }





        return $query;


    }









    /*
    |--------------------------------------------------------------------------
    | Transaction Details
    |--------------------------------------------------------------------------
    */

    public function show(Transaction $transaction)
    {


        $transaction->load([

            'user',

            'plan'

        ]);




        return view(

            'admin.transactions.show',

            compact('transaction')

        );


    }



    /*
    |--------------------------------------------------------------------------
    | Delete Transaction
    |--------------------------------------------------------------------------
    */

    public function destroy(Transaction $transaction)
    {


        /*
        |--------------------------------------------------------------------------
        | Protect Successful Payments
        |--------------------------------------------------------------------------
        */

        if(
            strtolower($transaction->status) === 'success'
            ||
            strtolower($transaction->status) === 'completed'
            ||
            strtolower($transaction->status) === 'paid'
        )
        {


            return back()
                ->with(
                    'error',
                    'Successful payments cannot be deleted.'
                );


        }


        $transactionId = $transaction->transaction_id;



        $transaction->delete();


        return redirect()

            ->route('admin.transactions.index')

            ->with(
                'success',
                "Transaction {$transactionId} deleted successfully."
            );


    }




}