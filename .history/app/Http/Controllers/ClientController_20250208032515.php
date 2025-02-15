<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Contracts\Service\Attribute\Required;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Mail;
use App\Mail\Websitemail;
use App\Models\Client;

class ClientController extends Controller
{
    public function ClientLogin(){
        return view('client.client_login');
    }
    // End Method

    public function ClientRegister(){
        return view('client.client_register');
    }
    // End Method

    public function ClientLoginSubmit(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $check = $request->all();

        $data = [
            'email' => $check['email'],
            'password' => $check['password']
        ];

        if(Auth::guard('client')->attempt($data)){
            return redirect()->route('admin.dashboard')->with('success',
            'Login Successfully');
        }else{
            return redirect()->route('client.login')->with('error',
            'Invalid Credentials');
        }
    }
    //End Method

    public function ClientLogout(){
        Auth::guard('client')->logout();
        return redirect()->route('client.login')->with('success',
        'Logout Successfully');
    }
    //End Method


    public function ClientForgetPassword(){
        return view('client.forget_password');
    }
    //End Method


    public function ClientPasswordSubmit(Request $request){
        $request->validate([
            'email' => 'required|email',
        ]);

        $admin_data = Client::where('email',$request->email)->first();

        if(!$admin_data){
            return redirect()->back()->with('error','Email Not Found');
        }

        $token = hash('sha256', time());
        $admin_data->token= $token;
        $admin_data->update();


        $reset_link = url('client/reset_password/'.$token.'/'.$request->email);
        $subject = "Reset Password";
        $message = "Please Click on below link to reset password<br>";
        $message .= "<a href='".$reset_link." '> Click Here </a>";


        Mail::to($request->email)->send(new Websitemail($subject, $message));
        return redirect()->back()->with('success','Reset Password Link Send On Your Mail');

    }
    //End Method

    public function ClientResetPassword($token,$email){

        $client_data = Client::where('email',$email)->where('token',$token)->first();

        if(!$client_data){
            return redirect()->route('client.login')->with('error','Invalid Token or Email');
        }

        return view('client.reset_password',compact('token','email'));

    }
    //End Method

    public function ClientResetPasswordSubmit(Request $request){
        $request->validate([
            'password' => 'required',
            'password_confirmation' => 'required|same:password',
        ]);

        $client_data = Client::where('email',$request->email)->where('token',$request->token)->first();
        $client_data->password = Hash::make($request->password);
        $client_data->token = "";
        $client_data->update();


        return redirect()->route('client.login')->with('success','Reset Password Successfully');

    }
    //End Method

    public function ClientProfile(){
        $id = Auth::guard('client')->id();
        $profileData = Client::find($id);
        return view('client.client_profile', compact('profileData'));
    }
    //End Method


    public function ClientProfileStore(Request $request){
        $id = Auth::guard('client')->id();
        $data = Client::find($id);
        
        $data->name = $request->name;
        $data->email = $request->email;
        $data->phone = $request->phone;
        $data->address = $request->address;
        $oldPhotoPath = $data->photo;
        
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');

            //generate file name of the inserted photo for admin
            $filename = time().'.'.$file-> getClientOriginalExtension();

            // photo upload hbe puclic/upload/admin_images folder e
            $file->move(public_path('upload/admin_images'), $filename);
            $data-> photo = $filename;

            // folder theke ager chobi soraiye new chobi rakha
            if ($oldPhotoPath && $oldPhotoPath !== $filename) {
                $this->deleteOldImage($oldPhotoPath);
            }
        }        
        

        $data->save();
        $notification = array(
            'message' => 'Profile Update Successfully',
            'alert-type' => 'success'
        );
        return redirect()->back()->with($notification);
    }
    //End Method

    private function deleteOldImage(string $oldPhotoPath): void{
        $fullPath = public_path('upload/admin_images/'.$oldPhotoPath);

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
    // End Private Method


    public function AdminChangePassword(){
        $id = Auth::guard('admin')->id();
        $profileData = Client::find($id);
        return view('admin.admin_change_Password', compact('profileData'));
    }
    //End Method

    public function AdminPasswordUpdate(Request $request){
        $admin = Auth::guard('admin')->user();
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required | confirmed',

        ]);

        if (!Hash::check($request->old_password, $admin->password)) {
            $notification = array(
                'message' => 'Old Password Does not Match!',
                'alert-type' => 'error'
            );
            return back()->with($notification);
        }


        //Update the new password
        Client::whereId($admin->id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        $notification = array(
            'message' => 'Password Change Successfully !',
            'alert-type' => 'success'
        );
        return back()->with($notification);

    }
    //End Method
}
