<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\BankInformationRequest;
use App\Jobs\DefaultMailJob;
use App\Mail\DefaultMail;
use App\Models\Course;
use App\Traits\GetGlobalInformationTrait;
use App\Traits\MailSenderTrait;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Modules\BasicPayment\app\Enums\BasicPaymentSupportedCurrenyListEnum;
use Modules\BasicPayment\app\Http\Controllers\FrontPaymentController;
use Modules\ClubPoint\app\Models\ClubPointHistory;
use Modules\Coupon\app\Models\Coupon;
use Modules\Coupon\app\Models\CouponHistory;
use Modules\Currency\app\Models\MultiCurrency;
use Modules\GlobalSetting\app\Models\EmailTemplate;
use Modules\Order\app\Models\Enrollment;
use Modules\Order\app\Models\Order;
use Modules\Order\app\Models\OrderItem;
use Modules\PaymentGateway\app\Enums\PaymentGatewaySupportedCurrenyListEnum;
use Modules\PaymentGateway\app\Http\Controllers\AddonPaymentController;
use Str;

class PaymentController extends Controller
{
    use GetGlobalInformationTrait, MailSenderTrait;

    public function payment()
    {

        /** you can write your project logic here, you can dyanmic payable amount also */
        $payable_amount = 100;

        $user = Auth::guard('web')->user();

        $basic_payment = $this->get_basic_payment_info();
        $payment_setting = $this->get_payment_gateway_info();

        $flutterwave_currency = MultiCurrency::where('id', $payment_setting->flutterwave_currency_id)->first();
        $paystack_currency = MultiCurrency::where('id', $payment_setting->paystack_currency_id)->first();

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
        ];
        /**end mollie setting */

        /**start instamojo setting */
        $instamojo_credentials = (object) [
            'instamojo_status' => $payment_setting->instamojo_status,
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
        ];
        /**end paystack setting */

        return view('payment')->with([
            'user' => $user,
            'payable_amount' => $payable_amount,
            'basic_payment' => $basic_payment,
            'payment_setting' => $payment_setting,
            'razorpay_credentials' => $razorpay_credentials,
            'mollie_credentials' => $mollie_credentials,
            'instamojo_credentials' => $instamojo_credentials,
            'flutterwave_credentials' => $flutterwave_credentials,
            'paystack_credentials' => $paystack_credentials,
        ]);
    }

    public function pay_via_stripe(Request $request)
    {
        if (!BasicPaymentSupportedCurrenyListEnum::isStripeSupportedCurrencies(getSessionCurrency())) {
            session()->flash('show_stripe_currency');

            $notification = trans('You are trying to use unsupported currency');
            $notification = ['messege' => $notification, 'alert-type' => 'warning'];

            return back()->with($notification);
        }

        $basic_payment = $this->get_basic_payment_info();

        $payable_amount = Session::get('payable_amount');

        $after_success_url = route('payment-addon-success');
        $after_faild_url = route('payment-addon-faild');

        $user = userAuth();

        $stripe_credentials = (object) [
            'stripe_key' => $basic_payment->stripe_key,
            'stripe_secret' => $basic_payment->stripe_secret,
        ];

        $stripe_payment = new FrontPaymentController();

        return $stripe_payment->pay_with_stripe($request, $stripe_credentials, $payable_amount, $after_success_url, $after_faild_url, $user);
    }

    public function pay_via_paypal()
    {
        if (!BasicPaymentSupportedCurrenyListEnum::isPaypalSupportedCurrencies(getSessionCurrency())) {
            session()->flash('show_paypal_currency');

            $notification = trans('You are trying to use unsupported currency');
            $notification = ['messege' => $notification, 'alert-type' => 'warning'];

            return back()->with($notification);
        }

        $basic_payment = $this->get_basic_payment_info();

        $payable_amount = Session::get('payable_amount');

        $after_success_url = route('payment-addon-success');
        $after_faild_url = route('payment-addon-faild');

        $user = userAuth();

        $paypal_credentials = (object) [
            'paypal_client_id' => $basic_payment->paypal_client_id,
            'paypal_secret_key' => $basic_payment->paypal_secret_key,
            'paypal_account_mode' => $basic_payment->paypal_account_mode,
            'paypal_app_id' => $basic_payment->paypal_app_id,
        ];

        $paypal_payment = new FrontPaymentController();

        return $paypal_payment->pay_with_paypal($paypal_credentials, $payable_amount, $after_success_url, $after_faild_url, $user);
    }



    public function pay_via_razorpay(Request $request)
    {
        if (!PaymentGatewaySupportedCurrenyListEnum::isRazorpaySupportedCurrencies(getSessionCurrency())) {
            session()->flash('show_razorpay_currency');

            $notification = trans('You are trying to use unsupported currency');
            $notification = ['messege' => $notification, 'alert-type' => 'warning'];

            return back()->with($notification);
        }

        $payment_setting = $this->get_payment_gateway_info();

        $after_success_url = route('payment-addon-success');
        $after_faild_url = route('payment-addon-faild');

        $user = userAuth();

        $razorpay_credentials = (object) [
            'razorpay_key' => $payment_setting->razorpay_key,
            'razorpay_secret' => $payment_setting->razorpay_secret,
        ];

        $razorpay_payment = new AddonPaymentController();

        return $razorpay_payment->pay_with_razorpay($request, $razorpay_credentials, $request->payable_amount, $after_success_url, $after_faild_url, $user);
    }
    public function pay_via_tzsmmpay(Request $request)
    {
        if (!PaymentGatewaySupportedCurrenyListEnum::istzsmmpaySupportedCurrencies(getSessionCurrency())) {
            session()->flash('show_tzsmmpay_currency');

            $notification = trans('You are trying to use unsupported currency');
            $notification = ['messege' => $notification, 'alert-type' => 'warning'];

            return back()->with($notification);
        }

        $payment_setting = $this->get_payment_gateway_info();
        $payable_amount = Session::get('payable_amount');
        $after_success_url = route('payment-addon-success');
        $after_faild_url = route('payment-addon-faild');

        $user = userAuth();

        $tzsmmpay_credentials = (object) [
            'tzsmmpay_api_key' => $payment_setting->tzsmmpay_api_key,
        ];

        $tzsmmpay_payment = new AddonPaymentController();

        return $tzsmmpay_payment->pay_with_tzsmmpay($request, $tzsmmpay_credentials, $payable_amount, $after_success_url, $after_faild_url, $user);
    }

    public function pay_via_mollie(Request $request)
    {
        if (!PaymentGatewaySupportedCurrenyListEnum::isMollieSupportedCurrencies(getSessionCurrency())) {
            session()->flash('show_mollie_currency');

            $notification = trans('You are trying to use unsupported currency');
            $notification = ['messege' => $notification, 'alert-type' => 'warning'];

            return back()->with($notification);
        }

        $payable_amount = Session::get('payable_amount');
        Session::put('payable_with_charge', $this->calculate_payable_charge($request->payable_amount, 'mollie')->payable_with_charge);

        $after_success_url = route('payment-addon-success');
        $after_faild_url = route('payment-addon-faild');

        $user = userAuth();

        $payment_setting = $this->get_payment_gateway_info();

        $mollie_credentials = (object) [
            'mollie_key' => $payment_setting->mollie_key,
        ];

        $mollie_payment = new AddonPaymentController();

        return $mollie_payment->pay_with_mollie($mollie_credentials, $payable_amount, $after_success_url, $after_faild_url, $user);
    }

    public function pay_via_instamojo()
    {
        if (!PaymentGatewaySupportedCurrenyListEnum::isInstamojoSupportedCurrencies(getSessionCurrency())) {
            session()->flash('show_instamojo_currency');

            $notification = trans('You are trying to use unsupported currency');
            $notification = ['messege' => $notification, 'alert-type' => 'warning'];

            return back()->with($notification);
        }

        $payable_amount = Session::get('payable_amount');

        $after_success_url = route('payment-addon-success');
        $after_faild_url = route('payment-addon-faild');

        $user = userAuth();

        $payment_setting = $this->get_payment_gateway_info();

        $instamojo_credentials = (object) [
            'instamojo_api_key' => $payment_setting->instamojo_api_key,
            'instamojo_auth_token' => $payment_setting->instamojo_auth_token,
            'account_mode' => $payment_setting->instamojo_account_mode,
        ];

        $instamojo_payment = new AddonPaymentController();

        return $instamojo_payment->pay_with_instamojo($instamojo_credentials, $payable_amount, $after_success_url, $after_faild_url, $user);
    }

    function pay_via_flutterwave(Request $request) {
        $payment_setting = $this->get_payment_gateway_info();
        $curl = curl_init();
        $tnx_id = $request->tnx_id;
        $url = "https://api.flutterwave.com/v3/transactions/$tnx_id/verify";
        $token = $payment_setting->flutterwave_secret_key;

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                "Authorization: Bearer $token",
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response);
        if ($response->status == 'success') {

            $paymentDetails = [
                'status' => $response->status,
                'trx_id' => $tnx_id,
                'amount' => $response?->data?->amount,
                'amount_settled' => $response?->data?->amount_settled,
                'currency' => $response?->data?->currency,
                'charged_amount' => $response?->data?->charged_amount,
                'app_fee' => $response?->data?->app_fee,
                'merchant_fee' => $response?->data?->merchant_fee,
                'card_last_4digits' => $response?->data?->card?->last_4digits,
            ];

            Session::put('paid_amount', $response?->data?->amount);
            Session::put('payable_currency', $response?->data?->currency);
            Session::put('payment_details', $paymentDetails);
            Session::put('after_success_gateway', 'Flutterwave');
            Session::put('after_success_transaction', $tnx_id);
            Session::put('payable_with_charge', $response?->data?->amount);

            return response()->json(['message' => 'payment success']);
        } else {
            $notification = trans('Payment Fail');

            return response()->json(['message' => $notification], 403);
        } 
    }

    function pay_via_paystack(Request $request) {
        
        $reference = $request->reference;
        $transaction = $request->tnx_id;
        $secret_key = $request->secret_key;
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/$reference",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $secret_key",
                'Cache-Control: no-cache',
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        if ($err) {
            info($err);
        }
        curl_close($curl);
        $final_data = json_decode($response);
        if ($final_data->status == true) {

            $paymentDetails = [
                'status' => $final_data?->data?->status,
                'transaction_id' => $transaction,
                'requested_amount' => $final_data?->data->requested_amount,
                'amount' => $final_data?->data?->amount,
                'currency' => $final_data?->data?->currency,
                'gateway_response' => $final_data?->data?->gateway_response,
                'paid_at' => $final_data?->data?->paid_at,
                'card_last_4_digits' => $final_data?->data->authorization?->last4,
            ];

            Session::put('paid_amount', ($final_data?->data?->amount / 100));
            Session::put('payable_currency', $final_data?->data?->currency);
            Session::put('payment_details', $paymentDetails);
            Session::put('after_success_gateway', 'Paystack');
            Session::put('after_success_transaction', $transaction);
            Session::put('payable_with_charge', $final_data?->data?->amount / 100);

            return response()->json(['message' => 'payment success']);
        } else {
            $notification = trans('Something went wrong, please try again');

            return response()->json(['message' => $notification], 403);
        } 
    }

    public function pay_via_bank(BankInformationRequest $request)
    {
        $bankDetails = json_encode($request->only(['bank_name', 'account_number', 'routing_number', 'branch', 'transaction']));

        $user = userAuth();

        $order = Order::create([
            'invoice_id' => Str::random(10),
            'buyer_id' => $user->id,
            'status' => 'completed',
            'has_coupon' => Session::has('coupon_code') ? 1 : 0,
            'coupon_code' => Session::get('coupon_code'),
            'coupon_discount_percent' => Session::get('offer_percentage'),
            'coupon_discount_amount' => Session::get('coupon_discount_amount'),
            'payment_method' => 'Bank',
            'payment_status' => 'pending',
            'payable_amount' => Session::get('payable_amount'),
            'gateway_charge' => 0,
            'payable_with_charge' => $this->calculate_payable_charge(Session::get('payable_amount'), getSessionCurrency())->payable_with_charge,
            'paid_amount' => $this->calculate_payable_charge(Session::get('payable_amount'), getSessionCurrency())->payable_with_charge,
            'conversion_rate' => 1,
            'payable_currency' => getSessionCurrency(),
            'payment_details' => $bankDetails,
            'transaction_id' => Str::random(10),
            'commission_rate' => Cache::get('setting')->commission_rate,
        ]);

        foreach (Cart::content() as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'price' => $item->price,
                'course_id' => $item->id,
                'commission_rate' => Cache::get('setting')->commission_rate,
            ]);
        }

        Session::forget([
            'after_success_url',
            'after_faild_url',
            'payable_amount',
            'gateway_charge',
            'after_success_gateway',
            'after_success_transaction',
            'subscription_plan_id',
            'payable_with_charge',
            'payable_currency',
            'subscription_plan_id',
            'paid_amount',
            'payment_details',
            'cart',
            'coupon_code',
            'offer_percentage',
            'coupon_discount_amount',
            'gateway_charge_in_usd'
        ]);

        $notification = trans('Payment Success.');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->route('order-success')->with($notification);
    }

    function pay_via_free_gateway() {
        if(!Session::has('payable_amount') || Session::get('payable_amount') != 0) {

            $notification = trans('Payment faild, please try again');
            $notification = ['messege' => $notification, 'alert-type' => 'error'];
            return redirect()->back()->with($notification);
        }

        $after_success_url = route('payment-addon-success');
        $after_faild_url = route('payment-addon-faild');

        Session::put('after_success_url', $after_success_url);
        Session::put('after_faild_url', $after_faild_url);
        Session::put('payable_amount', 0);
        Session::put('paid_amount', 0);
        Session::put('after_success_gateway', 'Free');
        Session::put('after_success_transaction', Str::random(10));
        Session::put('payment_details', []);
        session()->put('payable_with_charge', 0);

        return redirect($after_success_url);
    }

    public function payment_addon_success($bankDetails = null)
    {
        $payable_amount = Session::get('payable_amount');
        $payable_with_charge = Session::get('payable_with_charge', 0);
        $payable_currency = Session::get('payable_currency');
        $gateway_name = Session::get('after_success_gateway');
        $transaction = Session::get('after_success_transaction');
        $paid_amount = Session::get('paid_amount', 0);
        $paymentDetails = Session::get('payment_details');
        $gateway_charge = Session::get('gateway_charge_in_usd');

        if (in_array($gateway_name, ['Razorpay', 'Stripe'])) {
            $allCurrencyCodes = BasicPaymentSupportedCurrenyListEnum::getStripeSupportedCurrencies();

            if (in_array(Str::upper($payable_currency), $allCurrencyCodes['non_zero_currency_codes'])) {
                $paid_amount = $paid_amount;
            } elseif (in_array(Str::upper($payable_currency), $allCurrencyCodes['three_digit_currency_codes'])) {
                $paid_amount = (int) rtrim(strval($paid_amount), '0');
            } else {
                $paid_amount = floatval($paid_amount / 100);
            }
        }

        $user = userAuth();

        $order = Order::create([
            'invoice_id' => Str::random(10),
            'buyer_id' => $user->id,
            'status' => 'completed',
            'has_coupon' => Session::has('coupon_code') ? 1 : 0,
            'coupon_code' => Session::get('coupon_code'),
            'coupon_discount_percent' => Session::get('offer_percentage'),
            'coupon_discount_amount' => Session::get('coupon_discount_amount'),
            'payment_method' => $gateway_name,
            'payment_status' => 'paid',
            'payable_amount' => $payable_amount,
            'gateway_charge' => $gateway_charge,
            'payable_with_charge' => $payable_with_charge,
            'paid_amount' => $paid_amount,
            'payable_currency' => $payable_currency,
            'conversion_rate' => Session::get('currency_rate', 1),
            'payment_details' => json_encode($paymentDetails),
            'transaction_id' => $transaction,
            'commission_rate' => Cache::get('setting')->commission_rate,
        ]);

        $data_layer_order_items = [];

        foreach (Cart::content() as $item) {
            $order_item = [
                'order_id' => $order->id,
                'price' => $item->price,
                'course_id' => $item->id,
                'commission_rate' => Cache::get('setting')->commission_rate,
            ];
            OrderItem::create([
                'order_id' => $order->id,
                'price' => $item->price,
                'course_id' => $item->id,
                'commission_rate' => Cache::get('setting')->commission_rate,
            ]);
            $data_layer_order_items[] = [
                'course_name' => $item->name,
                'price' => currency($item->price),
                'url' => route('course.show', $item->options->slug),
            ];
            Enrollment::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'course_id' => $item->id,
                'has_access' => 1,
            ]);

            // insert instructor commission to his wallet
            $commissionAmount = $item->price * ($order->commission_rate / 100);
            $amountAfterCommission = $item->price - $commissionAmount;  
            $instructor = Course::find($item->id)->instructor;
            $instructor->increment('wallet_balance', $amountAfterCommission);
            
        }
        $settings = cache()->get('setting');
        $marketingSettings = cache()->get('marketing_setting');
        if ($user && $settings->google_tagmanager_status == 'active' && $marketingSettings->order_success) {
            $order_success = [
                'invoice_id' => $order->invoice_id,
                'transaction_id' => $order->transaction_id,
                'payment_method' => $order->payment_method,
                'payable_currency' => $order->payable_currency,
                'paid_amount' => $order->paid_amount,
                'payment_status' => $order->payment_status,
                'order_items' => $data_layer_order_items,
                'student_info' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ];
            session()->put('enrollSuccess', $order_success);
        }

        // send mail

        $this->handleMailSending([
            'email' => $user->email,
            'name' => $user->name,
            'order_id' => $order->invoice_id,
            'paid_amount' => $order->paid_amount. ' '.$order->payable_currency,
            'payment_method' => $order->payment_method
        ]);

        Session::forget([
            'after_success_url',
            'after_faild_url',
            'payable_amount',
            'gateway_charge',
            'after_success_gateway',
            'after_success_transaction',
            'subscription_plan_id',
            'payable_with_charge',
            'payable_currency',
            'subscription_plan_id',
            'paid_amount',
            'payment_details',
            'cart',
            'coupon_code',
            'offer_percentage',
            'coupon_discount_amount',
            'gateway_charge_in_usd'
        ]);

        $notification = trans('Payment Success.');
        $notification = ['messege' => $notification, 'alert-type' => 'success'];

        return redirect()->route('order-success')->with($notification);
    }

    public function payment_addon_faild()
    {
        $data_layer_order_items = [];
        foreach (Cart::content() as $item) {
            $data_layer_order_items[] = [
                'course_name' => $item->name,
                'price' => currency($item->price),
                'url' => route('course.show', $item->options->slug),
            ];
        }
        
        $settings = cache()->get('setting');
        $marketingSettings = cache()->get('marketing_setting');
        if ($settings->google_tagmanager_status == 'active' && $marketingSettings->order_failed) {
            $user = userAuth();
            $order_failed = [
                'payable_currency' => session('payable_currency',getSessionCurrency()),
                'paid_amount' => session('paid_amount' , null),
                'payment_status' => 'Failed',
                'order_items' => $data_layer_order_items,
                'student_info' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ];
            session()->put('enrollFailed', $order_failed);
        }
        Session::forget([
            'after_success_url',
            'after_faild_url',
            'payable_amount',
            'gateway_charge',
            'after_success_gateway',
            'after_success_transaction',
            'subscription_plan_id',
            'payable_with_charge',
            'payable_currency',
            'subscription_plan_id',
            'paid_amount',
            'payment_details',
            'cart',
            'coupon_code',
            'offer_percentage',
            'coupon_discount_amount',
            'gateway_charge_in_usd'
        ]);

        $notification = trans('Payment faild, please try again');
        $notification = ['messege' => $notification, 'alert-type' => 'error'];

        return redirect()->route('order-fail')->with($notification);
    }

    function order_success() {
       return view('frontend.pages.order-success'); 
    }

    function order_fail() {
       return view('frontend.pages.order-fail'); 
    }

    function handleMailSending(array $mailData)
    {
        self::setMailConfig();

        // Get email template
        $template = EmailTemplate::where('name', 'order_completed')->firstOrFail();
        $mailData['subject'] = $template->subject;

        // Prepare email content
        $message = str_replace('{{name}}', $mailData['name'], $template->message);
        $message = str_replace('{{order_id}}', $mailData['order_id'], $message);
        $message = str_replace('{{paid_amount}}', $mailData['paid_amount'], $message);
        $message = str_replace('{{payment_method}}', $mailData['payment_method'], $message);

        if (self::isQueable()) {
            DefaultMailJob::dispatch($mailData['email'], $mailData, $message);
        } else {
            Mail::to($mailData['email'])->send(new DefaultMail($mailData, $message));
        }
    }
}
