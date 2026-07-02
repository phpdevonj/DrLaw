<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DriverDocument;
use App\DataTables\DriverDocumentDataTable;
use App\Notifications\CommonNotification;
use App\Notifications\RideNotification;
use Illuminate\Support\Carbon;

class DriverDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(DriverDocumentDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.driver_document')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        $button = $auth_user->can('driverdocument add') ? '<a href="'.route('driverdocument.create').'" class="float-right btn btn-md border-radius-10 btn-outline-dark"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title',['form' => __('message.driver_document')]).'</a>' : '';
        $driverDocumentFilterButton = true;
        return $dataTable->render('global.datatable', compact('pageTitle','button','auth_user','driverDocumentFilterButton'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.driver_document')]);
        
        return view('driver_document.form', compact('pageTitle'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $data['expire_date'] = null;
        if(!empty($request->expire_date)){
            $expire_date = Carbon::parse($request->expire_date)->format('Y-m-d');
            $data['expire_date'] = $expire_date;
        }
        $data['is_verified'] = request('is_verified') != null ? request('is_verified') : 0;
        $data['driver_id'] = request('driver_id') == null && auth()->user()->hasRole('driver') ? auth()->user()->id : request('driver_id');
        $driver_document = DriverDocument::create($data);

        uploadMediaFile($driver_document,$request->driver_document, 'driver_document');
        $message = __('message.save_form',['form' => __('message.driver_document')]);
        $is_verified = $driver_document->is_verified;
        // Check if documents are verified
        //$docsVerified = (int) $driver_document->verifyDriverDocument($driver_document->driver->id);
            
        $driver_document->driver->checkVerified();

        if( in_array($is_verified, [ 1, 2, 3 ]) )
        {
            $type = 'document_approved';
            $status = __('message.approved');
            if( $is_verified == 0 ) {
                $type = 'document_pending';
                $status = __('message.pending');
            }
    
            if( $is_verified == 2 ) {
                $type = 'document_rejected';
                $status = __('message.rejected');
            }

            if( $is_verified == 3 ) {
                $type = 'document_expired';
                $status = __('message.expired');
            }

            $notification_data = [
                'id'   => $driver_document->driver->id,
                'is_verified_driver' => (int) $driver_document->driver->is_verified_driver,
                'type' => $type,
                'subject' => __('message.'.$type),
                'message' => $is_verified == 3
                    ? __('message.driver_expired_document', ['document' => $driver_document->document->name])
                    : __('message.approved_reject_form', [ 'form' => $driver_document->document->name, 'status' => $status ]),
                'country_id' => $driver_document->driver->country_id,
            ];
    
            $driver_document->driver->notify(new RideNotification($notification_data)); 
            $driver_document->driver->notify(new CommonNotification($notification_data['type'], $notification_data));
        }

         if($request->is('api/*')) {
            $response = [
                'driver_document_id' => $driver_document->id,
                'expiry_date' => $driver_document->expire_date,
                'message' => $message
            ];
            return response()->json($response,200);
        }
        
        return redirect()->route('driverdocument.index')->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.driver_document')]);
        $data = DriverDocument::findOrFail($id);

        return view('driver_document.show', compact('data'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pageTitle = __('message.update_form_title',[ 'form' => __('message.driver_document')]);
        $data = DriverDocument::findOrFail($id);
        
        return view('driver_document.form', compact('data', 'pageTitle', 'id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        if(!empty($request->expire_date)){
            $expire_date = Carbon::parse($request->expire_date)->format('Y-m-d');
            $request['expire_date'] = $expire_date;
        }

        $driver_document = DriverDocument::find($id);

        if (!request()->is('api/*')) {
            if ($driver_document->driver->country_id != session('current_country_id')) {
                abort(403, "Please switch country to access this page or you don't have permission for this country.");
            }
        }

        if($driver_document == '') {
            $message = __('message.not_found_entry', ['name' => __('message.driver_document')]);
            
            if(request()->is('api/*')){
                return json_message_response( $message );
            }

            return redirect()->route('driverdocument.index')->withErrors($message);
        }
        $old_is_verified = $driver_document->is_verified;
        // DriverDocument data...
        $driver_document->fill($request->all())->update();

        if (isset($request->driver_document) && $request->driver_document != null) {
            $driver_document->clearMediaCollection('driver_document');
            $driver_document->addMediaFromRequest('driver_document')->toMediaCollection('driver_document');
            
            // If re-uploading, reset status to pending and clear rejection reason
            if ($driver_document->is_verified == 2) {
                $driver_document->is_verified = 0;
                $driver_document->rejection_reason = null;
                $driver_document->save();
            }
        }
        
        $message = __('message.update_form',['form' => __('message.driver_document') ] );

        $is_verified = $driver_document->is_verified;
        if( in_array($is_verified, [ 0, 1, 2, 3 ])  || $driver_document->driver->is_verified_driver == 0 ) {
             // Manually trigger document verification check since fill()->update() doesn't fire observers
             $this->checkAndUpdateDriverVerification($driver_document->driver_id);
        }
        
        if($old_is_verified != $is_verified && in_array($is_verified, [ 0, 1, 2, 3 ] )) {
            
            $type = 'document_approved';
            $status = __('message.approved');
            if( $is_verified == 0 ) {
                $type = 'document_pending';
                $status = __('message.pending');
            }

            if( $is_verified == 2 ) {
                $type = 'document_rejected';
                $status = __('message.rejected');
            }

            if( $is_verified == 3 ) {
                $type = 'document_expired';
                $status = __('message.expired');
            }
            
            $notification_data = [
                'id'   => $driver_document->driver->id,
                'is_verified_driver' => (int) $driver_document->driver->is_verified_driver,
                'type' => $type,
                'subject' => __('message.'.$type),
                'message' => $is_verified == 3
                    ? __('message.driver_expired_document', ['document' => $driver_document->document->name])
                    : __('message.approved_reject_form', [ 'form' => $driver_document->document->name, 'status' => $status ]),
                'country_id' => $driver_document->driver->country_id,
            ];
    
            $driver_document->driver->notify(new RideNotification($notification_data)); 
            $driver_document->driver->notify(new CommonNotification($notification_data['type'], $notification_data));
        }

        if($request->is('api/*')) {
            $response = [
                'driver_document_id' => $driver_document->id,
                'expiry_date' => $driver_document->expire_date,
                'message' => $message
            ];
            return response()->json($response,200);
        }

        if(auth()->check()){
            return redirect()->route('driverdocument.index')->withSuccess($message);
        }
        return redirect()->back()->withSuccess($message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        if(env('APP_DEMO')){
            $message = __('message.demo_permission_denied');
            if(request()->ajax()) {
                return response()->json(['status' => true, 'message' => $message ]);
            }
            return redirect()->route('driverdocument.index')->withErrors($message);
        }
        $driver_document = DriverDocument::find($id);
        $status = 'errors';
        $message = __('message.not_found_entry', ['name' => __('message.driver_document')]);

        if($driver_document != '') {
            $driver_document->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.driver_document')]);
        }
        
        if(request()->is('api/*')){
            return json_message_response( $message );
        }

        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message ]);
        }

        return redirect()->back()->with($status,$message);
    }
    protected function checkAndUpdateDriverVerification(int $driverId)
    {
        try {
            \Illuminate\Support\Facades\Log::info("DOCUMENT CONTROLLER: Checking document verification for driver {$driverId}");
            
            $driver = \App\Models\User::find($driverId);
            
            if (!$driver) {
                \Illuminate\Support\Facades\Log::warning("DOCUMENT CONTROLLER: Driver {$driverId} not found.");
                return;
            }
            
            if ($driver->user_type !== 'driver') {
                \Illuminate\Support\Facades\Log::warning("DOCUMENT CONTROLLER: User {$driverId} is not a driver.");
                return;
            }
            
            // Get all required documents for driver's country
            $requiredDocuments = \App\Models\Document::where('country_id', $driver->country_id)
                ->where('is_required', 1)
                ->where('status', 1)
                ->get();
            
            $totalRequired = $requiredDocuments->count();
            
            if ($totalRequired == 0) {
                \Illuminate\Support\Facades\Log::info("DOCUMENT CONTROLLER: No required documents configured for driver {$driverId}. Setting as verified.");
                // $driver->is_verified_driver = 1;
                $driver->identity_check_status = 'not_started';

                // For India, automatically pass manual review
                if ($driver->country_id == 5 || (isset($driver->country) && strtolower($driver->country->name) == 'india')) {
                    $driver->manual_review_status = 'passed';
                    // $driver->status = 'active';
                }

                $driver->save();

                // sendVerificationNotification($driverId);

                return;
            }
            
            // Check each required document
            $verifiedCount = 0;
            $missingDocuments = [];
            
            foreach ($requiredDocuments as $doc) {
                $driverDoc = DriverDocument::where('driver_id', $driverId)
                    ->where('document_id', $doc->id)
                    ->where('is_verified', 1)
                    ->first();
                    
                if ($driverDoc) {
                    $verifiedCount++;
                } else {
                    $missingDocuments[] = $doc->name;
                }
            }
            
            \Illuminate\Support\Facades\Log::info("DOCUMENT CONTROLLER: Driver {$driverId} has {$verifiedCount}/{$totalRequired} verified documents.", [
                'missing' => $missingDocuments
            ]);

            if ($verifiedCount == $totalRequired) {
                // All documents verified - update driver status
                // $driver->is_verified_driver = 1;
                $driver->identity_check_status = 'not_started';

                // For India, automatically pass manual review
                if ($driver->country_id == 5 || (isset($driver->country) && strtolower($driver->country->name) == 'india')) {
                    $driver->manual_review_status = 'passed';
                    // $driver->status = 'active';
                    // $driver->is_verified_driver = 1;
                }

                $driver->save();
                
                // sendVerificationNotification($driverId);

                \Illuminate\Support\Facades\Log::info("DOCUMENT CONTROLLER: SUCCESS - Set is_verified_driver = 1 and identity_check_status = 'PASSED' for driver {$driverId}.");
            } else {
                \Illuminate\Support\Facades\Log::info("DOCUMENT CONTROLLER: Driver {$driverId} still has " . count($missingDocuments) . " unverified documents: " . implode(', ', $missingDocuments));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("DOCUMENT CONTROLLER: Failed to update driver verification for driver {$driverId}.", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
