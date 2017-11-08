<?php
namespace App\Http\Controllers\Api\V1\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use App\Models\Favorite;
use Hash;
use Auth;

class UserController extends BaseController
{
    protected $userModel;

    function __construct()
    {
        parent::__construct();
        $this->userModel = new User;
    }

    /*
     * store user
     */
    public function store()
    {
        $validator = $this->validator([
            'name'     => 'required|unique:users|unique:admins|alpha_num|min:3',
            'email'    => 'required|email|unique:users|unique:admins',
            'phone'    => 'required|regex:/^\+?[0-9]{5,}/|numeric|unique:users',
            'password' => 'required|regex:((?=.*\\d)(?=.*[A-Z]).{6,6})',
        ]);

        if($validator->fails())
            return $this->response(false, $validator->errors()->all());

        $activation              = rand(10000, 99999);
        $userModelData           = request(['name', 'email', 'password', 'phone']);
        $userModelData['status'] = $activation;
        $userModelData['type']   = 1;
        $user                    = $this->userModel->create($userModelData);
        $message_activation      = "Activation code " . $activation ;

        /*
         * for test only
         */
        $message = 'Hi ' . request('name') . ',' .
                'welcome to Reservato' .
                'Thank you for choosing Reservato.' .
                $message_activation .
                'Best regards' .
                'Reservato';

        // $this->sendmail(request('email'), $activation);
        $send_mail = mail(request('email'), "Welcome to Reservato", $message);

        //generate api token
        $user = $this->reGenerateApiToken($user);

        return $this->response(true, $user);
    }

    /**
     * log in function
     * @return [type] [description]
     */
    public function login()
    {
        $validator = $this->validator([
            'phone'    => 'required',
            'password' => 'required'
        ]);

        if($validator->fails()) {
            return $this->response(false, $validator->errors()->all());
        }

        $user = $this->userModel->client()->wherePhone(request('phone'))->first();
        if ($user && Hash::check(request('password'), $user->password)) {
            if ($user->status === 0) {
                return $this->response(false, [
                    'message'=>__('lang.your Account suspended')
                ]);
            }

            //generate api token
            $user = $this->reGenerateApiToken($user);
            
            return $this->response(true, $user);
        }

        return $this->response(false, [
            'message'=>__('lang.Invalid credientials')
        ]);
    }

    public function reGenerateApiToken($user)
    {
        $api_token = bcrypt($user->email);
        $user      = $user->update([
            'api_token'    => $api_token,
            'android_token'=> request('android_token'),
            'ios_token'    => request('ios_token'),
        ]);

        return $user;
    }

    /**
     * active account
     */
    public function postActiveAccount()
    {
        $user = $this->auth;

        if(!$user) {
            return $this->response(false, [
                'message'=>__('lang.your Account Is Wrong')
            ]);
        }

        $code = request('code');        
        if ($user->status !== 1 && $user->status === $code) {
            $user->update(['status' => 1]);

            return $this->response(true, $user);
        } else if($user->status === 1) {
            return $this->response(false, ['message'=>__('lang.your Account Already activated')]);
        } else if($user->status !== $code) {
            return $this->response(false, ['message'=>__('lang.Incorrect activation code')]);
        }
    }

  
    /**
     * reset password areas
     */
    public function sendActivationCode()
    {
        $validator = $this->validator([
            'method_activation' => 'required'
        ]);

        if($validator->fails()) {
            return $this->response(false, $validator->errors()->all());
        }

        $reset_password_code = mt_rand(1000,9999);

        $user = $this->userModel->client()->where('email', request('method_activation'))
                                          ->orWhere('phone', request('method_activation'));

        if ($user->get()->isEmpty()) {
            return $this->response(false, [
                'message' => __('lang.Email or Phone is Wrong')
            ]);
        }

    
        $update_reset_code = $user->update([
            'reset_password_code' => $reset_password_code,
            'status'              => $reset_password_code,
        ]);

        if(request('method_activation') == $user->first()->phone || request('method_activation') == $user->first()->email) {

            mail(request('method_activation'), "Reset password - Reservato", "Your code is : $reset_password_code");

            return $this->response(true, [
                'message' => __('lang.Reset password code sent successfully'),
                'code'    => $reset_password_code
            ]);
        }
    }


    public function postResendActivationCode()
    {
        $code  = $this->auth->status;
        $phone = $this->auth->phone;
        $this->sendSms($phone, $code);

        return $this->response(true, [
            'message'=> __('lang.activation code sent')
        ]);
    }


    /**
     * Reset password
     * @return [type] [description]
     */
    public function resetPassword()
    {
        $validator = $this->validator([
            'reset_password_code' => 'required',
            'method_reset'        => 'required',
            'password'            => 'required|regex:((?=.*\\d)(?=.*[A-Z]).{6,6})',
        ]);

        if($validator->fails()) {
            return $this->response(false, $validator->errors()->all());
        }

        $user = $this->userModel->where('reset_password_code' , request('reset_password_code'))
                                ->where('email',request('method_reset'))
                                ->orWhere('phone',request('method_reset'))
                                ->first();

        if ($user) {
            $user->update([
                'password'=>request('password'),
                'reset_password_code'=>'' ,
                'status' => 1

            ]);

            return $this->response(true, $user);
        }

        $errors = [__('lang.Incorrect reset code')];

        return $this->response(false, $errors);
    }


    /**
    * update profile
    */

    public function updateProfile()
    {
        $user = $this->auth;

        if(!$user) {
            return $this->response(false, [
                'message'=>__('lang.your Account Is Wrong')
            ]);
        }

        $user      = $this->auth;
        $validator = $this->validator([
            'email' => 'required|email|unique:admins|unique:users,email,'.$user->id,
            'phone' => 'required|regex:/^\+?[0-9]{5,}/|numeric|unique:users,phone,'.$user->id,
            'image' => 'image|mimes:png,jpeg|max:2048',
         ]);

        if($validator->fails()) {
            return $this->response(false, $validator->errors());
        }

        if (request('phone') != $user->phone || empty($user->phone)) {
            $activation = rand(10000,99999);

            $userModelData = request(['email','image'];
            $userModelData['temp_phone_code'] = $activation;
            $user->update($userModelData);

            $this->sendSms(request('phone'), $activation);
        } else {
            $user->update(request(['email','image']));
        }

        return $this->response(true, $user);
    }

    public function updatePhone()
    {
        $user      = $this->auth;
        $validator = $this->validator([
            'code'  => 'required',
            'phone' => 'required|regex:/^\+?[0-9]{5,}/|numeric|unique:users,phone,' . $user->id,
         ]);

        if($validator->fails()) {
            return $this->response(false, $validator->errors());
        }

        if ($this->auth->temp_phone_code == request('code')) {
            $user->update([
                'phone'=>request('phone'),
                'temp_phone_code' => null
            ]);

            return $this->response(true, $user);
        }

        return $this->response(false, ['message'=>__('lang.Code Is Wrong')]);
    }

    /**
     * update password account 
     */
    public function updatePassword()
    {
        $validator = $this->validator([
            'old_password' => 'required',
            'new_password' => 'required|min:6||regex:((?=.*\\d)(?=.*[A-Z]).{6,6})',
        ]);

        if($validator->fails()) {
            return $this->response(false, $validator->errors());
        }

        $user = $this->auth;

        if(!$user) {
            return $this->response(false, ['message'=>__('lang.your Account Not Recorded')]);
        }
        
        if (Hash::check(request('old_password'), $user->password)) {
            $user->update([
                'password' => request('new_password')
            ]);

            return $this->response(true, [
                'message'=>__('lang.Password Updated')
            ]);
        }

        return $this->response(false,[
            'message'=>__('lang. Old Password Is Wrong')
        ]);
    }
}