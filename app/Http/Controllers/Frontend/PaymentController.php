public function pay_via_tzsmmpay(Request $request)
    {
        if (!PaymentGatewaySupportedCurrenyListEnum::istzsmmpaySupportedCurrencies(getSessionCurrency())) {
            session()->flash('show_tzsmmpay_currency');

            $notification = trans('You are trying to use unsupported currency');
            $notification = ['messege' => $notification, 'alert-type' => 'warning'];

            return back()->with($notification);
        }

        $payment_setting = $this->get_payment_gateway_info();

        $after_success_url = route('payment-addon-success');
        $after_faild_url = route('payment-addon-faild');

        $user = userAuth();

        $tzsmmpay_credentials = (object) [
            'tzsmmpay_api_key' => $payment_setting->tzsmmpay_api_key,
        ];

        $tzsmmpay_payment = new AddonPaymentController();

        return $tzsmmpay_payment->pay_with_tzsmmpay($request, $tzsmmpay_credentials, $request->payable_amount, $after_success_url, $after_faild_url, $user);
    }
