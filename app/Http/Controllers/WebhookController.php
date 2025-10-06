<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class WebhookController extends Controller
{
    protected WebhookService $webhookService;
    
    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }
    
    /**
     * Display webhooks index page
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $webhooks = Webhook::where('business_id', session('user.business_id'))
                             ->with('deliveries')
                             ->latest();
            
            return DataTables::of($webhooks)
                ->addColumn('events_count', function ($webhook) {
                    return count($webhook->events ?? []);
                })
                ->addColumn('health_status', function ($webhook) {
                    $health = $webhook->getHealthStatus();
                    $statusClass = match($health['status']) {
                        'healthy' => 'success',
                        'degraded' => 'warning', 
                        'failed' => 'danger',
                        'inactive' => 'default'
                    };
                    
                    return '<span class="label label-' . $statusClass . '">' . ucfirst($health['status']) . '</span>';
                })
                ->addColumn('success_rate', function ($webhook) {
                    $health = $webhook->getHealthStatus();
                    return $health['success_rate'] . '%';
                })
                ->addColumn('last_triggered', function ($webhook) {
                    return $webhook->last_triggered_at ? 
                           $webhook->last_triggered_at->diffForHumans() : 
                           'Never';
                })
                ->addColumn('action', function ($webhook) {
                    $actions = '<div class="btn-group">';
                    
                    $actions .= '<a href="' . route('webhooks.show', $webhook->id) . '" class="btn btn-xs btn-info">
                        <i class="fa fa-eye"></i> View
                    </a>';
                    
                    $actions .= '<a href="' . route('webhooks.edit', $webhook->id) . '" class="btn btn-xs btn-primary">
                        <i class="fa fa-edit"></i> Edit
                    </a>';
                    
                    $actions .= '<button type="button" class="btn btn-xs btn-warning test-webhook" data-id="' . $webhook->id . '">
                        <i class="fa fa-send"></i> Test
                    </button>';
                    
                    if ($webhook->is_active) {
                        $actions .= '<button type="button" class="btn btn-xs btn-default toggle-webhook" data-id="' . $webhook->id . '" data-action="disable">
                            <i class="fa fa-pause"></i> Disable
                        </button>';
                    } else {
                        $actions .= '<button type="button" class="btn btn-xs btn-success toggle-webhook" data-id="' . $webhook->id . '" data-action="enable">
                            <i class="fa fa-play"></i> Enable
                        </button>';
                    }
                    
                    $actions .= '<button type="button" class="btn btn-xs btn-danger delete-webhook" data-id="' . $webhook->id . '">
                        <i class="fa fa-trash"></i> Delete
                    </button>';
                    
                    $actions .= '</div>';
                    
                    return $actions;
                })
                ->rawColumns(['health_status', 'action'])
                ->make(true);
        }
        
        return view('webhooks.index');
    }
    
    /**
     * Show webhook creation form
     */
    public function create()
    {
        $availableEvents = Webhook::AVAILABLE_EVENTS;
        $eventDescriptions = $this->getEventDescriptions();
        return view('webhooks.create', compact('availableEvents', 'eventDescriptions'));
    }
    
    /**
     * Store new webhook
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => [
                'required',
                Rule::in(Webhook::AVAILABLE_EVENTS)
            ],
            'timeout' => 'integer|min:5|max:120',
            'max_retries' => 'integer|min:0|max:10',
            'retry_delay' => 'integer|min:10|max:3600'
        ]);
        
        $webhook = Webhook::create([
            'business_id' => session('user.business_id'),
            'name' => $request->name,
            'url' => $request->url,
            'events' => $request->events,
            'secret' => Webhook::generateSecret(),
            'is_active' => true,
            'timeout' => $request->timeout ?? 30,
            'max_retries' => $request->max_retries ?? 3,
            'retry_delay' => $request->retry_delay ?? 60,
            'metadata' => $request->metadata ?? []
        ]);
        
        return redirect()->route('webhooks.show', $webhook->id)
                        ->with('status', [
                            'success' => true,
                            'msg' => 'Webhook created successfully!'
                        ]);
    }
    
    /**
     * Show webhook details
     */
    public function show(Webhook $webhook, Request $request)
    {
        $this->authorize('view', $webhook);
        
        // Get webhook statistics
        $stats = $this->webhookService->getWebhookStats($webhook, 7);
        
        // Get recent deliveries
        if ($request->ajax() && $request->get('deliveries')) {
            $deliveries = $webhook->deliveries()
                                 ->latest()
                                 ->limit(100);
            
            return DataTables::of($deliveries)
                ->addColumn('status_badge', function ($delivery) {
                    $statusClass = match($delivery->status) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        'cancelled' => 'default'
                    };
                    
                    return '<span class="label label-' . $statusClass . '">' . ucfirst($delivery->status) . '</span>';
                })
                ->addColumn('response_time_formatted', function ($delivery) {
                    return $delivery->response_time ? round($delivery->response_time * 1000, 2) . ' ms' : '-';
                })
                ->addColumn('delivered_at_formatted', function ($delivery) {
                    return $delivery->delivered_at ? $delivery->delivered_at->format('M d, Y H:i:s') : '-';
                })
                ->addColumn('action', function ($delivery) {
                    return '<button type="button" class="btn btn-xs btn-info view-delivery" data-id="' . $delivery->id . '">
                        <i class="fa fa-eye"></i> View Details
                    </button>';
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }
        
        return view('webhooks.show', compact('webhook', 'stats'));
    }
    
    /**
     * Show webhook edit form
     */
    public function edit(Webhook $webhook)
    {
        $this->authorize('update', $webhook);
        
        $availableEvents = Webhook::AVAILABLE_EVENTS;
        $eventDescriptions = $this->getEventDescriptions();
        return view('webhooks.edit', compact('webhook', 'availableEvents', 'eventDescriptions'));
    }
    
    /**
     * Update webhook
     */
    public function update(Request $request, Webhook $webhook)
    {
        $this->authorize('update', $webhook);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:500',
            'events' => 'required|array|min:1',
            'events.*' => [
                'required',
                Rule::in(Webhook::AVAILABLE_EVENTS)
            ],
            'timeout' => 'integer|min:5|max:120',
            'max_retries' => 'integer|min:0|max:10',
            'retry_delay' => 'integer|min:10|max:3600'
        ]);
        
        $webhook->update([
            'name' => $request->name,
            'url' => $request->url,
            'events' => $request->events,
            'timeout' => $request->timeout ?? 30,
            'max_retries' => $request->max_retries ?? 3,
            'retry_delay' => $request->retry_delay ?? 60,
            'metadata' => $request->metadata ?? []
        ]);
        
        return redirect()->route('webhooks.show', $webhook->id)
                        ->with('status', [
                            'success' => true,
                            'msg' => 'Webhook updated successfully!'
                        ]);
    }
    
    /**
     * Delete webhook
     */
    public function destroy(Webhook $webhook)
    {
        $this->authorize('delete', $webhook);
        
        $webhook->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Webhook deleted successfully!'
        ]);
    }
    
    /**
     * Toggle webhook active status
     */
    public function toggle(Webhook $webhook, Request $request)
    {
        $this->authorize('update', $webhook);
        
        $action = $request->get('action');
        $isActive = $action === 'enable';
        
        $webhook->update(['is_active' => $isActive]);
        
        if ($isActive) {
            $webhook->resetFailureCount();
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Webhook ' . ($isActive ? 'enabled' : 'disabled') . ' successfully!',
            'status' => $isActive ? 'enabled' : 'disabled'
        ]);
    }
    
    /**
     * Test webhook endpoint
     */
    public function test(Webhook $webhook)
    {
        $this->authorize('view', $webhook);
        
        $result = $this->webhookService->testWebhook($webhook);
        
        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'Webhook test successful!' : 'Webhook test failed!',
            'data' => $result
        ]);
    }
    
    /**
     * Regenerate webhook secret
     */
    public function regenerateSecret(Webhook $webhook)
    {
        $this->authorize('update', $webhook);
        
        $newSecret = Webhook::generateSecret();
        $webhook->update(['secret' => $newSecret]);
        
        return response()->json([
            'success' => true,
            'message' => 'Webhook secret regenerated successfully!',
            'secret' => $newSecret
        ]);
    }
    
    /**
     * Get delivery details
     */
    public function deliveryDetails(WebhookDelivery $delivery)
    {
        $this->authorize('view', $delivery->webhook);
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $delivery->id,
                'event_type' => $delivery->event_type,
                'status' => $delivery->status,
                'attempt_number' => $delivery->attempt_number,
                'response_status' => $delivery->response_status,
                'response_time' => $delivery->response_time ? round($delivery->response_time * 1000, 2) : null,
                'scheduled_at' => $delivery->scheduled_at->toISOString(),
                'delivered_at' => $delivery->delivered_at?->toISOString(),
                'error_message' => $delivery->error_message,
                'payload' => $delivery->payload,
                'response_body' => $delivery->response_body
            ]
        ]);
    }
    
    /**
     * Get event descriptions for display
     */
    private function getEventDescriptions(): array
    {
        return [
            'product.created' => 'New product added',
            'product.updated' => 'Product information changed',
            'product.deleted' => 'Product removed',
            'product.stock_updated' => 'Stock levels changed',
            'contact.created' => 'New customer/supplier added',
            'contact.updated' => 'Contact information changed',
            'contact.deleted' => 'Contact removed',
            'transaction.created' => 'New transaction recorded',
            'transaction.updated' => 'Transaction modified',
            'transaction.deleted' => 'Transaction removed',
            'transaction.payment_added' => 'Payment received',
            'transaction.status_changed' => 'Transaction status changed',
            'sale.created' => 'New sale transaction',
            'sale.completed' => 'Sale finalized',
            'sale.cancelled' => 'Sale cancelled',
            'sale.refunded' => 'Sale refunded',
            'purchase.created' => 'New purchase recorded',
            'purchase.received' => 'Purchase goods received',
            'purchase.cancelled' => 'Purchase cancelled',
            'stock.low_alert' => 'Low stock warning triggered',
            'stock.adjustment' => 'Stock quantity adjusted',
            'stock.transfer' => 'Stock transferred between locations',
            'business.settings_updated' => 'Business settings changed',
            'business.location_created' => 'New location added',
            'business.location_updated' => 'Location information changed'
        ];
    }
}