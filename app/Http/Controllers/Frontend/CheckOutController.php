<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Traits\GetGlobalInformationTrait;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Modules\Currency\app\Models\MultiCurrency;

class CheckOutController extends Controller
{
    use GetGlobalInformationTrait;
    function index()
    {
        $products = Cart::content();

        foreach($products as $product) {
            if(in_array($product->id, session()->get('enrollments'))) {
                return redirect()->route('cart')->with(['messege' => __('Error occurred please try agin'), 'alert-type' => 'error']);
            }
            if(in_array($product->id, session()->get('enrollments'))) {
                return redirect()->route('cart')->with(['messege' => __('Error occurred please try agin'), 'alert-type' => 'error']);
            }
        }

        $cartTotal = $this->cartTotal();
        $discountPercent = Session::has('offer_percentage') ? Session::get('offer_percentage') : 0;
        $discountAmount = ($cartTotal * $discountPercent) / 100;
        $total = currency($cartTotal - $discountAmount);
        $coupon = Session::has('coupon_code') ? Session::get('coupon_code') : '';

        $payable_amount = $cartTotal - $discountAmount;
        Session::put('payable_amount', $payable_amount);
        $user = userAuth();

        $basic_payment = $this->get_basic_payment_info();
        $payment_setting = $this->get_payment_gateway_info();

        $tzsmmpay_calculate_charge = $this->calculate_payable_charge($payable_amount, 'tzsmmpay');
        
        $tzsmmpay_credentials = (object) [
            'currency_code' => $tzsmmpay_calculate_charge->currency_code,
            'payable_with_charge' => $tzsmmpay_calculate_charge->payable_with_charge,
            'tzsmmpay_api_key' => $payment_setting->tzsmmpay_api_key,
            'tzsmmpay_image' => $payment_setting->tzsmmpay_image,
            'tzsmmpay_status' => $payment_setting->tzsmmpay_status,
        ];


        /**start razorpay setting */
        $razorpay_calculate_charge = $this->calculate_payable_charge($payable_amount, 'razorpay');

        $razorpay_credentials = (object) [
            'currency_code' => $razorpay_calculate_charge->currency_code,
            'payable_with_charge' => $razorpay_calculate_charge->payable_with_charge,
            'razorpay_key' => $payment_setting->razorpay_key,
            'razorpay_secret' => $payment_setting->razorpay_secret,
            'razorpay_name' => $payment_setting->razorpay_name,
            'razorpay_description' => $payment_setting->razorpay_description,
            'razorpay_image' => $payment_setting->razorpay_image,
            'razorpay_theme_color' => $payment_setting->razorpay_theme_color,
            'razorpay_status' => $payment_setting->razorpay_status,
        ];
        /**end razorpay setting */

        /**start mollie setting */
        $mollie_credentials = (object) [
            'mollie_status' => $payment_setting->mollie_status,
            'mollie_image' => $payment_setting->mollie_image,
        ];
        /**end mollie setting */

        /**start instamojo setting */
        $instamojo_credentials = (object) [
            'instamojo_status' => $payment_setting->instamojo_status,
            'instamojo_image' => $payment_setting->instamojo_image,
        ];
        /**end instamojo setting */

        /**start flutterwave setting */
        $flutterwave_calculate_charge = $this->calculate_payable_charge($payable_amount, 'flutterwave');

        $flutterwave_credentials = (object) [
            'country_code' => $flutterwave_calculate_charge->country_code,
            'currency_code' => $flutterwave_calculate_charge->currency_code,
            'payable_with_charge' => $flutterwave_calculate_charge->payable_with_charge,
            'flutterwave_public_key' => $payment_setting->flutterwave_public_key,
            'flutterwave_secret_key' => $payment_setting->flutterwave_secret_key,
            'flutterwave_app_name' => $payment_setting->flutterwave_app_name,
            'flutterwave_status' => $payment_setting->flutterwave_status,
            'flutterwave_image' => $payment_setting->flutterwave_image,
        ];
        /**end flutterwave setting */

        /**start paystack setting */
        $paystack_calculate_charge = $this->calculate_payable_charge($payable_amount, 'paystack');

        $paystack_credentials = (object) [
            'country_code' => $paystack_calculate_charge->country_code,
            'currency_code' => $paystack_calculate_charge->currency_code,
            'payable_with_charge' => $paystack_calculate_charge->payable_with_charge,
            'paystack_public_key' => $payment_setting->paystack_public_key,
            'paystack_secret_key' => $payment_setting->paystack_secret_key,
            'paystack_status' => $payment_setting->paystack_status,
            'paystack_image' => $payment_setting->paystack_image,
        ];
        /**end paystack setting */

        $basic_payment = $this->get_basic_payment_info();


        return view('frontend.pages.checkout')->with([
            'tzsmmpay_credentials' => $tzsmmpay_credentials,
            'products' => $products,
            'total' => $total,
            'discountAmount' => $discountAmount,
            'discountPercent' => $discountPercent,
            'coupon' => $coupon,

            'basic_payment' => $basic_payment,
            'payable_amount' => $payable_amount,
            'payment_setting' => $payment_setting,
            'razorpay_credentials' => $razorpay_credentials,
            'mollie_credentials' => $mollie_credentials,
            'instamojo_credentials' => $instamojo_credentials,
            'flutterwave_credentials' => $flutterwave_credentials,
            'paystack_credentials' => $paystack_credentials,
            'user' => $user,
            'strip_key' => $basic_payment->stripe_key,
        ]);
    }

    function cartTotal()
    {
        $cartTotal = 0;

        $cartItems = Cart::content();
        foreach ($cartItems as $key => $cartItem) {
            $cartTotal += $cartItem->price;
        }

        return $cartTotal;
    }

}
