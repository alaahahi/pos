<?php

namespace App\Http\Controllers;

use App\Models\Box;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Http\Requests\StoreBoxRequest;
use App\Http\Requests\UpdateBoxRequest;
use App\Http\Controllers\AccountingController;
use App\Models\UserType;
use App\Models\User;
use App\Models\Transactions;
use Carbon\Carbon;
class BoxesController extends Controller
{
    public function __construct(AccountingController $accountingController)
    {
        $this->middleware('permission:read boxes', ['only' => ['index']]);
        $this->middleware('permission:create box', ['only' => ['create']]);
        $this->middleware('permission:update box', ['only' => ['update', 'edit']]);
        $this->middleware('permission:delete box', ['only' => ['destroy']]);
        $this->accountingController = $accountingController;
        $this->userAccount =  UserType::where('name', 'account')->first()?->id;
        $this->mainBox= User::with('wallet')->where('type_id', $this->userAccount)->where('email','mainBox@account.com');
        $this->defaultCurrency = env('DEFAULT_CURRENCY', 'IQD'); // مثلاً 'KWD' كخيار افتراضي
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Define the filters
        $filters = [
            'name' => $request->name,
            'phone' => $request->phone,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $request->is_active,
        ];

        // Start the Box query
        $transactionsQuery = transactions::with('morphed')->orderBy('created_at', 'desc');

        // Apply the filters if they exist
        $transactionsQuery->when($filters['name'], function ($query, $name) {
            return $query->where('name', 'LIKE', "%{$name}%");
        });

        $transactionsQuery->when($filters['phone'], function ($query, $phone) {
            return $query->where('phone', 'LIKE', "%{$phone}%");
        });

        $transactionsQuery->when($filters['start_date']??Carbon::now()->subDays(1), function ($query, $start_date) {
            return $query->where('created', '>=', $start_date);
        });

        $transactionsQuery->when($filters['end_date']??Carbon::now(), function ($query, $end_date) {
            return $query->where('created', '<=', $end_date);
        });

    
        // Paginate the filtered boxes
        $transactions = $transactionsQuery->paginate(10);

        return Inertia('Boxes/index', [
            'translations' => __('messages'),
            'filters' => $filters,
            'transactions' => $transactions,
            'mainBox' => $this->mainBox->first(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia('Boxes/Create', [
            'translations' => __('messages'),
        ]);
    }
    public function addToBox(Request $request)
    {
        $request->validate([
            'amountDollar' => 'nullable|numeric',
            'amountDinar' => 'nullable|numeric',
            'amountNote' => 'nullable|string',
        ]);
        if($request->amountDollar > 0){
            $transaction = $this->accountingController->increaseWallet(
                $request->amountDollar??0, // المبلغ المدفوع
                $request->amountNote ?? '', // الوصف
                $this->mainBox->first()->id, // الصندوق الرئيسي للعميل
                $this->mainBox->first()->id, // صندوق النظام أو المستخدم الأساسي
                'App\Models\User',
                0,
                0,
                '$',
                $request->date
            );
        }
        if($request->amountDinar > 0){
            $transaction = $this->accountingController->increaseWallet(
                $request->amountDinar??0, // المبلغ المدفوع
                $request->amountNote ?? '', // الوصف
                $this->mainBox->first()->id, // الصندوق الرئيسي للعميل
                $this->mainBox->first()->id, // صندوق النظام أو المستخدم الأساسي
                'App\Models\User',
                0,
                0,
                'IQD',
                $request->date
            );
        }
 
        
        return response()->json(['message' => 'Transaction added successfully']);
    }
    public function dropFromBox(Request $request)
    {
        $request->validate([
            'amountDollar' => 'nullable|numeric',
            'amountDinar' => 'nullable|numeric',
            'amountNote' => 'nullable|string',
        ]);
        if($request->amountDollar > 0){
            $transaction = $this->accountingController->decreaseWallet(
                $request->amountDollar??0, // المبلغ المدفوع
                $request->amountNote ?? '', // الوصف
                $this->mainBox->first()->id, // الصندوق الرئيسي للعميل
                $this->mainBox->first()->id, // صندوق النظام أو المستخدم الأساسي
                'App\Models\User',
                0,
                0,
                '$',
                $request->date
            );
        }
        if($request->amountDinar > 0){
            $transaction = $this->accountingController->decreaseWallet(
                $request->amountDinar??0, // المبلغ المدفوع
                $request->amountNote ?? '', // الوصف
                $this->mainBox->first()->id, // الصندوق الرئيسي للعميل
                $this->mainBox->first()->id, // صندوق النظام أو المستخدم الأساسي
                'App\Models\User',
                0,
                0,
                'IQD',
                $request->date
            );
        }
 
        
        return response()->json(['message' => 'Transaction dropped successfully']);
    }
    public function transactions(Request $request)
    {
        $filters = [
            'name' => $request->name,
            'phone' => $request->phone,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_active' => $request->is_active,
        ];

        // Start the Box query
        $transactionsQuery = transactions::with('TransactionsImages')->with('morphed')->orderBy('created_at', 'desc');

        // Apply the filters if they exist
        $transactionsQuery->when($filters['name'], function ($query, $name) {
            return $query->where('name', 'LIKE', "%{$name}%");
        });

        $transactionsQuery->when($filters['phone'], function ($query, $phone) {
            return $query->where('phone', 'LIKE', "%{$phone}%");
        });

        $transactionsQuery->when($filters['start_date']??Carbon::now()->subDays(1), function ($query, $start_date) {
            return $query->where('created', '>=', $start_date);
        });

        $transactionsQuery->when($filters['end_date']??Carbon::now(), function ($query, $end_date) {
            return $query->where('created', '<=', $end_date);
        });

    
        // Paginate the filtered boxes
        $transactions = $transactionsQuery->paginate(10);
        $transactions->appends(request()->query());
        return response()->json($transactions);
    }
}
