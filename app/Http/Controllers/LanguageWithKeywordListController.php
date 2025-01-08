<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\LanguageWithKeywordDataTable;
use App\Exports\LanguageWithKeywordExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\LanguageWithKeywordRequest;
use App\Imports\ImportLanguageWithKeyword;
use App\Models\DefaultKeyword;
use App\Models\LanguageList;
use App\Models\LanguageWithKeyword;
use App\Models\Screen;

class LanguageWithKeywordListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(LanguageWithKeywordDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title',['form' => __('message.language_with_keyword')] );
        $auth_user = authSession();
        if (!auth()->user()->can('languagewithkeyword-list')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }
        $assets = ['datatable'];
        $language = request('language') ?? null;
        $keyword = request('keyword') ?? null;
        $screen = request('screen') ?? null;
        if ($language != null) {
            $language = LanguageList::find($language);
        }
        if ($keyword != null) {
            $keyword = DefaultKeyword::find($keyword);
        }
        if ($screen != null) {
            $screen = Screen::where('screenId',$screen)->first();
        }

        $filter_array =[
            'language' => $language,
            'keyword' => $keyword,
            'screen' => $screen
        ];

        $button = '';
        $reset_file_button = '<a href="' . route('languagewithkeyword.index') . '" class=" mr-1 mt-0 btn btn-sm btn-outline-danger border-radius-10 mt-4 p-2"><i class="ri-repeat-line" style="font-size:12px"></i> ' . __('message.reset_filter') . '</a>';
        $pdfbutton = '<a href="'.route('download.language.with,keyword.list',$filter_array).'" class="float-right mr-1 border-radius-10 btn btn-md btn-outline-dark"><i class="fas fa-file-csv"></i> '.__('message.download_csv').'</a>';
        return $dataTable->render('global.languagewithkeyword-datatable', compact('pageTitle','button','auth_user','language','keyword','screen','pdfbutton','reset_file_button'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
       
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
        if (!auth()->user()->can('languagewithkeyword-edit')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }

        $pageTitle = __('message.update_form_title',[ 'form' => __('message.language_with_keyword')]);
        $data = LanguageWithKeyword::findOrFail($id);

        return view('app-language-setting.languagewithkeyword.form', compact('data', 'pageTitle', 'id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(LanguageWithKeywordRequest $request, $id)
    {
        if (!auth()->user()->can('languagewithkeyword-edit')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }
        $keys = LanguageWithKeyword::find($id);
        
        $message = __('message.not_found_entry', ['name' => __('message.language_with_keyword')]);
        if($keys == null) {
            return response()->json(['status' => false, 'message' => $message ]);
        }
        $keys->fill($request->all())->update();
        updateLanguageVersion();
        $message = __('message.update_form',['form' => __('message.language_with_keyword')]);

        if(auth()->check()){
            return response()->json(['status' => true, 'event' => 'submited','message'=> $message]);
            
        }
        return response()->json(['status' => true, 'event' => 'submited', 'message'=> $message]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

    }

    public function downloadLanguageWithKeywordList(Request $request)
    {        
        return Excel::download(new LanguageWithKeywordExport ,  'language-with-keyword-'.date('Ymd_H_i_s').'.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    
    public function importlanguagewithkeyword(Request $request){
        Excel::import(new ImportLanguageWithKeyword, $request->file('language_with_keyword')->store('files'));
        $message = __('message.save_form', ['form' => __('message.language_with_keyword')]);
        return redirect()->route('bulk.language.data')->withDone($message);
    }


    
    public function bulklanguagedata()
    {
        $auth_user = authSession();
        if (!auth()->user()->can('bulkimport-list')) {
            $message = __('message.permission_denied_for_account');
            return redirect()->back()->withErrors($message);
        }
        $pageTitle = __('message.bulk_import_langugage_data');

        return view('app-language-setting.languagewithkeyword.bulkimport',compact([ 'pageTitle' ]));
        
    }

    public function help(){

        $pageTitle = __('message.keyword_value_bulk_upload_fields');

        return view('app-language-setting.languagewithkeyword.help',compact([ 'pageTitle' ]));
        
    }

    public function downloadtemplate() {
        $pageTitle = __('message.download_template');

        return view('app-language-setting.languagewithkeyword.downloadtemplate',compact([ 'pageTitle' ]));

    }

}
