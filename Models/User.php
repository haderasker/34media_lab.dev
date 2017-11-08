<?php
namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\Auth\ResetUserPassword;
use Auth;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name','email','phone','password','status','type',
        'image','cover','api_token','reset_password_code',
        'description','working_time','latitude','longitude',
        'android_token','ios_token','temp_phone_code',
    ];

    protected $attributes = [
        'image'  => 'img/default-profile-picture.png',
        'cover'  => 'img/default-cover-profile.jpg',
        'status' => 1,
    ];

    protected $appends = ['rate', 'is_favroited'];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /*
     * get rate per resturant
     */
    public function getRateAttribute()
    {
        $rate = CommentRate::where('resturant_id', $this->id)->avg('rate');
        if($rate) {
          return $rate;   
        }
       
        return '0';
    }

    /*
     * get favorite resturant per user
     */
    public function getIsFavroitedAttribute()
    {
        $user_id     = Auth::guard('api')->user()->id;
        $is_favorite = Favorite::where('resturant_id', $this->id)
                        ->where('user_id', $user_id )
                        ->first();

        if($is_favorite) {
          return '1';   
        }

        return '0';
    }

    /*
     * store image profile per user
     */
    public function setImageAttribute($image)
    {
        if (!$image) {
            return;
        }

        $image = request()->file('image')->store('uploads');
        $this->attributes['image'] = $image;
    }

    /*
     * get activate user or resturant if status 1
     */
    public function scopeActive($query)
    {
        $query->where('status', 1);
    }

    /*
     * get users if type 1
     */
    public function scopeClient($query)
    {
        $query->whereType(1);
    }

    /*
     * get activate users if status 1 & type 1
     */
    public function scopeActiveClient($query)
    {
        return $query->active()->client();
    }

    /*
     * get activate users if status 1
     */
    public function scopeRestaurant($query)
    {
        $query->whereType(2);
    }
}
