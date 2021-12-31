<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Hash;
use Session;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Mail;

class UserController extends Controller // Based on tutorial published here: https://www.positronx.io/laravel-custom-authentication-login-and-registration-tutorial/
{
    public function index()
    {
        return view('user.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);
   
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials)) {
            return redirect()->intended('subscriptions');
        }
  
        return redirect("/")->withSuccess('E-pasta adrese un/vai parole ir nepareiza.');
    }

    public function registration()
    {
        return view('user.registration');
    }
      

    public function registerUser(Request $request)
    {  
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'password-repeat' => 'required|same:password',
        ]);
           
        $data = $request->all();

        $emailConfirmationToken = uniqid();
        $check = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verification_token' => $emailConfirmationToken
        ]);
        
        $to_name = $data['name'];
        $to_email = $data['email'];
        $emailContent = array('name'=>$data['name'], 'body' => 'Jūs esat reģistrējies sistēmā "Seko Saeimai". Ja šo darbību tik tiešām veicāt jūs, lūdzu, apstipriniet to, atverot šo saiti: http://localhost:8000/confirm-email?address='.$data['email'].'&token='.$emailConfirmationToken);

        Mail::send("emails.mail", $emailContent, function($message) use ($to_name, $to_email) {
            $message->to($to_email, $to_name)->subject("You have signed in");
            $message->from("seko.saeimai@gmail.com","Seko Saeimai");
        });
        
        return redirect("subscriptions");
    }    

    public function confirmUserEmailAddress(Request $request) {
        $user = User::where('email', $request->get('address'))->where('email_verification_token', $request->get('token'))->whereNull('email_verified_at')->first();
        if (!empty($user)) {
            $user->email_verified_at = date('Y-m-d H:i:s');
            $user->save();
            return redirect("/")->withSuccess('Lietotāja '.$user->email.' e-pasta adrese veiksmīgi apstiprināta.');
        } else {
            return redirect("/")->withErrors('Notika kļūda. Šāds lietotājs neeksistē, šis lietotājs jau ir apstiprinājis savu e-pasta adresi vai arī e-pasta adreses apstiprināšanas saite nav pareiza.');
        }
    }
    

    public function signOut() {
        Session::flush();
        Auth::logout();
  
        return Redirect('/');
    }

}
