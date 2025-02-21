<?php

namespace Modules\PaymentGateway\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\GetGlobalInformationTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Mollie\Laravel\Facades\Mollie;
use Razorpay\Api\Api;


class AddonPaymentController extends Controller
{
    use GetGlobalInformationTrait;

    public function pay_with_tzsmmpay(Request $request, $tzsmmpay_credentials, $payable_amount, $after_success_url, $after_faild_url, $user)
    {
         $calculate_payable_charge = $this->calculate_payable_charge($payable_amount, 'tzsmmpay');
         $currency = strtoupper($calculate_payable_charge->currency_code);
         $apiKey = $tzsmmpay_credentials->tzsmmpay_api_key;
         $apiUrl = "https://tzsmmpay.com/api/payment/create";
         $posted = [
            'api_key'     => $apiKey,
            'cus_name'    => $user->name ?? 'John Doe',
            'cus_email'   => $user->email ?? 'noemail@example.com',
            'cus_number'  => $user->id, 
            'amount'      => number_format($calculate_payable_charge->payable_with_charge, 2, '.', ''),
            'currency'    => $currency ?? 'USD',
            'success_url' => $after_success_url,
            'cancel_url'  => route('student.dashboard'),
            'callback_url'=> $after_success_url,
        ];
        

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($posted),
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json"
            ],
        ]);
    
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
    
        if ($err) {
            die(json_encode(["status" => false, "message" => "cURL Error: " . $err]));
        }

        $result = json_decode($response, true);
    
        if (!isset($result['success']) || !$result['success']) {
            die(json_encode(["status" => false, "messages" => $result['messages'] ?? json_encode($result)]));
        }
        Session::put('after_success_url', $after_success_url);
        Session::put('after_faild_url', $after_faild_url);
        Session::put('payable_amount', $payable_amount);
        Session::put('after_success_gateway', 'TZSMM Pay');
        Session::put('paid_amount', $calculate_payable_charge->payable_with_charge);
        Session::put('payable_currency', $currency);
        Session::put('payable_with_charge', $calculate_payable_charge->payable_with_charge);        
        $payment_url = $result['payment_url'];
        return redirect($payment_url);

    }
    
    public function pay_with_razorpay(Request $request, $razorpay_credentials, $payable_amount, $after_success_url, $after_faild_url, $user)
    {
        $input = $request->all();
        $api = new Api($razorpay_credentials->razorpay_key, $razorpay_credentials->razorpay_secret);
        $payment = $api->payment->fetch($input['razorpay_payment_id']);
        if (count($input) && ! empty($input['razorpay_payment_id'])) {
            try {
                $response = $api->payment->fetch($input['razorpay_payment_id'])->capture(['amount' => $payment['amount']]);

                $paymentDetails = [
                    'transaction_id' => $response->id,
                    'amount' => $response->amount,
                    'currency' => $response->currency,
                    'fee' => $response->fee,
                    'description' => $response->description,
                    'payment_method' => $response->method,
                    'status' => $response->status,
                ];

                $calculate_payable_charge = $this->calculate_payable_charge($payable_amount, 'razorpay');
                Session::put('after_success_url', $after_success_url);
                Session::put('after_faild_url', $after_faild_url);
                Session::put('payable_amount', $payable_amount);
                Session::put('after_success_gateway', 'Razorpay');
                Session::put('after_success_transaction', $response->id);
                Session::put('paid_amount', $response->amount);
                Session::put('payable_currency', $response->currency);
                Session::put('payment_details', $paymentDetails);
                Session::put('payable_with_charge', $calculate_payable_charge->payable_with_charge);

                return redirect($after_success_url);
            } catch (\Exception $e) {
                info($e);

                return redirect($after_faild_url);
            }
        } else {
            return redirect($after_faild_url);
        }

    }

    public function pay_with_mollie($mollie_credentials, $payable_amount, $after_success_url, $after_faild_url, $user)
    {

        $calculate_payable_charge = $this->calculate_payable_charge($payable_amount, 'mollie');

        $currency = strtoupper($calculate_payable_charge->currency_code);

        try {
            Mollie::api()->setApiKey($mollie_credentials->mollie_key);
            $payment = Mollie::api()->payments()->create([
                'amount' => [
                    'currency' => ''.$currency.'',
                    'value' => ''.$calculate_payable_charge->payable_with_charge.'',
                ],
                'description' => env('APP_NAME'),
                'redirectUrl' => route('paymentgateway.mollie-payment-success'),
            ]);

            $payment = Mollie::api()->payments()->get($payment->id);
            session()->put('payment_id', $payment->id);
            session()->put('after_success_url', $after_success_url);
            session()->put('after_faild_url', $after_faild_url);
            session()->put('paid_amount', $payment->amount->value);
            session()->put('mollie_credentials', $mollie_credentials);
            session()->put('payable_with_charge', $calculate_payable_charge->payable_with_charge);

            return redirect($payment->getCheckoutUrl(), 303);

        } catch (Exception $ex) {
            $notification = $ex->getMessage();
            $notification = ['messege' => $notification, 'alert-type' => 'error'];

            return redirect()->back()->with($notification);
        }

    }

    public function mollie_payment_success(Request $request)
    {

        $mollie_credentials = Session::get('mollie_credentials');

        Mollie::api()->setApiKey($mollie_credentials->mollie_key);
        $payment = Mollie::api()->payments->get(session()->get('payment_id'));
        if ($payment->isPaid()) {

            Session::put('after_success_gateway', 'Mollie');
            Session::put('after_success_transaction', session()->get('payment_id'));

            $after_success_url = Session::get('after_success_url');

            return redirect($after_success_url);

        } else {
            $after_faild_url = Session::get('after_faild_url');

            return redirect($after_faild_url);
        }
    }

    public function pay_with_instamojo($instamojo_credentials, $payable_amount, $after_success_url, $after_faild_url, $user)
    {

        $calculate_payable_charge = $this->calculate_payable_charge($payable_amount, 'instamojo');

        $environment = $instamojo_credentials->account_mode;
        $api_key = $instamojo_credentials->instamojo_api_key;
        $auth_token = $instamojo_credentials->instamojo_auth_token;

        if ($environment == 'Sandbox') {
            $url = 'https://test.instamojo.com/api/1.1/';
        } else {
            $url = 'https://www.instamojo.com/api/1.1/';
        }

        try {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url.'payment-requests/');
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER,
                ["X-Api-Key:$api_key",
                    "X-Auth-Token:$auth_token"]);
            $payload = [
                'purpose' => env('APP_NAME'),
                'amount' => $calculate_payable_charge->payable_with_charge,
                'phone' => '918160651749',
                'buyer_name' => $user->name,
                'redirect_url' => route('paymentgateway.response-instamojo'),
                'send_email' => true,
                'webhook' => 'http://www.example.com/webhook/',
                'send_sms' => true,
                'email' => $user->email,
                'allow_repeated_payments' => false,
            ];
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            $response = curl_exec($ch);
            curl_close($ch);
            $response = json_decode($response);

            session()->put('user', $user);
            session()->put('after_success_url', $after_success_url);
            session()->put('after_faild_url', $after_faild_url);
            session()->put('payable_amount', $payable_amount);
            session()->put('instamojo_credentials', $instamojo_credentials);
            session()->put('payable_with_charge', $calculate_payable_charge->payable_with_charge);

            return redirect($response->payment_request->longurl);
        } catch (Exception $ex) {
            $notification = $ex->getMessage();
            $notification = ['messege' => $notification, 'alert-type' => 'error'];

            return redirect()->back()->with($notification);
        }

    }

    public function instamojo_success(Request $request)
    {

        $instamojo_credentials = Session::get('instamojo_credentials');

        $input = $request->all();
        $environment = $instamojo_credentials->account_mode;
        $api_key = $instamojo_credentials->instamojo_api_key;
        $auth_token = $instamojo_credentials->instamojo_auth_token;

        if ($environment == 'Sandbox') {
            $url = 'https://test.instamojo.com/api/1.1/';
        } else {
            $url = 'https://www.instamojo.com/api/1.1/';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url.'payments/'.$request->get('payment_id'));
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            ["X-Api-Key:$api_key",
                "X-Auth-Token:$auth_token"]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $after_faild_url = Session::get('after_faild_url');

            return redirect($after_faild_url);
        } else {
            $data = json_decode($response);
        }

        if ($data->success == true) {
            if ($data->payment->status == 'Credit') {
                Session::put('after_success_gateway', 'Instamojo');
                Session::put('after_success_transaction', $request->get('payment_id'));
                Session::put('paid_amount', $data->payment->amount);
                $after_success_url = Session::get('after_success_url');

                return redirect($after_success_url);
            }
        } else {
            $after_faild_url = Session::get('after_faild_url');

            return redirect($after_faild_url);
        }
    }

    public function flutterwave_payment(Request $request)
    {

        $curl = curl_init();
        $tnx_id = $request->tnx_id;
        $url = "https://api.flutterwave.com/v3/transactions/$tnx_id/verify";
        $token = $request->secret_key;
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

            Session::put('after_success_gateway', 'Flutterwave');
            Session::put('after_success_transaction', $tnx_id);
            Session::put('payable_amount', $request->payable_amount);

            return response()->json(['message' => 'payment success']);

        } else {
            $notification = __('Payment Faild');

            return response()->json(['message' => $notification], 403);
        }

    }

    public function paystack_payment(Request $request)
    {

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
        curl_close($curl);
        $final_data = json_decode($response);
        if ($final_data->status == true) {
            Session::put('after_success_gateway', 'Paystack');
            Session::put('after_success_transaction', $request->tnx_id);
            Session::put('payable_amount', $request->payable_amount);

            return response()->json(['message' => 'payment success']);

        } else {
            $notification = __('Something went wrong, please try again');

            return response()->json(['message' => $notification], 403);
        }
    }
}
