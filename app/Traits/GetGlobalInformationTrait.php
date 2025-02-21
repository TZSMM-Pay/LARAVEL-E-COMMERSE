<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use Modules\BasicPayment\app\Models\BasicPayment;
use Modules\PaymentGateway\app\Models\PaymentGateway;

trait GetGlobalInformationTrait {
    // get basic payment gateway information
    public function get_basic_payment_info() {
        $basic_payment = Cache::rememberForever('basic_payment', function () {

            $payment_info = BasicPayment::get();

            $basic_payment = [];
            foreach ($payment_info as $payment_item) {
                $basic_payment[$payment_item->key] = $payment_item->value;
            }

            return (object) $basic_payment;
        });

        return $basic_payment;

    }

    // get addon payment gateway information
    public function get_payment_gateway_info() {
        $payment_setting = Cache::rememberForever('payment_setting', function () {

            $payment_info = PaymentGateway::get();

            $payment_setting = [];
            foreach ($payment_info as $payment_item) {
                $payment_setting[$payment_item->key] = $payment_item->value;
            }

            return (object) $payment_setting;
        });

        return $payment_setting;
    }

    // calculate amount for all payment method
private function calculate_payable_charge($payable_amount, $gateway_name) {
    $gateway_charge = 0.0;
    $currency_rate = 1.0;
    $currency_code = 'USD';
    $country_code = 'US';
    $currency_id = 1;

    if ($gateway_name == 'stripe') {
        $basic_payment = $this->get_basic_payment_info();
        $gateway_charge = $basic_payment->stripe_charge;
    } elseif ($gateway_name == 'paypal') {
        $basic_payment = $this->get_basic_payment_info();
        $gateway_charge = $basic_payment->paypal_charge;
    } elseif ($gateway_name == 'razorpay') {
        $payment_setting = $this->get_payment_gateway_info();
        $gateway_charge = $payment_setting->razorpay_charge;
    } elseif ($gateway_name == 'mollie') {
        $payment_setting = $this->get_payment_gateway_info();
        $gateway_charge = $payment_setting->mollie_charge;
    } elseif ($gateway_name == 'instamojo') {
        $payment_setting = $this->get_payment_gateway_info();
        $gateway_charge = $payment_setting->instamojo_charge;
    } elseif ($gateway_name == 'flutterwave') {
        $payment_setting = $this->get_payment_gateway_info();
        $gateway_charge = $payment_setting->flutterwave_charge;
    } elseif ($gateway_name == 'paystack') {
        $payment_setting = $this->get_payment_gateway_info();
        $gateway_charge = $payment_setting->paystack_charge;
    } elseif ($gateway_name == 'tzsmmpay') { 
        $payment_setting = $this->get_payment_gateway_info();
        $gateway_charge = $payment_setting->tzsmmpay_charge; 
    }

    if (($gateway_name == 'stripe' || $gateway_name == 'paypal' || $gateway_name == 'razorpay' || 
         $gateway_name == 'mollie' || $gateway_name == 'instamojo' || $gateway_name == 'paystack' || 
         $gateway_name == 'flutterwave' || $gateway_name == 'tzsmmpay') && 
         getSessionCurrency() && session()->has('currency_code')) {

        $gateway_currency = allCurrencies()->where('currency_code', getSessionCurrency())->first();
        $currency_code = $gateway_currency->currency_code;
        $country_code = $gateway_currency->country_code;
        $currency_rate = $gateway_currency->currency_rate;
        $currency_id = $gateway_currency->id;
    }

    session()->put('gateway_charge_in_usd', $payable_amount * ($gateway_charge / 100));

    $payable_amount = $payable_amount * $currency_rate;
    $gateway_charge = $payable_amount * ($gateway_charge / 100);
    $payable_with_charge = $payable_amount + $gateway_charge;
    $payable_with_charge = sprintf('%0.2f', $payable_with_charge);

    session()->put('gateway_charge', $gateway_charge);
    session()->put('payable_currency', $currency_code);

    return (object) [
        'country_code'        => $country_code,
        'currency_code'       => $currency_code,
        'currency_id'         => $currency_id,
        'gateway_charge'      => $gateway_charge,
        'payable_with_charge' => $payable_with_charge,
        'payable_amount'      => $payable_amount,
    ];
}

    // mail configuraton setup
    private function set_mail_config() {
        $email_setting = Cache::get('setting');
        $mailConfig = [
            'transport'  => 'smtp',
            'host'       => $email_setting->mail_host,
            'port'       => $email_setting->mail_port,
            'encryption' => $email_setting->mail_encryption,
            'username'   => $email_setting->mail_username,
            'password'   => $email_setting->mail_password,
            'timeout'    => null,
        ];

        config(['mail.mailers.smtp' => $mailConfig]);
        config(['mail.from.address' => $email_setting->mail_sender_email]);
        config(['mail.from.name' => $email_setting->mail_sender_name]);
    }
}
