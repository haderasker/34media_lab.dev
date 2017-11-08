<?php
namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $user = $this->route('categories');
        switch ($this->method()) {
            case 'GET':
            case 'DELETE':
            {
                return [];
            }
            case 'POST':
            {
                return [
                    'name'     => 'required|unique:users|alpha_num|min:3',
                    'email'    => 'required|email|unique:users|unique:admins',
                    'phone'    => 'required|regex:/^\+?[0-9]{5,}/|numeric|unique:users',
                    'password' => 'required|regex:((?=.*\\d)(?=.*[A-Z]).{6,6})',
                ];
            }
            case 'PUT':
            case 'PATCH':
            {
                return [
                    'name'  => 'required|alpha_num|min:3|unique:users,name,' . $this->get('id'),
                    'email' => 'required|email|unique:users,email,' . $this->get('id'),
                    'phone' => 'required|regex:/^\+?[0-9]{5,}/|numeric|unique:users,phone,'. $this->get('id'),
                ];
            }
        }
    }
}
