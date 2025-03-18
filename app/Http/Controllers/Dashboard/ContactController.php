<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Imports\ContactsImport;
use App\Models\Account;
use App\Models\ActivityType;
use App\Models\City;
use App\Models\Contact;
use App\Models\Governorate;
use App\Models\SalesSegment;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Village;
use App\Notifications\ContactNotification;
use App\Services\ActivityLogsService;
use App\Services\PaymentTransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class ContactController extends Controller
{
    public $activityLogsService;

    public function __construct(ActivityLogsService $activityLogsService)
    {
        $this->middleware(function ($request, $next) {
            $type = $request->type ?? $request->route('contact.type') ?? 'customer';
            $permissionPrefix = $type === 'supplier' ? 'suppliers' : 'customers';

            // Basic CRUD Operations
            if ($request->routeIs('dashboard.contacts.index')) {
                $this->middleware("permissionMiddleware:read-{$permissionPrefix}")->only('index');
            }

            if ($request->routeIs('dashboard.contacts.create') || $request->routeIs('dashboard.contacts.store')) {
                $this->middleware("permissionMiddleware:create-{$permissionPrefix}")->only(['create', 'store']);
            }

            if ($request->routeIs('dashboard.contacts.edit') || $request->routeIs('dashboard.contacts.update')) {
                $this->middleware("permissionMiddleware:update-{$permissionPrefix}")->only(['edit', 'update']);
            }

            if ($request->routeIs('dashboard.contacts.destroy')) {
                $this->middleware("permissionMiddleware:delete-{$permissionPrefix}")->only('destroy');
            }

            if ($request->routeIs('dashboard.contacts.pay')) {
                $this->middleware("permissionMiddleware:pay-{$permissionPrefix}")->only('pay');
            }

            if ($request->routeIs('dashboard.contacts.payment-history')) {
                $this->middleware("permissionMiddleware:view-payment-history-{$permissionPrefix}")->only('paymentHistory');
            }

            if ($request->routeIs('dashboard.contacts.import')) {
                $this->middleware("permissionMiddleware:import-{$permissionPrefix}")->only(['import', 'processImport']);
            }

            if ($request->routeIs('dashboard.contacts.showContact')) {
                $this->middleware("permissionMiddleware:read-{$permissionPrefix}")->only('showContact');
            }
            if ($request->routeIs('dashboard.contacts.export')) {
                $this->middleware("permissionMiddleware:read-{$permissionPrefix}")->only('export');
            }

            return $next($request);
        });

        // Set activity logs service
        $this->activityLogsService = $activityLogsService;
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Contact::query()->orderBy('id', 'desc');

            if ($request->type)
                $data->where('type', $request->type);
            if ($request->sales_segment_id)
                $data->where('sales_segment_id', $request->sales_segment_id);

            return DataTables::of($data)
                ->addColumn('code', function ($row) {
                    return $row->id;
                })
                ->addColumn('action', function ($row) {
                    $type = $row->type === 'supplier' ? 'suppliers' : 'customers';

                    $btn = '<div class="btn-group"><button type="button" class="btn btn-success">' . trans('admin.Actions') . '</button><button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown"></button><div class="dropdown-menu" role="menu">';

                    // my menu
                    if (auth('user')->user()->has_permission("read-{$type}"))
                        $btn .= '<a class="dropdown-item fire-popup" data-url="' . route('dashboard.contacts.showContact', $row->id) . '"
                                href="#" data-toggle="modal" data-target="#modal-default-big">' . trans('admin.Show') . '</a>';
                    if (auth('user')->user()->has_permission("update-{$type}"))
                        $btn .= '<a class="dropdown-item" href="' . route('dashboard.contacts.edit', $row->id) . '">' . trans('admin.Edit') . '</a>';

                    if (auth('user')->user()->has_permission("delete-{$type}"))
                        $btn .= '<a class="dropdown-item delete-popup" href="#" data-toggle="modal" data-target="#modal-default" data-url="' . route('dashboard.contacts.destroy', $row->id) . '">' . trans('admin.Delete') . '</a>';

                    // Add pay option
                    if (auth('user')->user()->has_permission("pay-{$type}"))
                        $btn .= '<a class="dropdown-item" href="' . route('dashboard.contacts.pay', $row->id) . '">' . trans('admin.Pay') . '</a>';

                    // Add payment history option
                    if (auth('user')->user()->has_permission("view-payment-history-{$type}"))
                        $btn .= '<a class="dropdown-item" href="' . route('dashboard.contacts.payment-history', ['id' => $row->id, 'type' => $row->type]) . '">' . trans('admin.payment_history') . '</a>';

                    $btn .= '</div></div>';
                    return $btn;
                })
                ->addColumn('type', function ($row) {
                    return trans('admin.' . $row->type);
                })
                ->addColumn('sales_segment_id', function ($row) {
                    return $row->salesSegment->name ?? 'لا يوجد ';
                })
                ->addColumn('route', function ($row) {
                    return route('dashboard.contacts.showContact', $row->id);
                })
                ->addColumn('is_active', function ($row) {
                    if ($row->is_active == 1)
                        return trans('admin.yes');

                    return trans('admin.no');
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        $salesSegments = SalesSegment::all();
        return view('Dashboard.contacts.index', compact('salesSegments'));
    }

    public function create()
    {
        $settings = Setting::first();
        $activityTypes = ActivityType::all();
        $salesSegments = SalesSegment::all();
        $governorates = Governorate::get();
        $villages = Village::all();
        $cities = [];
        $users = User::all();
        $usersIds = [];
        return view('Dashboard.contacts.create', compact('salesSegments', 'settings', 'governorates', 'villages', 'cities', 'activityTypes', 'users', 'usersIds'));
    }

    public function store(Request $request)
    {
        try {
            // Validation rules
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'type' => 'required|in:supplier,customer',
                'address' => 'required|string|max:255',
                // 'activity_type_id' => 'nullable|exists:activity_types,id',
                'credit_limit' => 'nullable|numeric',
                'opening_balance' => 'nullable|numeric',
                // 'sales_segment_id' => 'nullable|exists:sales_segments,id',
                'governorate_id' => 'required|exists:governorates,id',
                'city_id' => 'required|exists:cities,id',
                'village_id' => 'nullable|exists:villages,id',
                // 'user_ids' => 'nullable|exists:users,id'
            ]);

            $request->merge(['is_active' => $request->get('is_active', 0)]);

            $contact = Contact::create([
                'name' => $validatedData['name'],
                'phone' => $validatedData['phone'],
                'address' => $validatedData['address'],
                // 'activity_type_id' => $validatedData['activity_type_id'],
                'type' => $validatedData['type'],
                'credit_limit' => $validatedData['credit_limit'],
                // 'sales_segment_id' => $validatedData['sales_segment_id'],
                'governorate_id' => $validatedData['governorate_id'],
                'city_id' => $validatedData['city_id'],
                'village_id' => $validatedData['village_id'],
                'is_active' => $request->is_active,
                'opening_balance' => $validatedData['opening_balance'] ?? 0,
                'balance' => $validatedData['opening_balance'] ?? 0,
            ]);

            // $contact->users()->attach($request->user_ids);

            if ($request->opening_balance != 0) {
                $status = '';
                if ($validatedData['type'] == 'customer') {
                    $status = $request->opening_balance < 0 ? 'final' : 'due';
                } else if ($validatedData['type'] == 'supplier') {
                    $status = $request->opening_balance > 0 ? 'final' : 'due';
                }

                Transaction::create([
                    'type' => 'opening_balance',
                    'status' => 'final',
                    'transaction_date' => now(),
                    'created_by' => auth()->id(),
                    'contact_id' => $contact->id,
                    'final_price' => $request->opening_balance,
                    'payment_status' => $status
                ]);
            }

            $type = $contact->type == 'customer' ? 'عميل' : 'مورد';

            // Send notification
            Notification::send(auth()->user(), new ContactNotification(
                $contact,
                'create',
                'تم اضافة جهة اتصال من نوع ' . $type . ' باسم ' . $contact->name . 'بواسطة ' . auth()->user()->name . ' في تاريخ ' . \Carbon\Carbon::parse($contact->created_at)->format('Y-m-d')
            ));

            // Log activity
            $this->activityLogsService->insert([
                'subject' => $contact,
                'title' => 'Contact created : ' . $contact->type . ' - ' . $contact->name,
                'description' => 'تم إنشاء جهة اتصال من نوع ' . $contact->type . ' باسم ' . $contact->name
                    . (isset($contact->credit_limit) && isset($contact->sales_segment_id)
                        ? ' وبحد ائتمان ' . $contact->credit_limit . 'وتم اضافته الي شريحة' . $contact->salesSegment->name
                        : ''),
                'proccess_type' => $contact->type == 'customer' ? 'customers' : 'suppliers',
                'user_id' => auth()->id(),
            ]);

            // Check if request is AJAX
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => trans('admin.Contact added successfully'),
                    'contact' => $contact->fresh(['salesSegment'])  // Load fresh instance with relationship
                ]);
            }

            return redirect('dashboard/contacts')->with('success', 'success');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error occurred while saving contact',
                    'errors' => [$e->getMessage()]
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function edit($id)
    {
        $activityTypes = ActivityType::all();
        $settings = Setting::first();
        $contact = Contact::findOrFail($id);
        $salesSegments = SalesSegment::all();
        $governorates = Governorate::get();
        $villages = Village::all();
        $settings = Setting::first();
        $users = User::all();
        $cities = [];
        $cities = City::all();
        return view('Dashboard.contacts.edit')->with([
            'data' => $contact,
            'salesSegments' => $salesSegments,
            'governorates' => $governorates,
            'cities' => $cities,
            'villages' => $villages,
            'settings' => $settings,
            'activityTypes' => $activityTypes,
            'users' => $users,
            'usersIds' => $contact->users()->pluck('user_id')->toArray()
        ]);
    }

    public function update($id, Request $request)
    {
        $contact = Contact::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:supplier,customer',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'activity_type_id' => 'nullable|exists:activity_types,id',
            'credit_limit' => 'nullable|numeric',
            'sales_segment_id' => 'nullable|exists:sales_segments,id',
            'governorate_id' => 'required|exists:governorates,id',
            'city_id' => 'required|exists:cities,id',
            'village_id' => 'nullable|exists:villages,id',
            'opening_balance' => 'nullable|numeric',
        ], [
            'name.required' => 'يجب أن يكون اسم العميل أو المورد موجود',
            'type.required' => 'يجب أن يكون نوع العميل أو المورد موجود',
            'phone.required' => 'يجب أن يكون رقم الهاتف موجود',
            'address.required' => 'يجب أن يكون العنوان موجود',
            'governorate_id.required' => 'يجب أن يكون المحافظة موجودة',
            'city_id.required' => 'يجب أن يكون المدينة موجودة',
            'opening_balance.numeric' => 'يجب أن يكون الرصيد الافتتاحي للعميل أو المورد رقم',
            'opening_balance.required' => 'يجب أن يكون الرصيد الافتتاحي للعميل أو المورد رقم',
        ]);
        $request->merge(['is_active' => $request->get('is_active', 0)]);
        $validatedData['is_active'] = $request->is_active;
        $contact->update($validatedData);
        $contact->users()->sync($request->user_ids);
        $contact->save();
        $type = $contact->type == 'customer' ? 'عميل' : 'مورد';
        Notification::send(auth()->user(), new ContactNotification($contact, 'update', 'تم تعديل جهة اتصال من نوع ' . $type . ' باسم ' . $contact->name . 'بواسطة ' . auth()->user()->name . ' في تاريخ ' . \Carbon\Carbon::parse($contact->updated_at)->format('Y-m-d')));
        $this->activityLogsService->insert([
            'subject' => $contact,
            'title' => 'Contact updated : ' . $contact->type . ' - ' . $contact->name,
            'description' => 'تم تعديل جهة اتصال من نوع ' . $contact->type . ' باسم ' . $contact->name . (isset($contact->credit_limit) && isset($contact->sales_segment_id) ? ' وبحد ائتمان ' . $contact->credit_limit . 'وتم اضافته الي شريحة' . $contact->salesSegment->name : ''),
            'proccess_type' => $contact->type == 'customer' ? 'customers' : 'suppliers',
            'user_id' => auth()->id(),
        ]);
        return redirect('dashboard/contacts')->with('success', 'success');
    }

    public function destroy($contact_id)
    {
        $contact = Contact::findOrFail($contact_id);
        $contact->delete();
        $type = $contact->type == 'customer' ? 'عميل' : 'مورد';
        Notification::send(auth()->user(), new ContactNotification($contact, 'delete', 'تم حذف جهة اتصال من نوع ' . $type . ' باسم ' . $contact->name . 'بواسطة ' . auth()->user()->name . ' في تاريخ ' . \Carbon\Carbon::parse($contact->deleted_at)->format('Y-m-d')));
        $this->activityLogsService->insert([
            'subject' => $contact,
            'title' => 'Contact deleted : ' . $contact->type . ' - ' . $contact->name,
            'description' => 'تم حذف جهة اتصال من نوع ' . $contact->type . ' باسم ' . $contact->name,
            'proccess_type' => $contact->type == 'customer' ? 'customers' : 'suppliers',
            'user_id' => auth()->id(),
        ]);
        return redirect()->back()->with('success', trans('admin.success'));
    }

    public function ContctCreditLimit(Request $request)
    {
        $contact = Contact::find($request->contact_id);
        return $contact->credit_limit ?? 0;
    }

    public function importContactsView()
    {
        return view('Dashboard.contacts.importContactsView');
    }

    public function importContacts(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'excel' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            DB::beginTransaction();

            // Read the Excel file
            $data = Excel::toArray([], $request->file('excel'))[0];

            // Remove header row if exists
            if (isset($data[0]) && $data[0][0] === 'code') {
                array_shift($data);
            }

            $successCount = 0;
            $errors = [];

            foreach ($data as $index => $row) {
                // Validate each row
                $rowValidator = Validator::make([
                    'code' => $row[0] ?? null,
                    'name' => $row[1] ?? null,
                    'type' => $row[2] ?? null,
                    'phone' => $row[3] ?? null,
                    'address' => $row[4] ?? null,
                    'credit_limit' => $row[5] ?? null,
                    'government' => $row[6] ?? null,
                    'city' => $row[7] ?? null,
                    'opening_balance' => $row[8] ?? 0,
                ], [
                    'code' => 'nullable|unique:contacts,code',
                    'name' => 'required|max:255',
                    'type' => 'required|in:supplier,customer',
                    'phone' => 'nullable|max:255',
                    'address' => 'nullable|string',
                    'credit_limit' => 'nullable|numeric',
                    'government' => 'nullable|string|max:255',
                    'city' => 'nullable|string|max:255',
                    'opening_balance' => 'required|numeric',
                ]);

                if ($rowValidator->fails()) {
                    $errors[] = 'الصف ' . ($index + 2) . ': ' . implode(', ', $rowValidator->errors()->all());
                    continue;
                }

                // Find government and city
                $government = Governorate::where('governorate_name_ar', 'LIKE', '%' . $row[6] . '%')->first();
                $city = City::where('city_name_ar', 'LIKE', '%' . $row[7] . '%')->first();

                // Validate government and city existence
                if (!$government) {
                    $errors[] = 'الصف ' . ($index + 2) . ': المحافظة غير موجودة: ' . ($row[6] ?? 'فارغ');
                    continue;
                }

                if (!$city) {
                    $errors[] = 'الصف ' . ($index + 2) . ': المدينة غير موجودة: ' . ($row[7] ?? 'فارغ');
                    continue;
                }
                $code = $row[0];
                if (!$row[0]) {
                    $newCotnact = new Contact();

                    $code = $newCotnact->generateNewCode($code);
                }

                try {
                    $contact = Contact::create([
                        'code' => $code,
                        'name' => $row[1],
                        'type' => $row[2],
                        'phone' => $row[3],
                        'address' => $row[4],
                        'credit_limit' => $row[5],
                        'governorate_id' => $government->id,
                        'city_id' => $city->id,
                        'opening_balance' => $row[8] ?? 0,
                        'balance' => $row[8] ?? 0,  // Set initial balance to 0
                        'is_active' => 1,
                    ]);

                    if ($row[8] != 0) {
                        $status = '';
                        if ($row[2] == 'customer') {
                            $status = $row[8] < 0 ? 'final' : 'due';
                        } else if ($row[2] == 'supplier') {
                            $status = $row[8] > 0 ? 'final' : 'due';
                        }

                        $transaction = Transaction::create([
                            'type' => 'opening_balance',
                            'status' => 'final',
                            'transaction_date' => now(),
                            'created_by' => auth()->id(),
                            'contact_id' => $contact->id,
                            'final_price' => $row[8],
                            'payment_status' => $status
                        ]);
                    }

                    $successCount++;
                } catch (\Exception $e) {
                    $errors[] = 'الصف ' . ($index + 2) . ': خطأ في إنشاء جهة الاتصال: ' . $e->getMessage();
                    continue;
                }
            }

            // If we have any successful imports, commit the transaction
            if ($successCount > 0) {
                DB::commit();
            } else {
                DB::rollBack();
            }

            // Prepare response message
            $message = 'تم استيراد ' . $successCount . ' جهة اتصال.';
            if (!empty($errors)) {
                $message .= " ولكن هناك بعض الاخطاء:\n" . implode("\n", $errors);
                return redirect()
                    ->back()
                    ->with('warning', $message)
                    ->withErrors($errors);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء الاستيراد: ' . $e->getMessage());
        }
    }

    public function showContact($id)
    {
        $contact = Contact::findOrFail($id);
        return [
            'title' => trans('admin.Show') . ' ' . trans('admin.' . $contact->type) . ' - ' . $contact->name,
            'body' => view('Dashboard.contacts.show')->with([
                'contact' => $contact,
            ])->render(),
        ];
    }
}
