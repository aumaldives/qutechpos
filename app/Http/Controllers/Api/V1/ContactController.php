<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\ContactResource;
use App\Contact;
use App\Utils\ContactUtil;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ContactController extends BaseApiController
{
    protected $contactUtil;

    public function __construct(ContactUtil $contactUtil)
    {
        $this->contactUtil = $contactUtil;
    }

    /**
     * Display a listing of contacts
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            $params = $this->getPaginationParams($request);
            
            $query = Contact::where('business_id', $business_id)
                ->with(['customer_group']);

            // Apply filters
            $searchableFields = ['name', 'contact_id', 'mobile', 'email', 'supplier_business_name'];
            $query = $this->applyFilters($query, $request, $searchableFields);

            // Contact type filter
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Active status filter
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Customer group filter
            if ($request->has('customer_group_id')) {
                $query->where('customer_group_id', $request->customer_group_id);
            }

            // City/State/Country filters
            if ($request->has('city')) {
                $query->where('city', 'LIKE', '%' . $request->city . '%');
            }

            if ($request->has('state')) {
                $query->where('state', 'LIKE', '%' . $request->state . '%');
            }

            if ($request->has('country')) {
                $query->where('country', 'LIKE', '%' . $request->country . '%');
            }

            $contacts = $query->paginate($params['per_page']);

            return $this->paginatedResponse($contacts, 'Contacts retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve contacts: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Store a newly created contact
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:customer,supplier,both',
                'name' => 'required|string|max:255',
                'supplier_business_name' => 'nullable|string|max:255',
                'contact_id' => 'nullable|string|max:255|unique:contacts,contact_id,NULL,id,business_id,' . $business_id,
                'email' => 'nullable|email|max:255',
                'mobile' => 'nullable|string|max:20',
                'landline' => 'nullable|string|max:20',
                'alternate_number' => 'nullable|string|max:20',
                'address_line_1' => 'nullable|string|max:255',
                'address_line_2' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'zip_code' => 'nullable|string|max:20',
                'shipping_address' => 'nullable|string|max:500',
                'tax_number' => 'nullable|string|max:255',
                'pay_term_number' => 'nullable|integer|min:0',
                'pay_term_type' => 'nullable|in:days,months',
                'credit_limit' => 'nullable|numeric|min:0',
                'opening_balance' => 'nullable|numeric',
                'customer_group_id' => 'nullable|integer|exists:customer_groups,id',
                'dob' => 'nullable|date',
                'is_active' => 'nullable|boolean',
                'enable_portal' => 'nullable|boolean',
                'custom_field1' => 'nullable|string|max:255',
                'custom_field2' => 'nullable|string|max:255',
                'custom_field3' => 'nullable|string|max:255',
                'custom_field4' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            DB::beginTransaction();

            // Prepare contact data
            $contactData = $validator->validated();
            $contactData['business_id'] = $business_id;
            $contactData['created_by'] = auth()->id() ?? 1; // Default to system user for API

            // Generate contact ID if not provided
            if (empty($contactData['contact_id'])) {
                $contactData['contact_id'] = $this->contactUtil->generateContactId($business_id);
            }

            // Handle opening balance
            $opening_balance = $contactData['opening_balance'] ?? 0;
            unset($contactData['opening_balance']);

            // Create the contact
            $contact = Contact::create($contactData);

            // Handle opening balance if provided
            if ($opening_balance != 0) {
                $this->contactUtil->addOpeningBalance(
                    $contact->id,
                    $opening_balance,
                    $business_id
                );
            }

            DB::commit();

            // Reload contact with relationships
            $contact->load(['customer_group']);

            return $this->resourceResponse(
                new ContactResource($contact),
                'Contact created successfully',
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to create contact: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified contact
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $query = Contact::where('business_id', $business_id)
                ->where('id', $id)
                ->with(['customer_group']);

            $contact = $query->first();

            if (!$contact) {
                return $this->errorResponse('Contact not found', 404);
            }

            return $this->resourceResponse(
                new ContactResource($contact),
                'Contact retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve contact: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified contact
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $contact = Contact::where('business_id', $business_id)->find($id);
            
            if (!$contact) {
                return $this->errorResponse('Contact not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'type' => 'sometimes|in:customer,supplier,both',
                'name' => 'sometimes|string|max:255',
                'supplier_business_name' => 'nullable|string|max:255',
                'contact_id' => 'sometimes|string|max:255|unique:contacts,contact_id,' . $id . ',id,business_id,' . $business_id,
                'email' => 'nullable|email|max:255',
                'mobile' => 'nullable|string|max:20',
                'landline' => 'nullable|string|max:20',
                'alternate_number' => 'nullable|string|max:20',
                'address_line_1' => 'nullable|string|max:255',
                'address_line_2' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'country' => 'nullable|string|max:100',
                'zip_code' => 'nullable|string|max:20',
                'shipping_address' => 'nullable|string|max:500',
                'tax_number' => 'nullable|string|max:255',
                'pay_term_number' => 'nullable|integer|min:0',
                'pay_term_type' => 'nullable|in:days,months',
                'credit_limit' => 'nullable|numeric|min:0',
                'customer_group_id' => 'nullable|integer|exists:customer_groups,id',
                'dob' => 'nullable|date',
                'is_active' => 'nullable|boolean',
                'enable_portal' => 'nullable|boolean',
                'custom_field1' => 'nullable|string|max:255',
                'custom_field2' => 'nullable|string|max:255',
                'custom_field3' => 'nullable|string|max:255',
                'custom_field4' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation failed', 422, $validator->errors()->toArray());
            }

            DB::beginTransaction();

            $contactData = $validator->validated();
            $contact->update($contactData);

            DB::commit();

            // Reload contact with relationships
            $contact->load(['customer_group']);

            return $this->resourceResponse(
                new ContactResource($contact),
                'Contact updated successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update contact: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified contact
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $contact = Contact::where('business_id', $business_id)->find($id);
            
            if (!$contact) {
                return $this->errorResponse('Contact not found', 404);
            }

            DB::beginTransaction();

            // Check if contact is used in any transactions
            $hasTransactions = DB::table('transactions')
                ->where('contact_id', $id)
                ->where('business_id', $business_id)
                ->exists();

            if ($hasTransactions) {
                return $this->errorResponse('Cannot delete contact that has been used in transactions. You can deactivate it instead.', 422);
            }

            // Delete the contact
            $contact->delete();

            DB::commit();

            return $this->successResponse(null, 'Contact deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to delete contact: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get contact transactions
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function transactions(Request $request, $id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $contact = Contact::where('business_id', $business_id)->find($id);
            
            if (!$contact) {
                return $this->errorResponse('Contact not found', 404);
            }

            $params = $this->getPaginationParams($request);
            
            $query = \App\Transaction::where('business_id', $business_id)
                ->where('contact_id', $id)
                ->with(['location'])
                ->when($request->get('type'), function ($q, $type) {
                    return $q->where('type', $type);
                })
                ->when($request->get('status'), function ($q, $status) {
                    return $q->where('status', $status);
                })
                ->orderBy('transaction_date', 'desc');

            $transactions = $query->paginate($params['per_page']);

            $transformedTransactions = $transactions->getCollection()->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'status' => $transaction->status,
                    'ref_no' => $transaction->ref_no,
                    'transaction_date' => $transaction->transaction_date,
                    'final_total' => (float) $transaction->final_total,
                    'payment_status' => $transaction->payment_status,
                    'location' => [
                        'id' => $transaction->location->id,
                        'name' => $transaction->location->name,
                    ],
                    'created_at' => $transaction->created_at?->toISOString(),
                ];
            });

            return $this->successResponse([
                'contact' => new ContactResource($contact),
                'transactions' => [
                    'data' => $transformedTransactions,
                    'meta' => [
                        'current_page' => $transactions->currentPage(),
                        'from' => $transactions->firstItem(),
                        'last_page' => $transactions->lastPage(),
                        'per_page' => $transactions->perPage(),
                        'to' => $transactions->lastItem(),
                        'total' => $transactions->total(),
                    ]
                ]
            ], 'Contact transactions retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve contact transactions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get contact balance information
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function balance($id): JsonResponse
    {
        try {
            $business_id = $this->getBusinessId();
            
            $contact = Contact::where('business_id', $business_id)->find($id);
            
            if (!$contact) {
                return $this->errorResponse('Contact not found', 404);
            }

            // Calculate balance using ContactUtil
            $balanceData = $this->contactUtil->getContactBalance($contact->id, $business_id);

            return $this->successResponse([
                'contact' => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'type' => $contact->type,
                ],
                'balance' => $balanceData,
                'summary' => [
                    'total_due' => $balanceData['total_purchase_due'] + $balanceData['total_sell_due'],
                    'net_balance' => $balanceData['total_sell_due'] - $balanceData['total_purchase_due'],
                    'last_transaction_date' => $balanceData['last_transaction_date'] ?? null,
                ]
            ], 'Contact balance retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve contact balance: ' . $e->getMessage(), 500);
        }
    }
}