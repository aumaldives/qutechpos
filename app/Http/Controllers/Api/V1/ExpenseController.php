<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\ExpenseController as WebExpenseController;
use App\Transaction;
use App\ExpenseCategory;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends BaseApiController
{
    protected $businessUtil;
    protected $transactionUtil;
    protected $moduleUtil;
    protected $expenseController;

    public function __construct(
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        ModuleUtil $moduleUtil,
        WebExpenseController $expenseController
    ) {
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->expenseController = $expenseController;
    }

    /**
     * Get all expenses
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            $location_id = $request->get('location_id');
            $expense_for = $request->get('expense_for'); // user_id
            $category_id = $request->get('category_id');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');
            $per_page = $request->get('per_page', 25);

            $query = Transaction::where('business_id', $business_id)
                ->where('type', 'expense')
                ->with(['expense_category', 'location', 'expense_for_user', 'payment_lines']);

            if ($location_id) {
                $query->where('location_id', $location_id);
            }

            if ($expense_for) {
                $query->where('expense_for', $expense_for);
            }

            if ($category_id) {
                $query->where('expense_category_id', $category_id);
            }

            if ($start_date) {
                $query->whereDate('transaction_date', '>=', $start_date);
            }

            if ($end_date) {
                $query->whereDate('transaction_date', '<=', $end_date);
            }

            $expenses = $query->latest('transaction_date')
                ->paginate($per_page);

            // Transform the data
            $expenses->getCollection()->transform(function ($expense) {
                return [
                    'id' => $expense->id,
                    'transaction_date' => $expense->transaction_date,
                    'ref_no' => $expense->ref_no,
                    'expense_category' => $expense->expense_category ? [
                        'id' => $expense->expense_category->id,
                        'name' => $expense->expense_category->name
                    ] : null,
                    'location' => $expense->location ? [
                        'id' => $expense->location->id,
                        'name' => $expense->location->name
                    ] : null,
                    'expense_for' => $expense->expense_for_user ? [
                        'id' => $expense->expense_for_user->id,
                        'first_name' => $expense->expense_for_user->first_name,
                        'last_name' => $expense->expense_for_user->last_name
                    ] : null,
                    'final_total' => $expense->final_total,
                    'payment_status' => $expense->payment_status,
                    'additional_notes' => $expense->additional_notes,
                    'created_at' => $expense->created_at
                ];
            });

            return $this->sendSuccess('Expenses retrieved successfully', [
                'expenses' => $expenses->items(),
                'pagination' => [
                    'current_page' => $expenses->currentPage(),
                    'per_page' => $expenses->perPage(),
                    'total' => $expenses->total(),
                    'last_page' => $expenses->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve expenses', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Create a new expense
     */
    public function store(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'location_id' => 'required|integer',
                'expense_category_id' => 'required|integer',
                'transaction_date' => 'required|date',
                'final_total' => 'required|numeric|min:0.01',
                'payment' => 'required|array',
                'payment.method' => 'required|string',
                'payment.amount' => 'required|numeric|min:0'
            ]);

            // Use the existing expense controller logic
            $response = $this->expenseController->store($request);

            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                DB::commit();
                
                // Get the created expense ID from session
                $expense_id = session('expense_id') ?? $request->get('transaction_id');
                
                return $this->sendSuccess('Expense created successfully', [
                    'expense_id' => $expense_id
                ]);
            }

            DB::rollBack();
            return $this->sendError('Failed to create expense');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->sendError('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to create expense', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get specific expense
     */
    public function show($id): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            
            $expense = Transaction::where('business_id', $business_id)
                ->where('type', 'expense')
                ->where('id', $id)
                ->with([
                    'expense_category',
                    'location',
                    'expense_for_user',
                    'payment_lines.payment_account'
                ])
                ->first();

            if (!$expense) {
                return $this->sendError('Expense not found', [], 404);
            }

            $expense_data = [
                'id' => $expense->id,
                'transaction_date' => $expense->transaction_date,
                'ref_no' => $expense->ref_no,
                'expense_category' => $expense->expense_category ? [
                    'id' => $expense->expense_category->id,
                    'name' => $expense->expense_category->name,
                    'parent_id' => $expense->expense_category->parent_id
                ] : null,
                'location' => $expense->location ? [
                    'id' => $expense->location->id,
                    'name' => $expense->location->name
                ] : null,
                'expense_for' => $expense->expense_for_user ? [
                    'id' => $expense->expense_for_user->id,
                    'first_name' => $expense->expense_for_user->first_name,
                    'last_name' => $expense->expense_for_user->last_name,
                    'username' => $expense->expense_for_user->username
                ] : null,
                'final_total' => $expense->final_total,
                'payment_status' => $expense->payment_status,
                'payments' => $expense->payment_lines->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'method' => $payment->method,
                        'paid_on' => $payment->paid_on,
                        'account' => $payment->payment_account ? $payment->payment_account->name : null
                    ];
                }),
                'additional_notes' => $expense->additional_notes,
                'created_at' => $expense->created_at,
                'updated_at' => $expense->updated_at
            ];

            return $this->sendSuccess('Expense retrieved successfully', ['expense' => $expense_data]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve expense', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Update expense
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $business_id = auth()->user()->business_id;
            
            $expense = Transaction::where('business_id', $business_id)
                ->where('type', 'expense')
                ->where('id', $id)
                ->first();

            if (!$expense) {
                return $this->sendError('Expense not found', [], 404);
            }

            $request->validate([
                'location_id' => 'sometimes|integer',
                'expense_category_id' => 'sometimes|integer',
                'transaction_date' => 'sometimes|date',
                'final_total' => 'sometimes|numeric|min:0.01'
            ]);

            // Use existing expense controller update logic
            $response = $this->expenseController->update($request, $id);

            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                DB::commit();
                return $this->sendSuccess('Expense updated successfully');
            }

            DB::rollBack();
            return $this->sendError('Failed to update expense');

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return $this->sendError('Validation failed', $e->errors(), 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to update expense', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Delete expense
     */
    public function destroy($id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $business_id = auth()->user()->business_id;
            
            $expense = Transaction::where('business_id', $business_id)
                ->where('type', 'expense')
                ->where('id', $id)
                ->first();

            if (!$expense) {
                return $this->sendError('Expense not found', [], 404);
            }

            // Use existing expense controller delete logic
            $response = $this->expenseController->destroy($id);

            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                DB::commit();
                return $this->sendSuccess('Expense deleted successfully');
            }

            DB::rollBack();
            return $this->sendError('Failed to delete expense');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to delete expense', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get expense categories
     */
    public function categories(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            
            $categories = ExpenseCategory::where('business_id', $business_id)
                ->select('id', 'name', 'parent_id', 'category_type')
                ->get();

            // Organize into parent and sub categories
            $organized_categories = [];
            
            foreach ($categories as $category) {
                if (!$category->parent_id) {
                    $organized_categories[] = [
                        'id' => $category->id,
                        'name' => $category->name,
                        'category_type' => $category->category_type,
                        'sub_categories' => $categories->where('parent_id', $category->id)->map(function ($sub) {
                            return [
                                'id' => $sub->id,
                                'name' => $sub->name,
                                'category_type' => $sub->category_type
                            ];
                        })->values()
                    ];
                }
            }

            return $this->sendSuccess('Expense categories retrieved successfully', [
                'categories' => $organized_categories
            ]);

        } catch (\Exception $e) {
            return $this->sendError('Failed to retrieve expense categories', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get expense summary by category
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $business_id = auth()->user()->business_id;
            $start_date = $request->get('start_date', now()->startOfMonth());
            $end_date = $request->get('end_date', now()->endOfMonth());
            $location_id = $request->get('location_id');

            $query = Transaction::where('business_id', $business_id)
                ->where('type', 'expense')
                ->whereDate('transaction_date', '>=', $start_date)
                ->whereDate('transaction_date', '<=', $end_date)
                ->with('expense_category');

            if ($location_id) {
                $query->where('location_id', $location_id);
            }

            $expenses = $query->get();

            $summary = [
                'total_expenses' => $expenses->sum('final_total'),
                'total_count' => $expenses->count(),
                'by_category' => $expenses->groupBy('expense_category.name')->map(function ($group, $category) {
                    return [
                        'category' => $category ?? 'Uncategorized',
                        'amount' => $group->sum('final_total'),
                        'count' => $group->count()
                    ];
                })->values(),
                'by_location' => $expenses->groupBy('location.name')->map(function ($group, $location) {
                    return [
                        'location' => $location ?? 'No Location',
                        'amount' => $group->sum('final_total'),
                        'count' => $group->count()
                    ];
                })->values(),
                'period' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date
                ]
            ];

            return $this->sendSuccess('Expense summary retrieved successfully', $summary);

        } catch (\Exception $e) {
            return $this->sendError('Failed to get expense summary', ['error' => $e->getMessage()]);
        }
    }
}