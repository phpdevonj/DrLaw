<?php

namespace App\DataTables;

use App\Models\User;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

use App\Traits\DataTableTrait;

class RiderDataTable extends DataTable
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
            
            ->editColumn('status', function($query) {
                $status = 'warning';
                switch ($query->status) {
                    case 'active':
                        $status = 'primary';
                        break;
                    case 'inactive':
                        $status = 'danger';
                        break;
                    case 'banned':
                        $status = 'dark';
                        break;
                }
                return '<span class="text-capitalize text-' .$status .' badge badge-light-'.$status.'">'.$query->status.'</span>';
            })
            ->editColumn('display_name', function ($query) {
                return '<a href="'.route('rider.show',$query->id).'">'.$query->display_name.'</span></a>';

            })
            ->editColumn('created_at', function($query) {
                return date('Y/m/d',strtotime($query->created_at));
            })
            ->editColumn('email', function($query) {
                return auth()->user()->hasRole('admin') ? $query->email : maskSensitiveInfo('email', $query->email);
            })
            ->editColumn('last_actived_at', function ($query) {
                return dateAgoFormate($query->last_actived_at, true) ?? '-';
            })

            ->editColumn('app_version',function($row){
                return $row->app_version ?? '-';
            })
            ->editColumn('created_at', function ($query) {
                return dateAgoFormate($query->created_at, true);
            })
            ->addIndexColumn()
            ->addColumn('action', 'rider.action')
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
            ->rawColumns(['action','status','display_name']);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\User $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $model = User::where('user_type','rider');
        if (request()->rider_id) {
            $model->where('id', request()->input('rider_id'));
        }
        if (request()->contact_number) {
            $model->where('contact_number', 'like', '%' . request()->input('contact_number') . '%');
        }        
        $last_active = isset($_GET['last_actived_at']) ? $_GET['last_actived_at'] : null;
        if ($last_active != null) {
            if ($last_active == 'active_user') {
                $model = $model->whereDate('last_actived_at',  now());
            } elseif ($last_active == 'engaged_user') {
                $model = $model->where('last_actived_at', '<', now()->subDay())->where('last_actived_at', '>', now()->subDays(15));
            } elseif ($last_active == 'inactive_user') {
                $model = $model->where('last_actived_at', '<=', now()->subDays(15))->orWhereNull('last_actived_at');
            }
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
            Column::make('DT_RowIndex')
                ->searchable(false)
                ->title(__('message.srno'))
                ->orderable(false)
                ->width(60),
            Column::make('display_name')->title( __('message.name') ),
            Column::make('email'),
            Column::make('contact_number'),
            Column::make('last_actived_at'),
            Column::make('app_version'),
            Column::make('created_at')->title( __('message.created_at') ),
            Column::make('status'),
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
        return 'Users_' . date('YmdHis');
    }
}
