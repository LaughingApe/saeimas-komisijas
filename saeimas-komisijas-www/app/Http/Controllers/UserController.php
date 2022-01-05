<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Hash;
use Session;
use App\Models\User;
use App\Models\Email;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Mail;

class UserController extends Controller // Based on tutorial published here: https://www.positronx.io/laravel-custom-authentication-login-and-registration-tutorial/
{
    public function index()
    {
        return view('user.login');
    }

    // Authenticate user
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
  
        return redirect("/")->withErrors(['error1' => 'E-pasta adrese un/vai parole ir nepareiza.']);
    }

    public function registration()
    {
        return view('user.registration');
    }
      
    // Register a new user
    public function registerUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'password-repeat' => 'required|same:password',
        ], [
            'name.required' => 'Vārds ir obligāts.',
            'email.required' => 'E-pasta adrese ir obligāta.',
            'email.unique' => 'E-pasta adrese jau ir aizņemta.',
            'email.email' => 'Ievadiet derīgu e-pasta adresi.',
            'password.required' => 'Parole ir obligāta.',
            'password.min' => 'Parolei jābūt vismaz :min simbolus garai.',
            'password-repeat.required' => 'Ievadiet paroli vēlreiz.',
            'password-repeat.same' => 'Parolei un atkārtotas paroles laukam jābūt vienādiem.',
        ]);

        if ($validator->fails()) {
            return redirect('registration')->withErrors($validator->errors());
        };
           
        $data = $request->all();

        $emailConfirmationToken = uniqid(); // The token is randomly generated
        $newUser = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verification_token' => $emailConfirmationToken
        ]);

        // Immediately create user email address also as their list of email addresses that they can subscribe from
        $email = new Email();
        $email->email_address = $data['email'];
        $email->email_verification_token = $emailConfirmationToken;
        $email->user_id = $newUser->id;
        $email->save();
        
        // Send email so that they can confirm they are the owner of this email address
        $to_name = $data['name'];
        $to_email = $data['email'];
        $emailContent = array('name'=>$data['name'], 'body' => 'Jūs esat reģistrējies sistēmā "Seko Saeimai". Ja šo darbību tik tiešām veicāt jūs, lūdzu, apstipriniet to, atverot šo saiti: http://localhost:8000/confirm-email?address='.$data['email'].'&token='.$emailConfirmationToken);

        Mail::send("emails.mail", $emailContent, function($message) use ($to_name, $to_email) {
            $message->to($to_email, $to_name)->subject("Apstipriniet savu reģistrāciju 'Seko Saeimai'!");
            $message->from("seko.saeimai@gmail.com","Seko Saeimai");
        });
        
        return redirect("/")->withSuccess('Lietotājs reģistrēts. Lai pabeigtu reģistrāciju, atveriet uz e-pasta adresi '.$to_email.' nosūtīto apstiprināšanas saiti!');
    }    

    // Confirm user email address (using link sent to email)
    public function confirmUserEmailAddress(Request $request) {
        $user = User::where('email', $request->get('address'))->where('email_verification_token', $request->get('token'))->whereNull('email_verified_at')->first(); // Find the user by email address and token
        if (!empty($user)) {
            $user->email_verified_at = date('Y-m-d H:i:s'); // Save verification time
            $user->save();

            // Also confirm the same email address in the subscription email list
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

    // Change user password
    public function updatePassword(Request $request) {
        if(Auth::check()) {
            if (empty(Auth::user()->email_verified_at) || Auth::user()->email_verified_at->format('Y-m-d H:i:s') > date('Y-m-d H:i:s'))
                return view('subscriptions')->withErrors(['error1' => 'Lūdzu, apstipriniet savu e-pasta adresi, pirms lietot "Seko Saeimai". Uz norādīto e-pasta adresi esam nosūtījuši apstiprinājuma saiti. Pārbaudi, vai tā nav iekritusi mēstuļu sadaļā!']);
                
            $user = Auth::user();

            // Check if current passwords match
            if (!Hash::check($request->oldPassword, $user->password)) {
                return redirect("change-password")->withErrors(['oldPassword' => 'Līdzšinējā parole nav pareiza.']);
            }

            $validator = Validator::make($request->all(), [
                'oldPassword' => 'required',
                'newPassword' => 'required|min:8',
                'newPasswordRepeat' => 'required|same:newPassword',
            ], [
                'oldPassword.required' => 'Līdzšinējā parole ir obligāta.',
                'newPassword.required' => 'Jaunā parole ir obligāta.',
                'newPassword.min' => 'Parolei jābūt vismaz :min simbolus garai..',
                'newPasswordRepeat.required' => 'Parole ir obligāta.',
                'newPasswordRepeat.same' => 'Parolei un atkārtotas paroles laukam jābūt vienādiem.',
            ]);
    
            if ($validator->fails()) {
                return redirect('change-password')->withErrors($validator->errors());
            };

    
            // Save new password
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
