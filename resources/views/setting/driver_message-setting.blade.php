<?php
    $language_option = appSettingData('get')->language_option;
    $language_array = languagesArray($language_option);
?>
<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label for="language_option" class="form-control-label">{{ __('message.language_option') }}</label>
            <select class="form-control select2js" name="language_option" id='change_language'>
                @if(count($language_array) > 0)
                    @foreach( $language_array  as $lang )
                        <option value="{{$lang['id']}}" {{ config('app.locale') == $lang['id']  ? 'selected' : '' }} >{{$lang['title']}}</option>
                    @endforeach
                @endif
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label for="message_type" class="form-control-label">Message Type</label>
            <select class="form-control select2js" name="message_type" id="message_type">
                <option value="pick_up" >Pick Up</option>
                <option value="post_ride" >Post Ride</option>
                <option value="special_situations" >Special Situations</option>
                <option value="rider_cancellation_reasons" >Cancellation Reasons for Passengers</option>
                <option value="driver_cancellation_reasons" >Cancellation Reasons for Drivers</option>
                <option value="general_cancellation_options" >General Cancellation Options</option>
                <option value="early_completion_reasons" >Early Completion Reasons</option>
            </select> 
        </div>
    </div>
    <div class="col-md-12">
        <div class="language-section">
            
        </div>
    </div>
</div>
<script>
    function getLanguageDriverMessage(lang = '', message_type = ''){
        var url = "{{ route('getLanguageDriverMessage') }}";
        $.ajax({
            type: 'post',
            url: url,
            data: {
                'lang': lang,
                'message_type': message_type
            },
            success: function(res){
                $('.language-section').html(res);
            }
        });
    }
    $(document).ready(function (){
        $('.select2js').select2();
        let selectedLang = $("#change_language :selected").val();
        let messageType = $('#message_type :selected').val();

        //console.log("selectedLang1:"+selectedLang+", messageType1:"+messageType);
        getLanguageDriverMessage(selectedLang,messageType)
        $(document).on('change','#change_language',function() {
            let selectedLang = $("#change_language :selected").val();
            let messageType = $('#message_type :selected').val();
            //console.log("selectedLang2:"+selectedLang+", messageType2:"+messageType);
            getLanguageDriverMessage(selectedLang,messageType)
        });

        $(document).on('change','#message_type',function() {
            let selectedLang = $("#change_language :selected").val();
            let messageType = $('#message_type :selected').val();
            //console.log("selectedLang3:"+selectedLang+", messageType3:"+messageType);
            getLanguageDriverMessage(selectedLang,messageType)
        });        
    });
</script>