<?php

namespace App\DataTables;

use App\Models\RideRequest;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

use App\Traits\DataTableTrait;

class RideRequestDataTable extends DataTable
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
            
            ->editColumn('driver_id' , function ( $riderequest ) {
                return $riderequest->driver_id != null ? optional($riderequest->driver)->display_name : '';
            })
            
            ->editColumn('riderequest_in_driver_id' , function ( $riderequest ) {
                if ($riderequest->ride_has_bid == 1) {
                    // return $riderequest->nearby_driver_ids != null ? $riderequest->nearby_driver_ids : '';
                    return $riderequest->nearby_driver_ids != null ? optional($riderequest->nearby_drivers)->display_name : '';
                } else {
                    return $riderequest->riderequest_in_driver_id != null ? optional($riderequest->riderequest_in_driver)->display_name : '';
                }
            })

            ->filterColumn('driver_id', function( $query, $keyword ){
                $query->whereHas('driver', function ($q) use($keyword){
                    $q->where('display_name', 'like' , '%'.$keyword.'%');
                });
            })

            ->editColumn('rider_id' , function ( $riderequest ) {
                return $riderequest->rider_id != null ? optional($riderequest->rider)->display_name : '';
            })

            ->editColumn('payment_status', function ( $riderequest ) {
                return isset($riderequest->payment) ? __('message.'.$riderequest->payment->payment_status) : __('message.pending');
            })

            ->editColumn('payment_type', function($riderequest) {
                return isset($riderequest->payment_type) ? __('message.'.$riderequest->payment_type) : __('message.cash');
            })

            ->editColumn('payment_status', function($riderequest) {
                
                $status = 'warning';
                $payment_status = isset($riderequest->payment) ? $riderequest->payment->payment_status : __('message.pending');
                
                switch ($payment_status) {
                    case 'pending':
                        $status = 'warning';
                        break;
                    case 'failed':
                        $status = 'danger';
                        break;
                    case 'paid':
                        $status = 'success';
                        break;
                }
                return '<span class="text-capitalize text-' .$status .' badge badge-light-'.$status.'">'.$payment_status.'</span>';
            })
            
            ->filterColumn('payment_status', function( $query, $keyword ){
                $query->whereHas('payment', function ($q) use($keyword){
                    $q->where('payment_status', 'like' , '%'.$keyword.'%');
                });
            })

            ->filterColumn('rider_id', function( $query, $keyword ){
                $query->whereHas('rider', function ($q) use($keyword){
                    $q->where('display_name', 'like' , '%'.$keyword.'%');
                });
            })

            ->editColumn('status', function($query) {
                return __('message.'.$query->status);
            })

            ->editColumn('status', function($riderequest) {
                
                $status = 'primary';
                $ride_status = $riderequest->status;
                
                switch ($ride_status) {
                    case 'pending':
                        $status = 'warning';
                        break;
                    case 'canceled':
                        $status = 'danger';
                        break;
                    case 'completed':
                        $status = 'success';
                        break;
                    default:
                        // $ride_status = '-';
                        break;
                }
                return '<span class="text-' .$status .' badge badge-light-'.$status.'">'.__('message.'.$riderequest->status).'</span>';
            })
            ->editColumn('created_at', function ($query) {
                return dateAgoFormate($query->created_at, true);
            })

            ->addColumn('invoice', function ($query) {
                return $query->status == 'completed' ? '<a href="' . route('ride-invoice', $query->id) . '"><i class="ri-download-2-line" style="font-size:25px"></i></a>' : 'N/A';
            })

            ->addIndexColumn()
            ->addColumn('action', 'riderequest.action')
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
            ->rawColumns([ 'action', 'status', 'payment_status', 'invoice' ]);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\RideRequest $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query()
    {
        $model = RideRequest::myRide()->orderBy('id','desc');

        if (!empty(request()->driver_id) || $this->driver_id) {
            $model->where('driver_id', request()->driver_id ?? $this->driver_id);
        }
    
        if (!empty(request()->rider_id) ||  $this->rider_id) {
            $model->where('rider_id', request()->rider_id ??  $this->rider_id);
        }
    
        $riderequest_type = request('riderequest_type');
        if (in_array($riderequest_type, ['pending', 'canceled', 'completed', 'new_ride_requested'])) {
            $model->where('status', $riderequest_type);
        }
    
        if (request()->payment_status) {
            $model->whereHas('payment', function ($q) {
                $q->where('payment_status', request('payment_status'));
            });
        }
    
        if (request()->payment_method) {
            $model->whereHas('payment', function ($q) {
                $q->where('payment_type', request('payment_method'));
            });
        }
    
        if (request()->ride_status) {
            $model->where('status', request('ride_status'));
        }
    
        $start_date = request('start_date');
        $end_date = request('end_date');
        if ($start_date || $end_date) {
            $model->whereDate('created_at','>=',$start_date);
            $model->whereDate('created_at','<=',$end_date);
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
            // Column::make('DT_RowIndex')
            //     ->searchable(false)
            //     ->title(__('message.srno'))
            //     ->orderable(false)
            //     ->width(60),
            Column::make('id')->title( '#' ),
            Column::make('rider_id')->title( __('message.rider') ),
            Column::make('riderequest_in_driver_id')->title( __('message.requested_driver') ),
            Column::make('driver_id')->title( __('message.driver') ),
            Column::make('datetime')->title( __('message.datetime') ),
            // Column::make('total_amount')->title( __('message.total_amount') ),
            Column::make('payment_type')->title( __('message.payment_method') ),
            Column::make('payment_status')->title( __('message.payment') )->orderable(false),
            Column::computed('invoice')->addClass('text-center'),
            Column::make('created_at')->title( __('message.created_at') ),
            Column::make('status')->title( __('message.status') ),
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
        return 'RideRequests_' . date('YmdHis');
    }
}
