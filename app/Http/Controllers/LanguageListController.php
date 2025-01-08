<?php

namespace App\Http\Controllers;

use App\DataTables\LanguageListDataTable;
use App\Http\Requests\LanguageListRequest;
use App\Models\DefaultKeyword;
use App\Models\LanguageList;
use App\Models\LanguageWithKeyword;

class LanguageListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(LanguageListDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.language')] );
        $auth_user = authSession();
        if (!auth()->user()->can('languagelist-list')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }
        $assets = ['datatable'];

        $button = $auth_user->can('languagelist-add') ? '<a href="'.route('languagelist.create').'" class="float-right btn btn-md border-radius-10 btn-outline-dark"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title',['form' => __('message.language')]).'</a>' : '';
        return $dataTable->render('global.datatable', compact('pageTitle','button','auth_user'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('languagelist-add')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }  
        $pageTitle = __('message.add_form_title',[ 'form' => __('message.language')]);
        
        return view('app-language-setting.languagelist.form', compact('pageTitle'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(LanguageListRequest $request)
    {
        if (!auth()->user()->can('languagelist-add')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }
    
        $language = LanguageList::create($request->all());
        uploadMediaFile($language,$request->language_image, 'language_image');

        if ($request->is_default) {
            LanguageList::where('id', '!=', $language->id)->update(['is_default' => 0]);
        }
        $language_keyword = DefaultKeyword::all();
        if(count($language_keyword) > 0){
            foreach($language_keyword as $value){
                $languagedata = [
                    'id' => null,
                    'keyword_id' => $value->keyword_id,
                    'screen_id' => $value->screen_id,
                    'language_id' => $language->id,
                    'keyword_value' => $value->keyword_value,
                ];
                LanguageWithKeyword::create($languagedata);
            }
        }
        updateLanguageVersion();
        $message = __('message.save_form', ['form' => __('message.language')]);
        
        if(request()->is('api/*')){
            return response()->json(['status' => true, 'message' => $message ]);
        }

        return redirect()->route('languagelist.index')->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('languagelist-edit')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }
        $pageTitle = __('message.update_form_title',[ 'form' => __('message.language')]);
        $data = LanguageList::findOrFail($id);

        return view('app-language-setting.languagelist.form', compact('data', 'pageTitle', 'id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(LanguageListRequest $request, $id)
    {
        if (!auth()->user()->can('languagelist-edit')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }
        $language = LanguageList::find($id);
        $message = __('message.not_found_entry', ['name' => __('message.language')]);

        if($language == null) {
            return response()->json(['status' => false, 'message' => $message ]);
        }
        if ($request->is_default) {
            LanguageList::where('id', '!=', $language->id)->update(['is_default' => 0]);
        }
        $language->fill($request->all())->update();  

        // Save language language_image...
        if (isset($request->language_image) && $request->language_image != null) {
            $language->clearMediaCollection('language_image');
            $language->addMediaFromRequest('language_image')->toMediaCollection('language_image');
        }

        updateLanguageVersion();     
        $message = __('message.update_form',['form' => __('message.language')]);
        
        if(auth()->check()){
            return redirect()->route('languagelist.index')->withSuccess($message);
        }
        return redirect()->back()->withSuccess($message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('languagelist-delete')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }
        $language = LanguageList::find($id);
        $status = 'error';
        $message = __('message.not_found_entry', ['name' => __('message.language')]);

        if($language != '') {
            $language->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.language')]);
        }
        updateLanguageVersion();
        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message ]);
        }

        return redirect()->back()->with($status,$message);
    }
}

