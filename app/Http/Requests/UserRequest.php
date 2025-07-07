<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


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

        $rules = [
            'username'  => 'required|unique:users,username,'.$user_id,
            'email'     => 'required|email|unique:users,email,'.$user_id,
            'contact_number' => 'required|max:20',
            'full_contact_number' => 'required|unique:users,contact_number,' . $user_id,
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:1024',
        ];

        return $rules;
    }

    public function messages()
    {
        return [
            'userProfile.dob.*'  =>'DOB is required.',
            'profile_image.image' => 'The profile image must be a valid image file.',
            'profile_image.mimes' => 'Allowed image types: jpeg, png, jpg, gif, webp.',
            'profile_image.max' => 'The profile image must not be larger than 1MB.',
            'contact_number.required' => 'Mobile number is required.',
            'full_contact_number.unique' => 'This mobile number has already been taken.',
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
