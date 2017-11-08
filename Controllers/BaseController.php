<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Auth;
use App\Models\User;
use Statickidz\GoogleTranslate;

class BaseController extends Controller
{
    public $auth;
    
	function __construct()
	{
        $this->middleware(function($request, $next){
            $this->auth = Auth::guard('api')->user();
            return $next($request);
        });
        app()->setLocale(request('locale')); 
	}

    /*
     * google translate api for translate  city
     */
    public function getCityTranslate($locale , $text)
    {  
        if($locale === 'ar') {
            $trans  = new GoogleTranslate();
            $result = $trans->translate('en', $locale, $text);

            return $result;
        }

        return $text;
    }
    
    public function getCityAndCountry($latitude, $longitude)
    {
        if($latitude > 0 && $longitude > 0) {
            $map        = $this->getMap();
            $str        = $map->formatted_address;
            $strToArray = explode(',', $str);
            $reserved   = array_reverse($strToArray);
            $text       = $reserved[1] .' - '. $reserved[0];
            $city       = $this->getCityTranslate(request('locale'), $text); 

            return $city;
        }

        return '';
    }

    protected function getMap()
    {
        $url = 'http://maps.googleapis.com/maps/api/geocode/json?latlng='.$latitude.','.$longitude.'&sensor=false';
        $url = json_decode(@file_get_contents($url));
        return $url->results[0];
    }

    /*
     * get nearest resturant from long & lat from user
     */
    public function getNearestRestaurants($latitude, $longitude , $num)
    {
        if($latitude > 0 && $longitude > 0) {
            $max_distance = 13;
            $lat_small = $latitude - ($max_distance * 0.018);
            $lat_large = $latitude + ($max_distance * 0.018);
            $lng_small = $longitude - ($max_distance * 0.018);
            $lng_large = $longitude + ($max_distance * 0.018);

            $restaurants = User::whereType('2')->whereBetween('latitude', [$lat_small, $lat_large])->whereBetween('longitude', [$lng_small, $lng_large])->paginate($num);
            return $restaurants;
        }

        return '' ;
    }

    /*
     * convert response to string
     */
    public function response($status, $response = [])
    {
        $response = $this->convertToString($response);
    	return response()->json(
            'status'   => $status,
            'response' => $response
        ]);
    }

    public function convertToString($response)
    {
        $response = collect($response)->map(function($o){
            if (is_array($o) || is_object($o)) 
                return $this->convertToString($o);
            return (string)$o;            
        });
        return $response;
    }

    /**
     * validation
     * @param  array  $rules            rules
     * @param  array  $messages         custom validation message
     * @param  array  $customAttributes attribue names
     * @return Instance Of validation response
     */
	public function validator(array $rules, array $messages = [], array $customAttributes = [])
    {
    	return Validator::make(request()->all(), $rules, $messages, $customAttributes);
    }


}
