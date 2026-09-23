<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;


class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'full_contact_number' => $this->country_code . $this->contact_number,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $user_id = auth()->user()->id ?? request()->id;
        $user_type = auth()->user()->user_type ?? request()->user_type;
   // if used from api endpint profile should string and if used from web endpint profile should image and file
        $profile_image = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:1024';
        $rules = [
            'username'  => [
                'required',
                Rule::unique('users', 'username')->where(fn ($q) => $q->where('user_type', $user_type))->ignore($user_id),
            ],
            'email'     => [
                'required',
                'email',
                Rule::unique('users', 'email')->where(fn ($q) => $q->where('user_type', $user_type))->ignore($user_id),
            ],
            'contact_number' => 'nullable|max:20',
            'full_contact_number' => [
                'nullable',
                Rule::unique('users', 'contact_number')->where(fn ($q) => $q->where('user_type', $user_type))->ignore($user_id),
            ],
            'profile_image' => $profile_image,
            'device_type' => [
                Rule::requiredIf($user_type === 'rider'),
                'string'
            ],
            'device_id' => [
                Rule::requiredIf($user_type === 'rider'),
                'string'
            ]
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'userProfile.dob.*'  => __('message.dob_required'),
            'profile_image.image' => __('message.profile_image_invalid'),
            'profile_image.mimes' => __('message.profile_image_mimes'),
            'profile_image.max' => __('message.profile_image_max'),
            //'contact_number.required' => 'Mobile number is required.',
            'full_contact_number.unique' => __('message.mobile_number_taken'),
        ];
    }

     /**
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator) {
        $data = [
            'status' => true,
            'message' => $validator->errors()->first(),
            'all_message' =>  $validator->errors()
        ];

        if ( request()->is('api*')){
           throw new HttpResponseException( response()->json($data,422) );
        }

        if ($this->ajax()) {
            throw new HttpResponseException(response()->json($data,422));
        } else {
            throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
        }
    }
}
