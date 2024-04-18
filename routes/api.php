<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

use App\Http\Controllers\MailDataController as MailData;

Route::POST('/maildata', [MailData::class, 'receive']);
Route::GET('/processdata/{module}/{status}/{encrypt}', [MailData::class, 'processData']);
Route::POST('/getaccess', [MailData::class, 'getAccess']);

use App\Http\Controllers\PurchaseSelectionController as Selection;
Route::POST('/purchase_selection', [Selection::class, 'Mail']);
Route::GET('/poselection/{status}/{encrypt}', [Selection::class, 'processData']);
Route::POST('/poselection/getaccess', [Selection::class, 'getaccess']);
Route::POST('/pos/getaccess', [Selection::class, 'getaccess']);

use App\Http\Controllers\CbPPuNewController as CbPPuNew;
Route::POST('/cbppunew', [CbPPuNew::class, 'Mail']);
Route::GET('/cbppu/{status}/{encrypt}', [CbPPuNew::class, 'processData']);
Route::POST('/cbppunew/getaccess', [CbPPuNew::class, 'getaccess']);

use App\Http\Controllers\StaffActionController as StaffAction;
Route::POST('/staffaction', [StaffAction::class, 'staffaction']);
Route::POST('/staffaction_por', [StaffAction::class, 'staffaction_por']);
Route::POST('/staffaction_pos', [StaffAction::class, 'staffaction_pos']);
Route::POST('/fileexist', [StaffAction::class, 'fileexist']);

use App\Http\Controllers\StaffFeedbackController as StaffFeedback;

Route::POST('/feedback_po', [StaffFeedback::class, 'feedback_po']);
Route::POST('/feedback_cb_fupd', [StaffFeedback::class, 'feedback_cb_fupd']);
Route::POST('/feedback_cb', [StaffFeedback::class, 'feedback_cb']);

use App\Http\Controllers\CbPPuVvipNewController as CbPPuVvipNew;
Route::POST('/cbppunewvvip', [CbPPuVvipNew::class, 'Mail']);
Route::GET('/cbppunewvvip/{status}/{encrypt}', [CbPPuVvipNew::class, 'processData']);
Route::POST('/cbppunewvvip/getaccess', [CbPPuVvipNew::class, 'getaccess']);


use App\Http\Controllers\SelController as Select;
Route::get('/select', [Select::class, 'index']);

use App\Http\Controllers\AutoSendController as AutoSend;
Route::get('/autosend', [AutoSend::class, 'index']);

use App\Http\Controllers\AutoFeedbackController as AutoFeedback;
Route::get('/autofeedback', [AutoFeedback::class, 'index']);

use App\Http\Controllers\OldFeedbackController as OldFeedback;
Route::get('/oldfeedback', [OldFeedback::class, 'index']);

