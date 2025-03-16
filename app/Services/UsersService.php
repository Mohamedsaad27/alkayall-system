<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Role;
use App\Models\User;
use App\Models\Image;
use App\Traits\Upload;
use App\Models\Setting;
use Illuminate\Support\Facades\Hash;

class UsersService
{
    public $activityLogsService;
    public function __construct(ActivityLogsService $activityLogsService) {
        $this->activityLogsService = $activityLogsService;

    }
    use Upload;
    public function insert($request){
        $user = User::create([
            'username'      => $request->username,
            'branch_id'      => $request->branch_id,
            'name'          => $request->name,
            'payment_method' => $request->payment_method,
            'working_hours_count' => $request->working_hours_count ?? 0,
            'salary' => $request->salary ?? 0,
            'vacation_days_count' => $request->vacation_days_count ?? 0,
            'hour_price' => $request->hour_price ?? 0,
            'overtime_hour_price' => $request->overtime_hour_price ?? 0,
            'presence_time' => $request->presence_time ? Carbon::createFromFormat('H:i', $request->presence_time) : null,
            'leave_time' => $request->leave_time ? Carbon::createFromFormat('H:i', $request->leave_time) : null,
            'password'      => Hash::make($request->password),
        ]);
        $user->roles()->attach([$request->role_id]);
        $user->Branches()->sync($request->branch_ids);
        $roles = Role::whereIn('id', [$request->role_id])->pluck('name')->toArray(); 
        $roleNames = implode(', ', $roles);  
        return $user;
    }

    public function update($user, $request){
        
        if($request->password == NULL){
            $password = $user->password;
        } else{
            $password = Hash::make($request->password);
        }

        $user->username       = $request->username;
        $user->branch_id       = $request->branch_id;
        $user->name           = $request->name;
        $user->password       = $password;
        $user->payment_method = $request->payment_method;
        $user->working_hours_count = $request->working_hours_count ;
        $user->salary = $request->salary;
        $user->vacation_days_count = $request->vacation_days_count;
        $user->hour_price = $request->hour_price;
        $user->overtime_hour_price = $request->overtime_hour_price;
        $user->presence_time  = $request->presence_time ? Carbon::createFromFormat('H:i', $request->presence_time) : $user->presence_time;
        $user->leave_time     = $request->leave_time ? Carbon::createFromFormat('H:i', $request->leave_time) : $user->leave_time;
        $user->save();

        $user->Branches()->sync($request->branch_ids);
        if($request->role_id){
            $user->roles()->detach([$user->getRoleId()]);
            $user->roles()->attach([$request->role_id]);
        }
        $roles = Role::whereIn('id', [$request->role_id])->pluck('name')->toArray();  
        $roleNames = implode(', ', $roles);
      
    }

    public function update_user_image($user,$image){
        $path = $this->uploadImage($image, 'uploads/users', 660);

        if($user->Image == null){
            //if user don't have image 
            Image::create([
                'imageable_id'   => $user->id,
                'imageable_type' => 'App\Models\User',
                'src'            => $path,
            ]);

        } else {
            //ig user have image
            $oldImage = $user->Image->src;

            if(file_exists(base_path('public/uploads/users/') . $oldImage)  && $oldImage) {
               
                unlink(base_path('public/uploads/users/') . $oldImage);
            }
         

            $user->Image->src = $path;
            $user->Image->save();
        }
    }
}