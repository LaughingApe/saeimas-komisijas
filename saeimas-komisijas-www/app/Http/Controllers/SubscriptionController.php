<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Commission;
use App\Models\Email;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

            $subscriptionMatrix = [];

            foreach ($commissions as $c) {
                $subscriptionMatrix[$c->id] = [];
                foreach ($emails as $e) {
                    $subscriptionMatrix[$c->id][$e->id] = false;
                }
            }

            $subscriptions = DB::table('subscriptions')->join('emails', 'subscriptions.email_id', '=', 'emails.id')->where('emails.user_id', Auth::user()->id)->get();
            
            foreach ($subscriptions as $s) {
                $subscriptionMatrix[$s->commission_id][$s->email_id] = true;
            }

            return view('subscriptions', [
                'commissions' => $commissions,
                'emails' => $emails,
                'subscriptions' => $subscriptionMatrix
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
            
            return redirect("subscriptions")->withSuccess('E-pasta adrese pievienota. Uz šo e-pastu ir nosūtīta saite, uz kuras nospiežot, varat apstiprināt, ka esat šī e-pasta īpašnieks.');
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

    
    public function deleteEmailAddress(Request $request, $email_id) {
        if(Auth::check()) {
            if (empty(Auth::user()->email_verified_at) || Auth::user()->email_verified_at->format('Y-m-d H:i:s') > date('Y-m-d H:i:s'))
                return view('subscriptions')->withErrors(['error1' => 'Lūdzu, apstipriniet savu e-pasta adresi, pirms lietot "Seko Saeimai". Uz norādīto e-pasta adresi esam nosūtījuši apstiprinājuma saiti. Pārbaudi, vai tā nav iekritusi mēstuļu sadaļā!']);
        
            $email = Email::find($email_id);

            if ($email == null) {
                return redirect('subsciptions')->withErrors(['error1' => 'Kļūda. E-pasta adrese netika atrasta.']);
            }

            Subscription::where('email_id', $email_id)->delete();
            $email->delete();
        } else {
            $emailAddress = $request->get('email-address');
            $emailToken = $request->get('token');
            
            if (empty($emailAddress) || empty($emailToken)) {
                return redirect('/')->withErrors(['error1' => 'Kļūda. E-pasta adresi neizdevās dzēst.']);
            }

            $email = Email::where('id', $email_id)->where('email_address', $emailAddress)->where('email_verification_token', $emailToken)->first();

            if ($email == null) {
                return redirect('/')->withErrors(['error1' => 'Kļūda. E-pasta adresi neizdevās dzēst.']);
            }


            Subscription::where('email_id', $email_id)->delete();
            $email->delete();
        }
        return redirect("/subscriptions")->withSuccess('E-pasta adrese '.$email->email_address.' noņemta.');
    }

    public function storeSubscriptions(Request $request)
    {
        if(Auth::check()) {
            if (empty(Auth::user()->email_verified_at) || Auth::user()->email_verified_at->format('Y-m-d H:i:s') > date('Y-m-d H:i:s'))
                return view('subscriptions')->withErrors(['error1' => 'Lūdzu, apstipriniet savu e-pasta adresi, pirms lietot "Seko Saeimai". Uz norādīto e-pasta adresi esam nosūtījuši apstiprinājuma saiti. Pārbaudi, vai tā nav iekritusi mēstuļu sadaļā!']);

                $commissions = Commission::all();

                foreach ($commissions as $c) {
                    if ($request->has('submit-'.$c->id)) {
                        
                        $emails = Email::where('user_id', Auth::user()->id)->get();

                        foreach ($emails as $e) {
                            $this->setSubscriptionStatus($e->id, $c->id, $request->has($c->id.':'.$e->id));
                        }
                        break;
                    }
                }


            return redirect("subscriptions");
        }
        return redirect("/")->withErrors(['error1' => 'Jums nav piekļuves.']);
    }

    private function setSubscriptionStatus($emailId, $commissionId, $status) {
        $subscription = Subscription::where('email_id', $emailId)->where('commission_id', $commissionId)->first();
        
        if ($status) { // Subscription must be on
            if (empty($subscription)) {
                $newSubscription = new Subscription();
                $newSubscription->email_id = $emailId;
                $newSubscription->commission_id = $commissionId;
                $newSubscription->save();
            }
        } else { // Subscription must be off
            if (!empty($subscription))
                $subscription->delete();
        }
    }
}