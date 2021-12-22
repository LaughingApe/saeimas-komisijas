<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Commission;
use App\Models\Email;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Mail;

class SubscriptionController extends Controller
{
    public function subscriptions()
    {
        if(Auth::check()) {
            if (empty(Auth::user()->email_verified_at) || Auth::user()->email_verified_at->format('Y-m-d H:i:s') > date('Y-m-d H:i:s'))
                return view('subscriptions')->withErrors(['error1' => 'Lūdzu, apstipriniet savu e-pasta adresi, pirms lietot "Seko Saeimai". Uz norādīto e-pasta adresi esam nosūtījuši apstiprinājuma saiti. Pārbaudi, vai tā nav iekritusi mēstuļu sadaļā!']);

            $emails = Email::where('user_id', Auth::user()->id)->get();
            $commissions = Commission::all();


            return view('subscriptions', [
                'commissions' => $commissions,
                'emails' => $emails
            ]);
        }
        return redirect("/")->withErrors(['error1' => 'Jums nav piekļuves.']);
    }

    public function createEmailAddress()
    {
        if(Auth::check()) {
            if (empty(Auth::user()->email_verified_at) || Auth::user()->email_verified_at->format('Y-m-d H:i:s') > date('Y-m-d H:i:s'))
                return view('subscriptions')->withErrors(['error1' => 'Lūdzu, apstipriniet savu e-pasta adresi, pirms lietot "Seko Saeimai". Uz norādīto e-pasta adresi esam nosūtījuši apstiprinājuma saiti. Pārbaudi, vai tā nav iekritusi mēstuļu sadaļā!']);

            return view('create_email_address');
        }
        return redirect("/")->withErrors(['error1' => 'Jums nav piekļuves.']);
    }

    public function storeEmailAddress(Request $request)
    {
        if(Auth::check()) {
            if (empty(Auth::user()->email_verified_at) || Auth::user()->email_verified_at->format('Y-m-d H:i:s') > date('Y-m-d H:i:s'))
                return view('subscriptions')->withErrors(['error1' => 'Lūdzu, apstipriniet savu e-pasta adresi, pirms lietot "Seko Saeimai". Uz norādīto e-pasta adresi esam nosūtījuši apstiprinājuma saiti. Pārbaudi, vai tā nav iekritusi mēstuļu sadaļā!']);

            $existingEmail = Email::where('email_address', $request->email)->where('user_id', Auth::user()->id)->first();

            if (!empty($existingEmail)) {
                return view('create_email_address')->withErrors(['error1' => 'Jūs jau esat reģistrējis šādu e-pasta adresi.']);
            }

            $email = new Email();
            
            $emailConfirmationToken = uniqid();
            $email->email_address = $request->email;
            $email->email_verification_token = $emailConfirmationToken;
            $email->user_id = Auth::user()->id;

            $email->save();
            
            $to_name = Auth::user()->name;
            $to_email = $request->email;
            $emailContent = array('name'=>Auth::user()->name, 'body' => 'Lietotājs '.Auth::user()->email.' šo e-pastu reģistrēja lietošanai sistēmā "Seko Saeimai". Ja šo darbību tik tiešām veicāt jūs, lūdzu, apstipriniet to, atverot šo saiti: http://localhost:8000/confirm-email-address?address='.$request->email.'&token='.$emailConfirmationToken);
            
            Mail::send("emails.mail", $emailContent, function($message) use ($to_name, $to_email) {
                $message->to($to_email, $to_name)->subject("E-pasta adrese reģistrēta 'Seko Saeimai'");
                $message->from("seko.saeimai@gmail.com","Seko Saeimai");
            });
            
            return redirect("subscriptions");
        }
        return redirect("/")->withErrors(['error1' => 'Jums nav piekļuves.']);
    }

    public function confirmEmailAddress(Request $request) {
        $email = Email::where('email_address', $request->get('address'))->where('email_verification_token', $request->get('token'))->whereNull('email_verification_time')->first();
        if (!empty($email)) {
            $email->email_verification_time = date('Y-m-d H:i:s');
            $email->save();
            return redirect("/")->withSuccess('E-pasta adrese '.$email->email_address.' veiksmīgi apstiprināta.');
        } else {
            return redirect("/")->withErrors('Notika kļūda. Apstiprināmā e-pasta adrese jau ir apstiprināta, neeksistē vai saite ir nepareiza.');
        }
    }
}