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
use Illuminate\Support\Facades\Validator;
use Mail;

class SubscriptionController extends Controller
{
    // The dashboard view for managing subscriptions
    public function subscriptions()
    {
        if(Auth::check()) {
            if (empty(Auth::user()->email_verified_at) || Auth::user()->email_verified_at->format('Y-m-d H:i:s') > date('Y-m-d H:i:s'))
                return view('subscriptions')->withErrors(['error1' => 'Lūdzu, apstipriniet savu e-pasta adresi, pirms lietot "Seko Saeimai". Uz norādīto e-pasta adresi esam nosūtījuši apstiprinājuma saiti. Pārbaudi, vai tā nav iekritusi mēstuļu sadaļā!']);

            $emails = Email::where('user_id', Auth::user()->id)->get(); // Get all email addresses of this user
            $commissions = Commission::all();

            // Build an empty matrix of subscriptions: rows are commissions and columns are email addresses; each cell has a true/false value depending on whether subscription exists
            $subscriptionMatrix = [];
            foreach ($commissions as $c) {
                $subscriptionMatrix[$c->id] = [];
                foreach ($emails as $e) {
                    $subscriptionMatrix[$c->id][$e->id] = false;
                }
            }

            // Get all subscriptions of this user (regardless of email)
            $subscriptions = DB::table('subscriptions')->join('emails', 'subscriptions.email_id', '=', 'emails.id')->where('emails.user_id', Auth::user()->id)->get();
            
            // Fill the subscription matrix with values
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

    // Store a new email address for this user in the database
    public function storeEmailAddress(Request $request)
    {
        if(Auth::check()) {
            if (empty(Auth::user()->email_verified_at) || Auth::user()->email_verified_at->format('Y-m-d H:i:s') > date('Y-m-d H:i:s'))
                return view('subscriptions')->withErrors(['error1' => 'Lūdzu, apstipriniet savu e-pasta adresi, pirms lietot "Seko Saeimai". Uz norādīto e-pasta adresi esam nosūtījuši apstiprinājuma saiti. Pārbaudi, vai tā nav iekritusi mēstuļu sadaļā!']);

            // Don't allow the same email address twice
            $existingEmail = Email::where('email_address', $request->email)->where('user_id', Auth::user()->id)->first();
            if (!empty($existingEmail)) {
                return view('create_email_address')->withErrors(['error1' => 'Jūs jau esat reģistrējis šādu e-pasta adresi.']);
            }

            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ], [
                'required' => 'E-pasta adrese ir obligāta.',
                'email' => 'Ievadiet derīgu e-pasta adresi!',
            ]);
    
            if ($validator->fails()) {
                return redirect('add-email')->withErrors($validator->errors());
            };

            // Create and save the email address
            $email = new Email();
            $emailConfirmationToken = uniqid();
            $email->email_address = $request->email;
            $email->email_verification_token = $emailConfirmationToken;
            $email->user_id = Auth::user()->id;
            $email->save();
            

            // Send an email with confirmation link to the new address
            $to_name = Auth::user()->name;
            $to_email = $request->email;
            $emailContent = array('name'=>Auth::user()->name, 'body' => 'Lietotājs '.Auth::user()->email.' šo e-pastu reģistrēja lietošanai sistēmā "Seko Saeimai". Ja šo darbību tik tiešām veicāt jūs, lūdzu, apstipriniet to, atverot šo saiti: http://localhost:8000/confirm-email-address?address='.$request->email.'&token='.$emailConfirmationToken);
            
            Mail::send("emails.mail", $emailContent, function($message) use ($to_name, $to_email) {
                $message->to($to_email, $to_name)->subject("Apstipriniet e-pasta adresi reģistrēšanai 'Seko Saeimai'!");
                $message->from("seko.saeimai@gmail.com","Seko Saeimai");
            });
            
            return redirect("subscriptions")->withSuccess('E-pasta adrese pievienota. Uz šo e-pastu ir nosūtīta saite, uz kuras nospiežot, varat apstiprināt, ka esat šī e-pasta īpašnieks.');
        }
        return redirect("/")->withErrors(['error1' => 'Jums nav piekļuves.']);
    }

    // Confirm email address (using link sent to the email address)
    public function confirmEmailAddress(Request $request) {
        $email = Email::where('email_address', $request->get('address'))->where('email_verification_token', $request->get('token'))->whereNull('email_verification_time')->first();
        if (!empty($email)) { // If email address with such token found, confirm
            $email->email_verification_time = date('Y-m-d H:i:s'); // Save verification time
            $email->save();
            return redirect("/")->withSuccess('E-pasta adrese '.$email->email_address.' veiksmīgi apstiprināta.');
        } else {
            return redirect("/")->withErrors('Notika kļūda. Apstiprināmā e-pasta adrese jau ir apstiprināta, neeksistē vai saite ir nepareiza.');
        }
    }

    // Delete the email address from subscription list
    public function deleteEmailAddress(Request $request, $email_id) {
        if(Auth::check()) { // If user is authenticated, then specifying email address ID is enough
            if (empty(Auth::user()->email_verified_at) || Auth::user()->email_verified_at->format('Y-m-d H:i:s') > date('Y-m-d H:i:s'))
                return view('subscriptions')->withErrors(['error1' => 'Lūdzu, apstipriniet savu e-pasta adresi, pirms lietot "Seko Saeimai". Uz norādīto e-pasta adresi esam nosūtījuši apstiprinājuma saiti. Pārbaudi, vai tā nav iekritusi mēstuļu sadaļā!']);
        
            $email = Email::where('id', $email_id)->where('user_id', Auth::user()->id)->first();

            if (empty($email)) {
                return redirect('subscriptions')->withErrors(['error1' => 'Kļūda. E-pasta adrese netika atrasta.']);
            }
            Subscription::where('email_id', $email->id)->delete();
            $email->delete();
            return redirect("/subscriptions")->withSuccess('E-pasta adrese '.$email->email_address.' noņemta.');
        } else { // If user hasn't authenticated, then email address and token must be specified, too
            $emailAddress = $request->get('email-address');
            $emailToken = $request->get('token');
            
            if (empty($emailAddress) || empty($emailToken)) { // If email address or token not specified, don't allow deleting
                return redirect('/')->withErrors(['error1' => 'Kļūda. E-pasta adresi neizdevās dzēst.']);
            }

            $email = Email::where('id', $email_id)->where('email_address', $emailAddress)->where('email_verification_token', $emailToken)->first();

            if (empty($email)) {
                return redirect('/')->withErrors(['error1' => 'Kļūda. E-pasta adresi neizdevās dzēst.']);
            }
            Subscription::where('email_id', $email->id)->delete();
            $email->delete();
            return redirect("/")->withSuccess('E-pasta adrese '.$email->email_address.' noņemta.');
        }
    }

    // Store updated subscriptions
    public function storeSubscriptions(Request $request)
    {
        if(Auth::check()) {
            if (empty(Auth::user()->email_verified_at) || Auth::user()->email_verified_at->format('Y-m-d H:i:s') > date('Y-m-d H:i:s'))
                return view('subscriptions')->withErrors(['error1' => 'Lūdzu, apstipriniet savu e-pasta adresi, pirms lietot "Seko Saeimai". Uz norādīto e-pasta adresi esam nosūtījuši apstiprinājuma saiti. Pārbaudi, vai tā nav iekritusi mēstuļu sadaļā!']);

            $commissions = Commission::all();

            // Find the commission who's 'Save' button has been clicked
            foreach ($commissions as $c) {
                if ($request->has('submit-'.$c->id)) { // If this is the updated commission
                    
                    // Get all user emails
                    $emails = Email::where('user_id', Auth::user()->id)->get();

                    foreach ($emails as $e) {
                        // Set the subscription status for this commission and email address to the one that the checkbox holds
                        $this->setSubscriptionStatus($e->id, $c->id, $request->has($c->id.':'.$e->id)); 
                    }
                    break; // Only one commission is updated at a time
                }
            }

            return redirect("subscriptions");
        }
        return redirect("/")->withErrors(['error1' => 'Jums nav piekļuves.']);
    }

    // Set the subscription status between email address and commission
    private function setSubscriptionStatus($emailId, $commissionId, $status) {
        // Check if current subscription exists
        $subscription = Subscription::where('email_id', $emailId)->where('commission_id', $commissionId)->first();
        
        if ($status) { // Subscription must be on
            if (empty($subscription)) { // If subscription must exist but doesn't, add it
                $newSubscription = new Subscription();
                $newSubscription->email_id = $emailId;
                $newSubscription->commission_id = $commissionId;
                $newSubscription->save();
            }
        } else { // Subscription must be off
            if (!empty($subscription)) // If subscription must not exist but it does, delete it
                $subscription->delete();
        }
    }
}