<?php

use Illuminate\Support\Facades\Route;
use Modules\PaymentGateway\app\Http\Controllers\AddonPaymentController;
use Modules\PaymentGateway\app\Http\Controllers\PaymentGatewayController;

Route::group(['as' => 'admin.', 'prefix' => 'admin', 'middleware' => ['auth:admin', 'translation']], function () {

    Route::controller(PaymentGatewayController::class)->group(function () {
        Route::get('paymentgateway', 'paymentgateway')->name('paymentgateway');
        Route::put('razorpay-update', 'razorpay_update')->name('razorpay-update');
        Route::put('flutterwave-update', 'flutterwave_update')->name('flutterwave-update');
        Route::put('paystack-update', 'paystack_update')->name('paystack-update');
        Route::put('mollie-update', 'mollie_update')->name('mollie-update');
        Route::put('instamojo-update', 'instamojo_update')->name('instamojo-update');
        Route::put('tzsmmpay-update', 'tzsmmpay_update')->name('tzsmmpay-update');
    });

});

Route::group(['as' => 'paymentgateway.', 'prefix' => 'paymentgateway'], function () {

    Route::controller(AddonPaymentController::class)->group(function () {

        Route::get('mollie-payment-success', 'mollie_payment_success')->name('mollie-payment-success');
        Route::get('tzsmmpay-payment-success', 'tzsmmpay_payment_success')->name('tzsmmpay-payment-success');
        Route::get('response-instamojo', 'instamojo_success')->name('response-instamojo');
        Route::post('pay-via-flutterwave', 'flutterwave_payment')->name('pay-via-flutterwave');
        Route::post('pay-via-paystack', 'paystack_payment')->name('pay-via-paystack');

    });
});
