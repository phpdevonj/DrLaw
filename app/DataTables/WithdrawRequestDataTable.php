<?php

namespace App\DataTables;

use App\Models\WithdrawRequest;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

use App\Traits\DataTableTrait;

class WithdrawRequestDataTable extends DataTable
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
                    case 0:
                        $status = 'primary  ';
                        $status_label =  __('message.requested');
                        break;
                    case 1:
                        $status = 'success';
                        $status_label =  __('message.approved');
                        break;
                    case 2:
                        $status = 'danger';
                        $status_label =  __('message.decline');
                        break;
                    default:
                        $status_label = null;
                        break;
                }
                return '<span class="text-capitalize text-' .$status .' badge badge-light-'.$status.'">'.$status_label.'</span>';
            })
            ->editColumn('user_id' , function ( $query ) {
                $user = optional($query->user);
                $route = '#';
                if ($user->user_type == 'driver') {
                    # code...
                    $route = route('driver.show',$query->user_id);
                }
                if ($user->user_type == 'rider') {
                    # code...
                    $route = route('rider.show',$query->user_id);
                }
                return $query->user_id != null ? '<a href="'.$route.'">'.$user->display_name.'</a>' : '-';
            })

            ->filterColumn('user_id', function( $query, $keyword ){
                $query->whereHas('user', function ($q) use($keyword){
                    $q->where('display_name', 'like' , '%'.$keyword.'%');
                });
            })

            ->editColumn('wallet_balance' , function ( $query ) {
                $wallet_balance = '-';
                if($query->status == 0 ) {
                    $wallet_balance = optional($query->user) && optional($query->user)->userWallet ? getPriceFormat(optional($query->user)->userWallet->total_amount) : null;
                }

                return $wallet_balance;
            })
            ->editColumn('created_at', function ($query) {
                return dateAgoFormate($query->created_at, true);
            })
            ->editColumn('updated_at', function ($query) {
                return dateAgoFormate($query->updated_at, true);
            })
            ->editColumn('amount', function ($query) {
                return getPriceFormat($query->amount);
            })
            ->addColumn('bank_details', function ($row) {
                return $row->user_id != null ? '<a href="'.route('bankdetail',$row->user_id).'" class="mr-2 loadRemoteModel"><i class="fas fa-eye text-secondary"></i></a>' : '-';
            })
            ->addIndexColumn()
            
            ->addColumn('action', function($query){
                return view('withdrawrequest.action',compact('query'))->render();
            })
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
            ->rawColumns([ 'action', 'status', 'user_id', 'bank_details' ]);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\WithdrawRequest $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(WithdrawRequest $model)
    {
        if ($this->rider_id != null) {
            $model = $model->where('user_id',$this->rider_id);
        }

        $withdraw_type = isset($_GET['withdraw_type']) ? $_GET['withdraw_type'] : null;
        switch ($withdraw_type) {
            case 'pending':
                # code...
                $model = $model->where('status',0);
                break;
            case 'approved':
                # code...
                $model = $model->where('status',1);
                break;
            case 'decline':
                # code...
                $model = $model->where('status',2);
                break;
            default:
                # code...
                break;
        }

        $model = $model->myWithdrawRequest()->orderBy('id','desc');
        return $this->applyScopes($model);
    }

    /**
     * Get columns.
     *
     * @return array
     */
    protected function getColumns()
    {
        $withdraw_type = isset($_GET['withdraw_type']) ? $_GET['withdraw_type'] : null;

        $columns = [
            Column::make('DT_RowIndex')->searchable(false)->title(__('message.srno'))->orderable(false)->width(60),
            Column::make('user_id')->title(__('message.name')),
            Column::make('amount')->title(__('message.amount')),
        ];
    
        $wallet_balance = Column::make('wallet_balance')->title(__('message.available_balnce'))->searchable(false)->orderable(false);
        $created_at = Column::make('created_at')->title(__('message.request_at'));
        $updated_at = Column::make('updated_at')->title(__('message.action_at'));
        $bank_details = Column::computed('bank_details')->title(__('message.detail_form_title', ['form' => __('message.bank')]));
        $status = Column::make('status')->title(__('message.status'));
        $action = Column::computed('action')->exportable(false)->printable(false)->width(60)->addClass('text-center');
    
        
        
        switch ($withdraw_type) {
            case 'approved':
            case 'decline':
                $columns = array_merge($columns, [$created_at, $updated_at->title(__('message.'.$withdraw_type.'_date')), $status]);
                break;
            case 'pending':
                $columns = array_merge($columns, [$wallet_balance, $created_at, $bank_details, $status, $action]);
                break;
            case 'all':
                $columns = array_merge($columns, [$wallet_balance, $created_at, $updated_at, $bank_details, $status, $action]);
                break;
        }
    
        return $columns;
    }

    /**
     * Get filename for export.
     *
     * @return string
     */
    protected function filename()
    {
        return 'withdraw_request_' . date('YmdHis');
    }
}
