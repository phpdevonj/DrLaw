<?php

namespace App\DataTables;

use App\Models\PushNotification;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

use App\Traits\DataTableTrait;

class PushNotificationDataTable extends DataTable
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
            ->editColumn('usertype', function($query) {
                if($query->for_rider && $query->for_driver) {
                    $status = 'primary';
                    $status_label = __('message.both');
                }elseif ($query->for_rider) {
                    $status = 'info';
                    $status_label = __('message.rider');
                }else {
                    $status = 'secondary';
                    $status_label = __('message.driver');
                }
                return '<span class="text-capitalize text-' .$status .' badge badge-light-'.$status.'">'.$status_label.'</span>';
            })

            ->editColumn('message', function($row) {
                return isset($row->message) ? stringLong($row->message, 'desc') : null;
            })
            ->editColumn('created_at', function ($query) {
                return dateAgoFormate($query->created_at, true);
            })
            ->addIndexColumn()
            ->addColumn('action', 'push_notification.action')
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
            ->rawColumns([ 'action', 'usertype' ]);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\PushNotification $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $model = PushNotification::query();
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
            Column::make('title')->title( __('message.title') ),
            Column::make('message')->title( __('message.message') ),
            Column::make('usertype')->title( __('message.notification_for') )->orderable(false),
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
        return 'PushNotifications_' . date('YmdHis');
    }
}
