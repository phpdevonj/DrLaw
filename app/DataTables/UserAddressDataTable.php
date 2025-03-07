<?php

namespace App\DataTables;

use App\Models\UserAddress;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

use App\Traits\DataTableTrait;

class UserAddressDataTable extends DataTable
{
    use DataTableTrait;
    /**
     * Build DataTable class.
     *
     * @param mixed $query Results from query() method.
     * @return \Yajra\DataTables\DataTableAbstract
     */
    public function dataTable($query)
    {
        return datatables()
        ->eloquent($query)
        ->editColumn('user_id' , function ( $query ) {
            return $query->user_id != null ? optional($query->user)->display_name : '';
        })        
        ->filterColumn('user_id', function( $query, $keyword ){
            $query->whereHas('user', function ($q) use($keyword){
                $q->where('display_name', 'like' , '%'.$keyword.'%');
            });
        })
        ->editColumn('label' , function ( $query ) {
            return $query->label != null ? ucwords($query->label) : '';
        })
        // ->editColumn('is_default', function ($query) {
        //     return $query->is_default == 1 ? 'Yes' : 'No';
        // })
        ->editColumn('created_at', function ($query) {
            return dateAgoFormate($query->created_at, true);
        })
        ->addIndexColumn()
        ->addColumn('action', 'user_address.action')
        ->order(function ($query) {
            if (request()->has('order')) {
                $order = request()->order[0];
                $column_index = $order['column'];

                $column_name = 'created_at';
                $direction = 'desc';
                if( $column_index != 0) {
                    $column_name = request()->columns[$column_index]['data'];
                    $direction = $order['dir'];
                }

                $query->orderBy($column_name, $direction);
            }
        })
        ->rawColumns([ 'action']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\UserAddressDataTable $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(UserAddressDataTable $model)
    {
        $model = UserAddress::query();

        if( $this->user_id != null ) {
            return $model->where('user_id', $this->user_id);
        } 
        return $this->applyScopes($model);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        return [
            Column::make('id')->title( __('message.id') ),
            Column::make('user_id')->title( __('message.name') ),
            Column::make('label')->title( __('message.address_type') ),
            Column::make('custom_label')->title( __('message.custom_label') ),
            Column::make('address_line1')->title( __('message.street_address') ),
            // Column::make('address_line2')->title( __('message.address_line2') ),
            // Column::make('city')->title( __('message.city') ),
            // Column::make('country')->title( __('message.country') ),
            // Column::make('is_default')->title( __('message.is_default') ),
            Column::make('created_at')->title( __('message.created_at') ),
            Column::computed('action')
                  ->exportable(false)
                  ->printable(false)
                  ->width(60)
                  ->addClass('text-center'),
        ];
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'UserAddress_' . date('YmdHis');
    }
}
