<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SubscriptionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*Route::get('/', function () {
    return view('welcome');
});*/

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/


// User routes
Route::get('/', [UserController::class, 'index'])->name('login');
Route::post('login', [UserController::class, 'login'])->name('login.post');
Route::get('registration', [UserController::class, 'registration'])->name('register-user');
Route::post('registration', [UserController::class, 'registerUser'])->name('register.post');
Route::get('change-password', [UserController::class, 'changePassword'])->name('change-password');
Route::post('change-password', [UserController::class, 'updatePassword'])->name('change-password.store');
Route::get('signout', [UserController::class, 'signOut'])->name('signout');
Route::get('confirm-email', [UserController::class, 'confirmUserEmailAddress'])->name('confirm-user-email-address');
Route::get('delete-account', [UserController::class, 'deleteAccount'])->name('delete-account');
Route::post('delete-account', [UserController::class, 'deleteUser'])->name('user.delete');


// Subscription routes
Route::get('subscriptions', [SubscriptionController::class, 'subscriptions']);
Route::post('subscriptions', [SubscriptionController::class, 'storeSubscriptions'])->name('subscriptions.store');
Route::get('add-email', [SubscriptionController::class, 'createEmailAddress'])->name('email.create');
Route::post('add-email', [SubscriptionController::class, 'storeEmailAddress'])->name('email.store');
Route::get('remove-email/{email_id}', [SubscriptionController::class, 'deleteEmailAddress'])->name('email.delete');
Route::get('confirm-email-address', [SubscriptionController::class, 'confirmEmailAddress'])->name('confirm-email-address');