<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Transaction;
use App\TransactionPayment;
use App\Contact;
use App\Product;
use App\Variation;
use App\Utils\TransactionUtil;
use App\Utils\ProductUtil;
use App\Utils\BusinessUtil;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends BaseApiController
{
    protected $transactionUtil;
    protected $productUtil;
    protected $businessUtil;

    public function __construct(
        TransactionUtil $transactionUtil,
        ProductUtil $productUtil,
        BusinessUtil $businessUtil
    ) {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
    }

    /**
     * Get dashboard summary metrics
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $location_id = $request->get('location_id');
            
            // Date range (default to current month)
            $start_date = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
            $end_date = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

            // Build query with business and optional location filter
            $query = Transaction::where('business_id', $business_id)
                ->whereBetween('transaction_date', [$start_date, $end_date]);
                
            if ($location_id) {
                $query->where('location_id', $location_id);
            }

            // Sales metrics
            $sales = $query->clone()->where('type', 'sell')->where('status', 'final');
            $salesCount = $sales->count();
            $salesTotal = $sales->sum('final_total');

            // Purchase metrics  
            $purchases = $query->clone()->where('type', 'purchase')->where('status', 'received');
            $purchasesCount = $purchases->count();
            $purchasesTotal = $purchases->sum('final_total');

            // Expense metrics
            $expenses = $query->clone()->where('type', 'expense')->where('status', 'final');
            $expensesTotal = $expenses->sum('final_total');

            // Payment metrics
            $paymentsReceived = TransactionPayment::whereHas('transaction', function($q) use ($business_id, $location_id, $start_date, $end_date) {
                $q->where('business_id', $business_id)
                  ->where('type', 'sell')
                  ->whereBetween('transaction_date', [$start_date, $end_date]);
                if ($location_id) {
                    $q->where('location_id', $location_id);
                }
            })->whereBetween('paid_on', [$start_date, $end_date])->sum('amount');

            $paymentsPaid = TransactionPayment::whereHas('transaction', function($q) use ($business_id, $location_id, $start_date, $end_date) {
                $q->where('business_id', $business_id)
                  ->where('type', 'purchase')
                  ->whereBetween('transaction_date', [$start_date, $end_date]);
                if ($location_id) {
                    $q->where('location_id', $location_id);
                }
            })->whereBetween('paid_on', [$start_date, $end_date])->sum('amount');

            // Profit calculation (simplified)
            $grossProfit = $salesTotal - $purchasesTotal;
            $netProfit = $grossProfit - $expensesTotal;

            return $this->successResponse([
                'period' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                ],
                'sales' => [
                    'count' => $salesCount,
                    'total' => (float) $salesTotal,
                    'average_per_sale' => $salesCount > 0 ? (float) ($salesTotal / $salesCount) : 0,
                ],
                'purchases' => [
                    'count' => $purchasesCount,  
                    'total' => (float) $purchasesTotal,
                    'average_per_purchase' => $purchasesCount > 0 ? (float) ($purchasesTotal / $purchasesCount) : 0,
                ],
                'expenses' => [
                    'total' => (float) $expensesTotal,
                ],
                'payments' => [
                    'received' => (float) $paymentsReceived,
                    'paid' => (float) $paymentsPaid,
                    'net_cash_flow' => (float) ($paymentsReceived - $paymentsPaid),
                ],
                'profitability' => [
                    'gross_profit' => (float) $grossProfit,
                    'net_profit' => (float) $netProfit,
                    'gross_margin_percent' => $salesTotal > 0 ? round(($grossProfit / $salesTotal) * 100, 2) : 0,
                    'net_margin_percent' => $salesTotal > 0 ? round(($netProfit / $salesTotal) * 100, 2) : 0,
                ],
            ], 'Dashboard metrics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve dashboard metrics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get sales analytics report
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function salesAnalytics(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $location_id = $request->get('location_id');
            
            // Date range (default to current month)
            $start_date = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
            $end_date = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

            // Base query
            $query = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('status', 'final')
                ->whereBetween('transaction_date', [$start_date, $end_date]);
                
            if ($location_id) {
                $query->where('location_id', $location_id);
            }

            // Daily sales trend
            $dailySales = $query->clone()
                ->select(
                    DB::raw('DATE(transaction_date) as date'),
                    DB::raw('COUNT(*) as transaction_count'),
                    DB::raw('SUM(final_total) as total_sales'),
                    DB::raw('AVG(final_total) as average_sale')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Payment method breakdown
            $paymentMethods = TransactionPayment::whereHas('transaction', function($q) use ($business_id, $location_id, $start_date, $end_date) {
                $q->where('business_id', $business_id)
                  ->where('type', 'sell')
                  ->where('status', 'final')
                  ->whereBetween('transaction_date', [$start_date, $end_date]);
                if ($location_id) {
                    $q->where('location_id', $location_id);
                }
            })
            ->select('method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->get();

            // Top customers (by purchase amount)
            $topCustomers = Transaction::where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereBetween('transactions.transaction_date', [$start_date, $end_date])
                ->when($location_id, function($q) use ($location_id) {
                    return $q->where('transactions.location_id', $location_id);
                })
                ->join('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->where('contacts.business_id', $business_id)
                ->select(
                    'contacts.id',
                    'contacts.name',
                    'contacts.contact_id',
                    DB::raw('COUNT(transactions.id) as transaction_count'),
                    DB::raw('SUM(transactions.final_total) as total_purchases')
                )
                ->groupBy('contacts.id', 'contacts.name', 'contacts.contact_id')
                ->orderByDesc('total_purchases')
                ->limit(10)
                ->get();

            // Sales by location (if multiple locations)
            $locationSales = Transaction::where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->whereBetween('transactions.transaction_date', [$start_date, $end_date])
                ->join('business_locations', 'transactions.location_id', '=', 'business_locations.id')
                ->where('business_locations.business_id', $business_id)
                ->select(
                    'business_locations.id',
                    'business_locations.name',
                    DB::raw('COUNT(transactions.id) as transaction_count'),
                    DB::raw('SUM(transactions.final_total) as total_sales')
                )
                ->groupBy('business_locations.id', 'business_locations.name')
                ->orderByDesc('total_sales')
                ->get();

            return $this->successResponse([
                'period' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                ],
                'daily_trend' => $dailySales->map(function($day) {
                    return [
                        'date' => $day->date,
                        'transaction_count' => (int) $day->transaction_count,
                        'total_sales' => (float) $day->total_sales,
                        'average_sale' => (float) $day->average_sale,
                    ];
                }),
                'payment_methods' => $paymentMethods->map(function($method) {
                    return [
                        'method' => $method->method,
                        'transaction_count' => (int) $method->count,
                        'total_amount' => (float) $method->total,
                    ];
                }),
                'top_customers' => $topCustomers->map(function($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'contact_id' => $customer->contact_id,
                        'transaction_count' => (int) $customer->transaction_count,
                        'total_purchases' => (float) $customer->total_purchases,
                    ];
                }),
                'location_breakdown' => $locationSales->map(function($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'transaction_count' => (int) $location->transaction_count,
                        'total_sales' => (float) $location->total_sales,
                    ];
                }),
            ], 'Sales analytics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve sales analytics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get profit and loss report
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function profitLoss(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $location_id = $request->get('location_id');
            
            // Date range (default to current month)
            $start_date = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
            $end_date = $request->get('end_date', Carbon::now()->endOfMonth()->toDateString());

            $query = Transaction::where('business_id', $business_id)
                ->whereBetween('transaction_date', [$start_date, $end_date]);
                
            if ($location_id) {
                $query->where('location_id', $location_id);
            }

            // Revenue (Sales)
            $totalSales = $query->clone()->where('type', 'sell')->where('status', 'final')->sum('final_total');
            $salesReturns = $query->clone()->where('type', 'sell_return')->where('status', 'final')->sum('final_total');
            $netSales = $totalSales - $salesReturns;

            // Cost of Goods Sold (COGS)
            $totalPurchases = $query->clone()->where('type', 'purchase')->where('status', 'received')->sum('final_total');
            $purchaseReturns = $query->clone()->where('type', 'purchase_return')->where('status', 'received')->sum('final_total');
            $netPurchases = $totalPurchases - $purchaseReturns;

            // Expenses
            $totalExpenses = $query->clone()->where('type', 'expense')->where('status', 'final')->sum('final_total');

            // Calculations
            $grossProfit = $netSales - $netPurchases;
            $netProfit = $grossProfit - $totalExpenses;

            // Expense breakdown by category
            $expenseBreakdown = Transaction::where('transactions.business_id', $business_id)
                ->where('transactions.type', 'expense')
                ->where('transactions.status', 'final')
                ->whereBetween('transactions.transaction_date', [$start_date, $end_date])
                ->join('expense_categories', 'transactions.expense_category_id', '=', 'expense_categories.id')
                ->where('expense_categories.business_id', $business_id)
                ->select(
                    'expense_categories.name',
                    DB::raw('SUM(transactions.final_total) as total_amount')
                )
                ->groupBy('expense_categories.id', 'expense_categories.name')
                ->orderByDesc('total_amount')
                ->get();

            return $this->successResponse([
                'period' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                ],
                'revenue' => [
                    'total_sales' => (float) $totalSales,
                    'sales_returns' => (float) $salesReturns,
                    'net_sales' => (float) $netSales,
                ],
                'cost_of_goods_sold' => [
                    'total_purchases' => (float) $totalPurchases,
                    'purchase_returns' => (float) $purchaseReturns,
                    'net_purchases' => (float) $netPurchases,
                ],
                'expenses' => [
                    'total_expenses' => (float) $totalExpenses,
                    'breakdown' => $expenseBreakdown->map(function($expense) {
                        return [
                            'category' => $expense->name,
                            'amount' => (float) $expense->total_amount,
                        ];
                    }),
                ],
                'profitability' => [
                    'gross_profit' => (float) $grossProfit,
                    'net_profit' => (float) $netProfit,
                    'gross_margin_percent' => $netSales > 0 ? round(($grossProfit / $netSales) * 100, 2) : 0,
                    'net_margin_percent' => $netSales > 0 ? round(($netProfit / $netSales) * 100, 2) : 0,
                ],
            ], 'Profit & loss report retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve profit & loss report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get stock report
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stockReport(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $location_id = $request->get('location_id');
            
            // Build stock query
            $stockQuery = DB::table('variation_location_details as vld')
                ->join('variations as v', 'vld.variation_id', '=', 'v.id')
                ->join('products as p', 'v.product_id', '=', 'p.id')
                ->join('business_locations as bl', 'vld.location_id', '=', 'bl.id')
                ->where('p.business_id', $business_id)
                ->select(
                    'p.id as product_id',
                    'p.name as product_name',
                    'p.sku',
                    'v.id as variation_id',
                    'v.name as variation_name',
                    'v.sub_sku',
                    'bl.id as location_id',
                    'bl.name as location_name',
                    'vld.qty_available',
                    DB::raw('COALESCE(p.alert_quantity, 0) as alert_quantity')
                );

            if ($location_id) {
                $stockQuery->where('vld.location_id', $location_id);
            }

            // Get stock levels
            $stockLevels = $stockQuery->get();

            // Calculate summaries
            $totalProducts = $stockLevels->groupBy('product_id')->count();
            $totalVariations = $stockLevels->count();
            $lowStockCount = $stockLevels->where('qty_available', '<=', function($item) {
                return $item->alert_quantity;
            })->count();
            $outOfStockCount = $stockLevels->where('qty_available', '<=', 0)->count();

            // Top products by stock value (simplified calculation)
            $topProductsByStock = $stockLevels->groupBy('product_id')->map(function($variations, $productId) {
                $first = $variations->first();
                return [
                    'product_id' => $productId,
                    'product_name' => $first->product_name,
                    'sku' => $first->sku,
                    'total_quantity' => $variations->sum('qty_available'),
                    'variation_count' => $variations->count(),
                ];
            })->sortByDesc('total_quantity')->take(10)->values();

            // Low stock alerts
            $lowStockAlerts = $stockLevels->filter(function($item) {
                return $item->qty_available <= $item->alert_quantity && $item->alert_quantity > 0;
            })->map(function($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'variation_name' => $item->variation_name,
                    'sku' => $item->sku,
                    'sub_sku' => $item->sub_sku,
                    'location_name' => $item->location_name,
                    'current_stock' => (float) $item->qty_available,
                    'alert_quantity' => (float) $item->alert_quantity,
                ];
            })->values();

            return $this->successResponse([
                'summary' => [
                    'total_products' => $totalProducts,
                    'total_variations' => $totalVariations,
                    'low_stock_count' => $lowStockCount,
                    'out_of_stock_count' => $outOfStockCount,
                ],
                'top_products_by_stock' => $topProductsByStock,
                'low_stock_alerts' => $lowStockAlerts->take(20), // Limit to prevent large responses
            ], 'Stock report retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve stock report: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get trending products report
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trendingProducts(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $location_id = $request->get('location_id');
            
            // Date range (default to last 30 days)
            $start_date = $request->get('start_date', Carbon::now()->subDays(30)->toDateString());
            $end_date = $request->get('end_date', Carbon::now()->toDateString());

            // Build query for sold products
            $query = DB::table('transaction_sell_lines as tsl')
                ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
                ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
                ->join('products as p', 'v.product_id', '=', 'p.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->where('t.status', 'final')
                ->whereBetween('t.transaction_date', [$start_date, $end_date]);

            if ($location_id) {
                $query->where('t.location_id', $location_id);
            }

            // Top selling products by quantity
            $topByQuantity = $query->clone()
                ->select(
                    'p.id as product_id',
                    'p.name as product_name',
                    'p.sku',
                    DB::raw('SUM(tsl.quantity) as total_quantity'),
                    DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as total_revenue'),
                    DB::raw('COUNT(DISTINCT t.id) as transaction_count')
                )
                ->groupBy('p.id', 'p.name', 'p.sku')
                ->orderByDesc('total_quantity')
                ->limit(10)
                ->get();

            // Top selling products by revenue
            $topByRevenue = $query->clone()
                ->select(
                    'p.id as product_id',
                    'p.name as product_name',
                    'p.sku',
                    DB::raw('SUM(tsl.quantity) as total_quantity'),
                    DB::raw('SUM(tsl.quantity * tsl.unit_price_inc_tax) as total_revenue'),
                    DB::raw('COUNT(DISTINCT t.id) as transaction_count')
                )
                ->groupBy('p.id', 'p.name', 'p.sku')
                ->orderByDesc('total_revenue')
                ->limit(10)
                ->get();

            return $this->successResponse([
                'period' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                ],
                'top_by_quantity' => $topByQuantity->map(function($product) {
                    return [
                        'product_id' => $product->product_id,
                        'product_name' => $product->product_name,
                        'sku' => $product->sku,
                        'total_quantity' => (float) $product->total_quantity,
                        'total_revenue' => (float) $product->total_revenue,
                        'transaction_count' => (int) $product->transaction_count,
                        'average_price' => (float) ($product->total_quantity > 0 ? $product->total_revenue / $product->total_quantity : 0),
                    ];
                }),
                'top_by_revenue' => $topByRevenue->map(function($product) {
                    return [
                        'product_id' => $product->product_id,
                        'product_name' => $product->product_name,
                        'sku' => $product->sku,
                        'total_quantity' => (float) $product->total_quantity,
                        'total_revenue' => (float) $product->total_revenue,
                        'transaction_count' => (int) $product->transaction_count,
                        'average_price' => (float) ($product->total_quantity > 0 ? $product->total_revenue / $product->total_quantity : 0),
                    ];
                }),
            ], 'Trending products report retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve trending products: ' . $e->getMessage(), 500);
        }
    }
}