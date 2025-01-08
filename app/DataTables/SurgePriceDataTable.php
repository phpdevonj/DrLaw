<?php

namespace App\DataTables;

use App\Models\SurgePrice;
use App\Traits\DataTableTrait;
use DateTime;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SurgePriceDataTable extends DataTable
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
            ->editColumn('day', function ($row) {
                $dayIds = $row->day;
            
                $dayNamesMap = ['1' => 'Monday','2' => 'Tuesday','3' => 'Wednesday','4' => 'Thursday',
                    '5' => 'Friday','6' => 'Saturday','7' => 'Sunday'
                ];
            
                $dayNames = array_map(function($dayId) use ($dayNamesMap) {
                    return $dayNamesMap[$dayId] ?? '';
                }, $dayIds);
            
                return implode(', ', $dayNames);
            })
            
            ->filterColumn('day', function( $query, $keyword ){
                $query->where('day', 'like' , '%'.$keyword.'%');                    
            })
            
            ->editColumn('type' , function ( $row ) {
                return ucfirst($row->type) ?? '';
            })

            ->editColumn('value' , function ( $row ) {
                if ($row->type == 'fixed') {
                    return getPriceFormat($row->value);
                } else {
                    return $row->value . " %";
                }
            })

            ->addColumn('time_ranges', function ($row) {
                $fromTimes = is_array($row->from_time) ? $row->from_time : [];
                $toTimes = is_array($row->to_time) ? $row->to_time : [];
                $timeRanges = [];
            
                foreach (array_map(null, $fromTimes, $toTimes) as [$fromTime, $toTime]) {
                    $fromTimeFormatted = (new DateTime($fromTime))->format('g:i A');
                    $toTimeFormatted = (new DateTime($toTime))->format('g:i A');
                    $timeRanges[] = "From time: $fromTimeFormatted, To time: $toTimeFormatted";
                }
            
                return implode('<br>', $timeRanges);
            })
            ->editColumn('created_at', function ($query) {
                return dateAgoFormate($query->created_at, true);
            })
            ->addIndexColumn()
            ->addColumn('action', function($surge_price){
                $id = $surge_price->id;
                return view('surge_price.action',compact('surge_price','id'))->render();
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
            ->rawColumns([ 'action', 'status','time_ranges' ]);
    }

    /**
     * Get query source of dataTable.
     *
     * @param \App\Models\SurgePriceDataTable $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function query(SurgePrice $model)
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use html builder.
     *
     * @return \Yajra\DataTables\Html\Builder
     */
    protected function getColumns()
    {
        return [
            Column::make('DT_RowIndex')
                ->searchable(false)
                ->title(__('message.srno'))
                ->orderable(false)
                ->width(60),
            Column::make('day')->title( __('message.day') ),
            Column::make('type')->title( __('message.type') ),
            Column::make('value')->title( __('message.value') ),
            Column::make('time_ranges')->title( __('message.timing') )->orderable(false),
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
        return 'SurgePrice_' . date('YmdHis');
    }
}
