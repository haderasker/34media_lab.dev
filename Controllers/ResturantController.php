<?php

namespace App\Http\Controllers\Api\V1\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use App\Models\User;
use Hash , Auth;
use Illuminate\Pagination\Paginator;

class ResturantController extends BaseController
{

    function __construct()
    {
        parent::__construct();
        $this->model = new User;
    }

    /**
     * get all resturant
     *
     * 
     */
    public function index(Request $request)
    {
        $latitude        = $request->input('latitude');
        $longitude       = $request->input('longitude');
        $api_token       = $request->input('api_token');

        $validator = $this->validator(array(
            'latitude'=>'required',
            'longitude'=>'required',
         ));
        if($validator->fails())
            return $this->response(false, $validator->errors()->all());
        
        $city_country  = $this->getCityAndCountry($latitude, $longitude);
        $nearest       = $this->getNearestRestaurants($latitude, $longitude , 6);
        $popular       = User::Restaurant()->paginate(6);

        $response = ['city_country' => $city_country, 'next_reservation' => [], 'nearest_restaurants' => $nearest, 'popular_restaurants' => $popular];
        

        return $this->response(true, $response);
    }

    /**
     * get nearest resturant
     *
     * 
     */

    public function nearestResturant(Request $request)
    {
        $latitude        = $request->input('latitude');
        $longitude       = $request->input('longitude');

        $validator = $this->validator(array(
            'latitude'=>'required',
            'longitude'=>'required',
         ));
        if($validator->fails())
            return $this->response(false, $validator->errors()->all());

        $response  = $this->getNearestRestaurants($latitude, $longitude, 10);
 
        return $this->response(true, $response);
    }

    /**
     * get pupular resturant
     *
     * 
     */

    public function pupularResturant()
    {
        

        $response  = User::Restaurant()->paginate(10);

        return $this->response(true, $response);
    }



}