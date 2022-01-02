<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Hash;
use Session;
use App\Models\User;
use App\Models\Email;
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
        $newUser = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verification_token' => $emailConfirmationToken
        ]);

        $email = new Email();
        $email->email_address = $data['email'];
        $email->email_verification_token = $emailConfirmationToken;
        $email->user_id = $newUser->id;
        $email->save();
        
        $to_name = $data['name'];
        $to_email = $data['email'];
        $emailContent = array('name'=>$data['name'], 'body' => 'Jūs esat reģistrējies sistēmā "Seko Saeimai". Ja šo darbību tik tiešām veicāt jūs, lūdzu, apstipriniet to, atverot šo saiti: http://localhost:8000/confirm-email?address='.$data['email'].'&token='.$emailConfirmationToken);

        Mail::send("emails.mail", $emailContent, function($message) use ($to_name, $to_email) {
            $message->to($to_email, $to_name)->subject("Apstipriniet savu reģistrāciju 'Seko Saeimai'!");
            $message->from("seko.saeimai@gmail.com","Seko Saeimai");
        });
        
        return redirect("/");
    }    

    public function confirmUserEmailAddress(Request $request) {
        $user = User::where('email', $request->get('address'))->where('email_verification_token', $request->get('token'))->whereNull('email_verified_at')->first();
        if (!empty($user)) {
            $user->email_verified_at = date('Y-m-d H:i:s');
            $user->save();

            $email = Email::where('email_address', $user->email)->where('user_id', $user->id)->where('email_verification_token', $request->get('token'))->first();
            if (!empty($email)) {
                $email->email_verification_time = date('Y-m-d H:i:s');
                $email->save();
            }
            return redirect("/")->withSuccess('Lietotāja '.$user->email.' e-pasta adrese veiksmīgi apstiprināta.');
        } else {
            return redirect("/")->withErrors('Notika kļūda. Šāds lietotājs neeksistē, šis lietotājs jau ir apstiprinājis savu e-pasta adresi vai arī e-pasta adreses apstiprināšanas saite nav pareiza.');
        }
    }

    public function changePassword() {
        if(Auth::check()) {
            if (empty(Auth::user()->email_verified_at) || Auth::user()->email_verified_at->format('Y-m-d H:i:s') > date('Y-m-d H:i:s'))
                return view('subscriptions')->withErrors(['error1' => 'Lūdzu, apstipriniet savu e-pasta adresi, pirms lietot "Seko Saeimai". Uz norādīto e-pasta adresi esam nosūtījuši apstiprinājuma saiti. Pārbaudi, vai tā nav iekritusi mēstuļu sadaļā!']);
        
            return view('user.change_password');
        }
        return redirect("/")->withErrors(['error1' => 'Jums nav piekļuves.']);

    }

    public function updatePassword(Request $request) {
        if(Auth::check()) {
            if (empty(Auth::user()->email_verified_at) || Auth::user()->email_verified_at->format('Y-m-d H:i:s') > date('Y-m-d H:i:s'))
                return view('subscriptions')->withErrors(['error1' => 'Lūdzu, apstipriniet savu e-pasta adresi, pirms lietot "Seko Saeimai". Uz norādīto e-pasta adresi esam nosūtījuši apstiprinājuma saiti. Pārbaudi, vai tā nav iekritusi mēstuļu sadaļā!']);
            
            $request->validate([
                'oldPassword' => 'required',
                'newPassword' => 'required|string|min:8',
                'newPasswordRepeat' => 'required|same:newPassword',
            ]);
    
            $user = Auth::user();
    
            if (!Hash::check($request->oldPassword, $user->password)) {
                return redirect("change-password")->withErrors(['error1' => 'Līdzšinējā parole nav pareiza.']);
            }
    
            $user->password = Hash::make($request->newPassword);
            $user->save();
    
            return redirect("change-password")->withSuccess('Parole nomainīta.');
        }
        return redirect("/")->withErrors(['error1' => 'Jums nav piekļuves.']);
    }
    

    public function signOut() {
        Session::flush();
        Auth::logout();
  
        return Redirect('/');
    }

}
