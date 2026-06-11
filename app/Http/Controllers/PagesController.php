<?php

namespace App\Http\Controllers;

use App\DataTables\PagesDataTable;
use App\Http\Requests\PageRequest;
use App\Models\Pages;
use Illuminate\Http\Request;

class PagesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(PagesDataTable $dataTable)
    {
        $pageTitle = __('message.list_form_title', ['form' => __('message.pages')]);
        $auth_user = authSession();
        $assets = ['datatable'];
        $button = $auth_user->can('pages add') ? '<a href="'.route('pages.create').'" class="float-right btn btn-sm btn-primary"><i class="fa fa-plus-circle"></i> '.__('message.add_form_title',['form' => __('message.pages')]).'</a>' : '';
        return $dataTable->render('global.datatable', compact('pageTitle', 'auth_user','button'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pageTitle = __('message.add_form_title', ['form' => __('message.pages')]);
        return view('pages.form', compact('pageTitle'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PageRequest $request)
    {
        $pages = Pages::Create($request->all());

        $message = __('message.save_form',['form' => __('message.pages')]);
        return redirect()->route('pages.index')->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $pageTitle = __('message.update_form_title', ['form' => __('message.pages')]);

        $pages = Pages::find($id);

        return view('pages.form', compact('pageTitle','pages','id'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PageRequest $request, $id)
    {
        $pages = Pages::find($id);
        $message = __('message.not_found_entry', ['name' => __('message.pages')]);
        if($pages == null) {
            return response()->json(['status' => false, 'message' => $message ]);
        }
        $message = __('message.update_form', ['form' => __('message.pages')]);
        $pages->fill($request->all())->update();

        return redirect()->route('pages.index')->withSuccess($message);
    
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $pages = Pages::find($id);
        $status = 'error';
        $message = __('message.not_found_entry', ['name' => __('message.pages')]);

        if($pages != '') {
            $pages->delete();
            $status = 'success';
            $message = __('message.delete_form', ['form' => __('message.pages')]);
        }

        if(request()->is('api/*')){
            return response()->json(['status' => true, 'message' => $message ]);
        }
        if(request()->ajax()) {
            return response()->json(['status' => true, 'message' => $message ]);
        }

        return redirect()->back()->with($status,$message);
    }
}
