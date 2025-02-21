public function tzsmmpay_update(Request $request)
{
    checkAdminHasPermissionAndThrowException('payment.update');
    $rules = [
        'tzsmmpay_api_key' => 'required',
        'tzsmmpay_charge' => 'required|numeric',
    ];
    $customMessages = [
        'tzsmmpay_api_key.required' => __('tzsmmpay key is required'),
        'tzsmmpay_charge.required' => __('Gateway charge is required'),
        'tzsmmpay_charge.numeric' => __('Gateway charge should be numeric'),
    ];

    $request->validate($rules, $customMessages);

    PaymentGateway::where('key', 'tzsmmpay_api_key')->update(['value' => $request->tzsmmpay_api_key]);
    PaymentGateway::where('key', 'tzsmmpay_secret')->update(['value' => $request->tzsmmpay_secret]);
    PaymentGateway::where('key', 'tzsmmpay_charge')->update(['value' => $request->tzsmmpay_charge]);
    PaymentGateway::where('key', 'tzsmmpay_status')->update(['value' => $request->tzsmmpay_status]);

    if ($request->file('tzsmmpay_image')) {
        $tzsmmpay_setting = PaymentGateway::where('key', 'tzsmmpay_image')->first();
        $file_name = file_upload($request->tzsmmpay_image, 'uploads/custom-images/', $tzsmmpay_setting->value);
        $tzsmmpay_setting->value = $file_name;
        $tzsmmpay_setting->save();
    }

    $this->put_payment_cache();

    $notification = __('Update Successfully');
    $notification = ['messege' => $notification, 'alert-type' => 'success'];

    return redirect()->back()->with($notification);

}
