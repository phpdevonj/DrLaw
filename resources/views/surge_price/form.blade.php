<x-master-layout :assets="$assets ?? []">
    <div>
        <?php $id = $id ?? null;?>
        @if(isset($id))
            {!! Form::model($data, ['route' => ['surge-prices.update', $id], 'method' => 'patch']) !!}
        @else
            {!! Form::open(['route' => ['surge-prices.store'], 'method' => 'post']) !!}
        @endif
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between">
                        <div class="header-title">
                            <h4 class="card-title">{{ $pageTitle }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="new-user-info">
                            <div class="row">
                                <div class="form-group col-md-4">
                                    {{ Form::label('day',__('message.day').' <span class="text-danger">*</span>',['class'=>'form-control-label'], false ) }}
                                    {{ Form::select('day[]',getDaysOfWeek(), old('day'), [ 'class' =>'form-control select2js' ,'multiple' => 'multiple','data-placeholder' => __('message.select_name',[ 'select' => __('message.day') ])]) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('type',__('message.type'),['class'=>'form-control-label']) }}
                                    {{ Form::select('type',[ 'fixed' => __('message.fixed'), 'percentage' => __('message.percentage') ], old('type'), [ 'class' =>'form-control select2js']) }}
                                </div>

                                <div class="form-group col-md-4">
                                    {{ Form::label('value', __('message.value').' <span class="text-danger">*</span>',['class' => 'form-control-label'], false ) }}
                                    {{ Form::number('value', old('value'),[ 'step' =>'any', 'min' =>'0', 'placeholder' => __('message.value'), 'class' => 'form-control']) }}
                                </div>

                                <div class=" form-group col-md-12 mt-3">
                                    <button type="button" id="add_button" class="btn mb-3 btn-sm btn-primary float-right">{{ __('message.add_form_title',['form' => '']) }}</button>
                                        @if(isset($id) && !empty($data))
                                            <table id="table_list" class="table border-none">
                                                <tbody> 
                                                    @foreach($data->from_time as $index => $fromTime)
                                                    <tr id="row_{{$index}}" row="{{$index}}" data-id="{{$index}}">
                                                            <td></td>
                                                            <td class="col-md-5">
                                                                <div class="form-group mt-3">
                                                                    {{ Form::label('from_time[]', __('message.from_time'), ['class' => 'form-control-label']) }}
                                                                    {{ Form::text('from_time[]', $fromTime, ['id' => 'from_time_'.$index, 'class' => 'form-control clone-min-timerange-picker']) }}
                                                                </div>
                                                            </td>
                                                            <td class="col-md-5">
                                                                <div class="form-group mt-3">
                                                                    {{ Form::label('to_time[]', __('message.to_time'), ['class' => 'form-control-label']) }}
                                                                    {{ Form::time('to_time[]', $data->to_time[$index], ['id' => 'to_time_'.$index, 'class' => 'form-control clone-min-timerange-picker']) }}
                                                                </div>
                                                            </td>
                                                            <td class="col-md-2">
                                                                <div class="form-group mt-4">
                                                                    <a href="javascript:void(0)" id="remove_{{$index}}" class="removebtn btn btn-sm btn-icon btn-danger" row="{{$index}}">
                                                                        <i class="ri-delete-bin-2-fill"></i>
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <table id="table_list" class="table border-none">
                                                <tbody>
                                                    <tr id="row_0" row="0" data-id="0">
                                                        <td></td>
                                                        <td class="col-md-5">
                                                            <div class="form-group mt-3">
                                                                {{ Form::label('from_time', __('message.from_time'),['class' => 'form-control-label'] ) }}
                                                                {{ Form::text('from_time[]', is_array(old('from_time')),[ 'id' => 'from_time_no_0', 'placeholder' => __('message.from_time'),'class' =>'form-control  clone-min-timerange-picker']) }}
                                                            </div>
                                                        </td>
                                                        <td class="col-md-5">
                                                            <div class="form-group mt-3">
                                                                {{ Form::label('to_time', __('message.to_time'),['class' => 'form-control-label'] ) }}
                                                                {{ Form::text('to_time[]', is_array(old('to_time')),[ 'id' => 'to_time_no_0', 'placeholder' => __('message.to_time'),'class' =>'form-control  clone-min-timerange-picker']) }}
                                                            </div>
                                                        </td>
                                                        <td class="col-md-2">
                                                            <div class="form-group mt-4">
                                                                <a href="javascript:void(0)" id="remove_0" class="removebtn btn btn-sm btn-icon btn-danger" row="0">
                                                                    <i class="ri-delete-bin-2-fill"></i>
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        @endif
                                    <hr>
                                </div>
                            </div>
                            <hr>
                            {{ Form::button(
                                '<span id="button-loader" style="display:none;"><div class="spinner-border spinner-border-sm text-light" role="status"></div></span> ' . 
                                (isset($id) ? __('message.update') : __('message.save')), 
                                [
                                    'type' => 'submit',
                                    'class' => 'btn btn-md btn-primary float-right',
                                    'id' => 'submit-btn'
                                ]
                            ) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {!! Form::close() !!}
    </div>
    <style>
        .flatpickr-hour:focus{
            background-color: #b9b5b5 !important;
        }
    </style>
    @section('bottom_script')
        <script>
            (function($) {
                "use strict";
                $(document).ready(function() {
                    var resetSequenceNumbers = function() {
                        $("#table_list tbody tr").each(function(i) {
                            $(this).find('td:first').text(i + 1);
                        });
                    };
    
                    resetSequenceNumbers();
                    flatpickr('.clone-min-timerange-picker', {
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: "H:i",
                    });
                    var row = 0;
                    $('#add_button').on('click', function ()
                    {
                        $(".clone-min-timerange-picker").flatpickr("destroy");
                        var tableBody = $('#table_list').find("tbody");
                        var trLast = tableBody.find("tr:last");
                        
                        trLast.find(".removebtn").show().fadeIn(300);
    
                        var trNew = trLast.clone();
                        row = trNew.attr('row');
                        row++;
    
                        trNew.attr('id','row_'+row).attr('data-id',0).attr('row',row);
                        trNew.find('[type="hidden"]').val(0).attr('data-id',0);
                        trNew.find('[id^="surge_prices_id_no_"]').attr('name',"surge_prices_id["+row+"]").attr('id',"surge_prices_id_no_"+row).val('');
                        trNew.find('[id^="from_time_"]').attr('name',"from_time["+row+"]").attr('id',"from_time_"+row).val('');
                        trNew.find('[id^="to_time_"]').attr('name',"to_time["+row+"]").attr('id',"to_time_"+row).val('');
                        trNew.find('[id^="remove_"]').attr('id',"remove_"+row).attr('row',row);
                        trLast.after(trNew);

                        flatpickr('.clone-min-timerange-picker', {
                            enableTime: true,
                            noCalendar: true,
                            dateFormat: "H:i",
                        });
                        resetSequenceNumbers();
                    });
    
                    $(document).on('click','.removebtn', function()
                    {
                        var row = $(this).attr('row');
                        var delete_row  = $('#row_'+row);
                        var check_exists_id = delete_row.attr('data-id');
                        var total_row = $('#table_list tbody tr').length;
                        var user_response = confirm("Are you sure?");
                        if(!user_response) {
                            return false;
                        }
    
                        if(total_row == 1){
                            $(document).find('#add_button').trigger('click');
                        }
                        if(check_exists_id != 0 ) {
                            {{--  $.ajax({
                                url: "{{ route('article.reference.delete')}}",
                                type: 'post',
                                data: {'id': check_exists_id, '_token': $('input[name=_token]').val()},
                                dataType: 'json',
                                success: function (response) {
                                    if(response['status']) {
                                        delete_row.remove();
                                        showMessage(response.message);
                                    } else {
                                        errorMessage(response.message);
                                    }
                                }
                            });  --}}
                        } else {
                            delete_row.remove();
                        }
                        resetSequenceNumbers();
                    }) 
                });
            })(jQuery);
        </script>
    @endsection
</x-master-layout>
