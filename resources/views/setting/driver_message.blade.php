{{ Form::model($driverMessage,['method' => 'POST', 'route'=> 'saveLanguageDriverMessage', 'data-toggle' => 'validator'] ) }}
    <input type="hidden" value="{{ $message_type }}" name="message_type"/>
    <input type="hidden" value="{{ $requestLang }}" name="requestLang"/>
    <table class="table language_table table-sm table-fixed">
        <thead>
            <tr>
                <th scope="col">{{ __('message.key') }}</th>
                <th scope="col">{{ __('message.value') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($driverMessage as $key => $val)
               <tr>
                   <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                   <td><input class="form-control" name="{{ $key }}" value="{{ $val }}" /></td>
               </tr>
            @endforeach
        </tbody>
    </table>
{{ Form::submit( __('message.save'), ['class' => 'btn btn-md btn-primary float-right']) }}
{{ Form::close() }}