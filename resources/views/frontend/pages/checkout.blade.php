@extends('frontend.layouts.master')
@section('meta_title', 'Checkout' . ' || ' . $setting->app_name)
@section('contents')
    <!-- breadcrumb-area -->
    <x-frontend.breadcrumb :title="__('Make Payment')" :links="[
        ['url' => route('home'), 'text' => __('Home')],
        ['url' => route('checkout.index'), 'text' => __('Make Payment')],
    ]" />
    <!-- breadcrumb-area-end -->

    <!-- checkout-area -->
    <div class="checkout__area section-py-120">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div id="show_currency_notifications">
                        @php
                            $BasicPaymentSupportedCurrenyListEnum =
                                \Modules\BasicPayment\app\Enums\BasicPaymentSupportedCurrenyListEnum::class;
                            $PaymentGatewaySupportedCurrenyListEnum =
                                \Modules\PaymentGateway\app\Enums\PaymentGatewaySupportedCurrenyListEnum::class;
                        @endphp
                        @if (session()->has('show_stripe_currency') && session()->get('show_stripe_currency'))
                            <div class="alert alert-warning">
                                Stripe {{ __('Support only those type of currencies') }} :
                                {{ is_array($BasicPaymentSupportedCurrenyListEnum::getStripeSupportedCurrencies()['all_currency_codes'])
                                    ? implode(', ', $BasicPaymentSupportedCurrenyListEnum::getStripeSupportedCurrencies()['all_currency_codes'])
                                    : '' }}
                            </div>
                        @endif
                        @if (session()->has('show_mollie_currency') && session()->get('show_mollie_currency'))
                            <div class="alert alert-warning">
                                {{ __('Mollie') }} {{ __('Support only those type of currencies') }} :
                                {{ is_array($PaymentGatewaySupportedCurrenyListEnum::getMollieSupportedCurrencies())
                                    ? implode(', ', $PaymentGatewaySupportedCurrenyListEnum::getMollieSupportedCurrencies())
                                    : '' }}
                            </div>
                        @endif
                        @if (session()->has('show_paypal_currency') && session()->get('show_paypal_currency'))
                            <div class="alert alert-warning">
                                {{ __('Paypal ') }} {{ __('Support only those type of currencies') }} :
                                {{ is_array($BasicPaymentSupportedCurrenyListEnum::getPaypalSupportedCurrencies())
                                    ? implode(', ', $BasicPaymentSupportedCurrenyListEnum::getPaypalSupportedCurrencies())
                                    : '' }}
                            </div>
                        @endif
                        @if (session()->has('show_instamojo_currency') && session()->get('show_instamojo_currency'))
                            <div class="alert alert-warning">
                                {{ __('Instamojo ') }} {{ __('Support only those type of currencies') }} :
                                {{ is_array($PaymentGatewaySupportedCurrenyListEnum::getInstamojoSupportedCurrencies())
                                    ? implode(', ', $PaymentGatewaySupportedCurrenyListEnum::getInstamojoSupportedCurrencies())
                                    : '' }}
                            </div>
                        @endif
                    </div>
                    <div class="wsus__payment_area">
                        <div class="row">
                            @if (Session::get('payable_amount') > 0)
                                @if ($basic_payment->stripe_status == 'active')
                                    <div class="col-lg-3 col-6 col-sm-4">
                                        <a class="wsus__single_payment" data-bs-toggle="modal" data-bs-target="#stripeModal"
                                            href="javascript:;">
                                            <img src="{{ asset($basic_payment->stripe_image) }}" alt="Pay with stripe"
                                                class="img-fluid w-100">
                                        </a>
                                    </div>
                                @endif
                                @if ($tzsmmpay_credentials->tzsmmpay_status == 'active')
                                    <div class="col-lg-3 col-6 col-sm-4">
                                        <a class="wsus__single_payment"  href="{{ route('pay-via-tzsmmpay') }}"
                                            href="javascript:;">
                                            <img src="{{ asset($tzsmmpay_credentials->tzsmmpay_image) }}" alt="Pay with TZSMM Pay"
                                                class="img-fluid w-100">
                                        </a>
                                    </div>
                                @endif

                                @if ($basic_payment->paypal_status == 'active')
                                    <div class="col-lg-3 col-6 col-sm-4">
                                        <a class="wsus__single_payment" href="{{ route('pay-via-paypal') }}">
                                            <img src="{{ asset($basic_payment->paypal_image) }}" alt="Pay with paypal"
                                                class="img-fluid w-100">
                                        </a>
                                    </div>
                                @endif


                                @if ($basic_payment->bank_status == 'active')
                                    <div class="col-lg-3 col-6 col-sm-4">
                                        <a class="wsus__single_payment" data-bs-toggle="modal" data-bs-target="#bankModal"
                                            href="javascript:;">
                                            <img src="{{ asset($basic_payment->bank_image) }}" alt="Pay with bank"
                                                class="img-fluid w-100">
                                        </a>
                                    </div>
                                @endif

                                @if ($razorpay_credentials->razorpay_status == 'active')
                                    <div class="col-lg-3 col-6 col-sm-4">
                                        <a href="javascript:;" class="wsus__single_payment" id="razorpayBtn">
                                            <img src="{{ asset($razorpay_credentials->razorpay_image) }}"
                                                alt="payment method" class="img-fluid w-100">
                                        </a>
                                    </div>

                                    <form action="{{ route('pay-via-razorpay') }}" method="POST" class="d-none">
                                        @csrf

                                        <input type="hidden" name="payable_amount" value="{{ $payable_amount }}">

                                        <script src="https://checkout.razorpay.com/v1/checkout.js" data-key="{{ $razorpay_credentials->razorpay_key }}"
                                            data-currency="{{ $razorpay_credentials->currency_code }}"
                                            data-amount="{{ $razorpay_credentials->payable_with_charge * 100 }}" data-buttontext="{{ __('Pay') }}"
                                            data-name="{{ $razorpay_credentials->razorpay_name }}"
                                            data-description="{{ $razorpay_credentials->razorpay_description }}"
                                            data-image="{{ asset($razorpay_credentials->razorpay_image) }}" data-prefill.name="{{ userAuth()->name }}"
                                            data-prefill.email="{{ userAuth()->email }}" data-theme.color="{{ $razorpay_credentials->razorpay_theme_color }}">
                                        </script>
                                    </form>
                                @endif


                                @if ($mollie_credentials->mollie_status == 'active')
                                    <div class="col-lg-3 col-6 col-sm-4">
                                        <a href="{{ route('pay-via-mollie') }}" class="wsus__single_payment">
                                            <img src="{{ asset($mollie_credentials->mollie_image) }}" alt="payment method"
                                                class="img-fluid w-100">
                                        </a>
                                    </div>
                                @endif

                                @if ($instamojo_credentials->instamojo_status == 'active')
                                    <div class="col-lg-3 col-6 col-sm-4">
                                        <a href="{{ route('pay-via-instamojo') }}" class="wsus__single_payment">
                                            <img src="{{ asset($instamojo_credentials->instamojo_image) }}"
                                                alt="instamojo method" class="img-fluid w-100">
                                        </a>
                                    </div>
                                @endif

                                @if ($flutterwave_credentials->flutterwave_status == 'active')
                                    <div class="col-lg-3 col-6 col-sm-4">
                                        <a href="javascript:;" class="wsus__single_payment" onclick="flutterwavePayment()">
                                            <img src="{{ asset($flutterwave_credentials->flutterwave_image) }}"
                                                alt="flutterwave method" class="img-fluid w-100">
                                        </a>
                                    </div>
                                @endif

                                @if ($payment_setting->paystack_status == 'active')
                                    <div class="col-lg-3 col-6 col-sm-4">
                                        <a href="javascript:;" class="wsus__single_payment" onclick="payWithPaystack()">
                                            <img src="{{ asset($paystack_credentials->paystack_image) }}"
                                                alt="paystack method" class="img-fluid w-100">
                                        </a>
                                    </div>
                                @endif
                            @else
                                <div class="col-lg-3 col-6 col-sm-4">
                                    <form action="{{ route('pay-via-free-gateway') }}" method="POST">
                                        @csrf
                                        <button class="wsus__single_payment border-0">
                                            <img src="{{ asset('uploads/website-images/buy_now.png') }}"
                                                alt="Pay with stripe" class="img-fluid w-100">
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="cart__collaterals-wrap payment_slidebar">
                        <h2 class="title">{{ __('Cart totals') }}</h2>
                        <ul class="list-wrap pb-0">
                            <li>{{ __('Total Items') }}<span>{{ count(Cart::content()) }}</span></li>
                            <li>
                                @if (Session::has('coupon_code'))
                                    <p class="coupon-discount m-0">
                                        <span>{{ __('Discount') }}</span>
                                        <br>
                                        <small>{{ $coupon }} ({{ $discountPercent }} %)<a class="ms-2 text-danger"
                                                href="/remove-coupon">×</a></small>
                                    </p>
                                    <span class="discount-amount">{{ currency($discountAmount) }}</span>
                                @else
                                    <p class="coupon-discount m-0">
                                        <span>{{ __('Discount') }}</span>
                                    </p>
                                    <span class="discount-amount">{{ currency(0) }}</span>
                                @endif
                            </li>
                            <li>{{ __('Total') }} <span class="amount">{{ $total }}</span></li>

                            @if (Session::get('payable_amount') > 0)
                                <h6 class="bold payable-bold">{{ __('payable with gateway charge') }}:</h6>

                                @if (payable_with_charges(Session::get('payable_amount')))
                                    @foreach (payable_with_charges(Session::get('payable_amount')) as $key => $value)
                                        @if (
                                            $key == 'stripe' &&
                                                $BasicPaymentSupportedCurrenyListEnum::isStripeSupportedCurrencies(getSessionCurrency()) &&
                                                $basic_payment->stripe_status == 'active')
                                            <p class="payable-text">{{ str($key)->title() }}: <span>{{ $value }}
                                                    {{ getSessionCurrency() }}</span></p>
                                        @elseif (
                                            $key == 'paypal' &&
                                                $BasicPaymentSupportedCurrenyListEnum::isPaypalSupportedCurrencies(getSessionCurrency()) &&
                                                $basic_payment->paypal_status == 'active')
                                            <p class="payable-text">{{ str($key)->title() }}: <span>{{ $value }}
                                                    {{ getSessionCurrency() }}</span></p>
                                        @elseif (
                                            $key == 'mollie' &&
                                                $PaymentGatewaySupportedCurrenyListEnum::isMollieSupportedCurrencies(getSessionCurrency()) &&
                                                $mollie_credentials->mollie_status == 'active')
                                            <p class="payable-text">{{ str($key)->title() }}: <span>{{ $value }}
                                                    {{ getSessionCurrency() }}</span></p>
                                        @elseif (
                                            $key == 'razorpay' &&
                                                $PaymentGatewaySupportedCurrenyListEnum::isRazorpaySupportedCurrencies(getSessionCurrency()) &&
                                                $razorpay_credentials->razorpay_status == 'active')
                                            <p class="payable-text">{{ str($key)->title() }}: <span>{{ $value }}
                                                    {{ getSessionCurrency() }}</span></p>
                                        @elseif (
                                            $key == 'instamojo' &&
                                                $PaymentGatewaySupportedCurrenyListEnum::isInstamojoSupportedCurrencies(getSessionCurrency()) &&
                                                $instamojo_credentials->instamojo_status == 'active')
                                            <p class="payable-text">{{ str($key)->title() }}: <span>{{ $value }}
                                                    {{ getSessionCurrency() }}</span></p>
                                        @elseif (
                                            $key == 'flutterwave' &&
                                                $PaymentGatewaySupportedCurrenyListEnum::isFlutterwaveSupportedCurrencies(getSessionCurrency()) &&
                                                $flutterwave_credentials->flutterwave_status == 'active')
                                            <p class="payable-text">{{ str($key)->title() }}: <span>{{ $value }}
                                                    {{ getSessionCurrency() }}</span></p>
                                        @elseif (
                                            $key == 'paystack' &&
                                                $PaymentGatewaySupportedCurrenyListEnum::isPaystackSupportedCurrencies(getSessionCurrency()) &&
                                                $payment_setting->paystack_status == 'active')
                                            <p class="payable-text">{{ str($key)->title() }}: <span>{{ $value }}
                                                    {{ getSessionCurrency() }}</span></p>
                                        @elseif ($key == 'bank' && $basic_payment->bank_status == 'active')
                                            <p class="payable-text">{{ str($key)->title() }}: <span>{{ $value }}
                                                    {{ getSessionCurrency() }}</span></p>
                                        @elseif(
                                            $key != 'stripe' &&
                                                $key != 'paypal' &&
                                                $key !== 'mollie' &&
                                                $key !== 'razorpay' &&
                                                $key !== 'instamojo' &&
                                                $key !== 'flutterwave' &&
                                                $key !== 'bank' &&
                                                $key !== 'paystack')
                                            <p class="payable-text">{{ str($key)->title() }}: <span>{{ $value }}
                                                    {{ getSessionCurrency() }}</span></p>
                                        @endif
                                    @endforeach
                                @endif
                            @endif
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- checkout-area-end -->
    @if ($basic_payment->stripe_status == 'active')
        <!-- stripe Modal -->
        <div class="modal fade" id="stripeModal" tabindex="-1" aria-labelledby="stripeModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">
                            <h3>{{ __('Stripe Payment') }}</h3>
                        </h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form role="form" class="require-validation" action="{{ route('pay-via-stripe') }}"
                            method="post" data-cc-on-file="false" data-stripe-publishable-key="{{ $strip_key }}"
                            id="payment-form">
                            @csrf
                            <div class="row">
                                <div class="mt-2 col-md-12">
                                    <div class="form-group required">
                                        <label for="card_number">{{ __('Card Number') }}<span
                                                class="text-danger">*</span></label>
                                        <input autocomplete='off' id="card_number" class='form-control card-number'
                                            size='20' type='text' name="card_number"
                                            placeholder="{{ __('Card Number') }}" autocomplete="off">

                                    </div>
                                </div>
                                <div class="mt-4 col-md-12">
                                    <div class="form-group expiration required">
                                        <label for="month">{{ __('Month') }}<span
                                                class="text-danger">*</span></label>
                                        <input id="month" class='form-control card-expiry-month' size='2'
                                            type='text' name="month" placeholder="{{ __('MM') }}"
                                            autocomplete="off">
                                    </div>
                                </div>
                                <div class="mt-4 col-md-12">
                                    <div class="form-group expiration required">
                                        <label for="year">{{ __('Year') }}<span
                                                class="text-danger">*</span></label>
                                        <input id="year" class='form-control card-expiry-year' size='4'
                                            type='text' name="year" autocomplete="off"
                                            placeholder="{{ __('YY') }}">
                                    </div>
                                </div>
                                <div class="my-4 col-md-12">
                                    <div class="form-group cvc required">
                                        <label for="cvc">{{ __('CVC') }}<span
                                                class="text-danger">*</span></label>
                                        <input id="cvc" autocomplete='off' class='form-control card-cvc'
                                            size='4' type='text' name="cvc"
                                            placeholder="{{ __('CVC') }}" autocomplete="off">
                                    </div>
                                </div>
                            </div>

                            <div class='form-row row'>
                                <div class='col-md-12 error form-group d-none '>
                                    <div class='alert-danger alert'>
                                        {{ __('Please correct the errors and try again.') }}
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger"
                                    data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <button class="btn btn-primary btn-block" id="stripePaymentSubmitButton"
                                    type="submit">{{ __('Payment') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($basic_payment->bank_status == 'active')
        {{-- bank modal --}}
        <div class="payment_modal">
            <div class="modal fade" id="bankModal" tabindex="-1" aria-labelledby="bankModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="bankModalLabel">{{ __('Pay via direct bank') }}</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">

                            {!! clean(nl2br($basic_payment->bank_information)) !!}

                            <form action="{{ route('pay-via-bank') }}" method="post">
                                @csrf

                                <!-- Bank Name -->
                                <div class="my-1 form-group">
                                    <label for="bank_name">{{ __('Bank Name') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="bank_name" name="bank_name"
                                        placeholder="{{ __('Your bank name') }}" required>
                                </div>

                                <!-- Account Number -->
                                <div class="my-1 form-group">
                                    <label for="account_number">{{ __('Account Number') }} <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="account_number" name="account_number"
                                        placeholder="{{ __('Your bank account number') }}" required>
                                </div>

                                <!-- Routing Number -->
                                <div class="my-1 form-group">
                                    <label for="routing_number">{{ __('Routing Number') }}</label>
                                    <input type="text" class="form-control" id="routing_number" name="routing_number"
                                        placeholder="{{ __('Your bank routing number') }}">
                                </div>

                                <!-- Branch -->
                                <div class="my-1 form-group">
                                    <label for="branch">{{ __('Branch') }} <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="branch" name="branch"
                                        placeholder="{{ __('Your bank branch name') }}" required>
                                </div>

                                <button class="mt-2 btn btn-primary">{{ __('Submit') }}</button>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    @if ($basic_payment->stripe_status == 'active')
        <script type="text/javascript" src="https://js.stripe.com/v2/"></script>
        <script type="text/javascript">
            "use strict";
            $(function() {
                var $form = $(".require-validation");

                $('form.require-validation').on('submit', function(e) {
                    $('#stripePaymentSubmitButton').prop('disabled', true);

                    var $form = $(".require-validation"),
                        inputSelector = ['input[type=email]', 'input[type=password]',
                            'input[type=text]', 'input[type=file]',
                            'textarea'
                        ].join(', '),
                        $inputs = $form.find('.required').find(inputSelector),
                        $errorMessage = $form.find('div.error'),
                        valid = true;
                    $errorMessage.addClass('d-none');

                    $('.has-error').removeClass('has-error');
                    $inputs.each(function(i, el) {
                        var $input = $(el);
                        if ($input.val() === '') {
                            $input.parent().addClass('has-error');
                            $errorMessage.removeClass('d-none');
                            e.preventDefault();
                        }
                    });

                    if (!$form.data('cc-on-file')) {
                        e.preventDefault();
                        Stripe.setPublishableKey($form.data('stripe-publishable-key'));
                        Stripe.createToken({
                            number: $('.card-number').val(),
                            cvc: $('.card-cvc').val(),
                            exp_month: $('.card-expiry-month').val(),
                            exp_year: $('.card-expiry-year').val()
                        }, stripeResponseHandler);
                    }

                });

                function stripeResponseHandler(status, response) {
                    if (response.error) {
                        $('#stripePaymentSubmitButton').prop('disabled', false);
                        $('.error')
                            .removeClass('d-none')
                            .find('.alert')
                            .text(response.error.message);
                    } else {
                        /* token contains id, last4, and card type */
                        var token = response['id'];

                        $form.find('input[type=text]').empty();
                        $form.append("<input type='hidden' name='stripeToken' value='" + token + "' />");
                        $form.get(0).submit();
                    }
                }
            });
        </script>
    @endif


    @if ($razorpay_credentials->razorpay_status == 'active')
        {{-- start razorpay payment --}}
        <script>
            "use strict";
            $(function() {
                $("#razorpayBtn").on("click", function() {
                    var isCurrencyAllowed =
                        "{{ $PaymentGatewaySupportedCurrenyListEnum::isRazorpaySupportedCurrencies($razorpay_credentials->currency_code) ? '1' : '0' }}";

                    if (isCurrencyAllowed == 0) {
                        toastr.error('This currency is not supported by Razorpay');
                        $('#show_currency_notifications').empty();
                        $('#show_currency_notifications').append(
                            `<div class='alert alert-warning'>
                        Razorpay {{ __('Support only those type of currencies') }} :
                        {{ is_array($PaymentGatewaySupportedCurrenyListEnum::getRazorpaySupportedCurrencies())
                            ? implode(', ', $PaymentGatewaySupportedCurrenyListEnum::getRazorpaySupportedCurrencies())
                            : '' }}
                    </div>`
                        );
                        return;
                    }

                    $(".razorpay-payment-button").trigger('click');
                })
            });
        </script>
    @endif

    @if ($flutterwave_credentials->flutterwave_status == 'active')
        {{-- start flutterwave payment --}}
        <script src="https://checkout.flutterwave.com/v3.js"></script>

        <script>
            "use strict";

            function flutterwavePayment() {

                var isDemo = "{{ env('APP_MODE') }}"
                if (isDemo == 'DEMO') {
                    toastr.error('This Is Demo Version. You Can Not Change Anything');
                    return;
                }

                var isCurrencyAllowed =
                    "{{ $PaymentGatewaySupportedCurrenyListEnum::isFlutterwaveSupportedCurrencies($flutterwave_credentials->currency_code) ? '1' : '0' }}";

                if (isCurrencyAllowed == 0) {
                    toastr.error('This currency is not supported by Flutterwave');
                    $('#show_currency_notifications').empty();
                    $('#show_currency_notifications').append(
                        `<div class='alert alert-warning'>
                        Flutterwave {{ __('Support only those type of currencies') }} :
                        {{ is_array($PaymentGatewaySupportedCurrenyListEnum::getFlutterwaveSupportedCurrencies())
                            ? implode(', ', array_keys($PaymentGatewaySupportedCurrenyListEnum::getFlutterwaveSupportedCurrencies()))
                            : '' }}
                    </div>`
                    );
                    return;
                }

                FlutterwaveCheckout({
                    public_key: "{{ $flutterwave_credentials->flutterwave_public_key }}",
                    tx_ref: "{{ substr(rand(0, time()), 0, 10) }}",
                    amount: "{{ $flutterwave_credentials->payable_with_charge }}",
                    currency: "{{ $flutterwave_credentials->currency_code }}",
                    country: "{{ $flutterwave_credentials->country_code }}",
                    payment_options: " ",
                    customer: {
                        email: "{{ $user->email }}",
                        phone_number: "{{ $user->phone }}",
                        name: "{{ $user->name }}",
                    },
                    callback: function(data) {
                        var tnx_id = data.transaction_id;
                        var _token = "{{ csrf_token() }}";
                        var payable_amount = "{{ $payable_amount }}";
                        $.ajax({
                            type: 'post',
                            data: {
                                tnx_id,
                                _token,
                                payable_amount,
                            },
                            url: "{{ url('/pay-via-flutterwave') }}",
                            success: function(response) {
                                window.location.href =
                                    "{{ route('payment-addon-success') }}";
                            },
                            error: function(err) {
                                toastr.error("{{ __('Payment faild, please try again') }}");
                                window.location.reload();
                            }
                        });
                    },
                    customizations: {
                        title: "{{ $flutterwave_credentials->flutterwave_app_name }}",
                        logo: "{{ asset($flutterwave_credentials->flutterwave_image) }}",
                    },
                });

            }
        </script>
        {{-- end flutterwave payment --}}
    @endif

    @if ($payment_setting->paystack_status == 'active')
        {{-- paystack start --}}

        <script src="https://js.paystack.co/v1/inline.js"></script>

        <script>
            "use strict";

            function payWithPaystack() {

                var isDemo = "{{ env('APP_MODE') }}"
                if (isDemo == 'DEMO') {
                    toastr.error('This Is Demo Version. You Can Not Change Anything');
                    return;
                }

                var isCurrencyAllowed =
                    "{{ $PaymentGatewaySupportedCurrenyListEnum::isPaystackSupportedCurrencies($paystack_credentials->currency_code) ? '1' : '0' }}";

                if (isCurrencyAllowed == 0) {
                    toastr.error('This currency is not supported by Paystack');
                    $('#show_currency_notifications').empty();
                    $('#show_currency_notifications').append(
                        `<div class='alert alert-warning'>
                    Paystack {{ __('Support only those type of currencies') }} :
                    {{ is_array($PaymentGatewaySupportedCurrenyListEnum::getPaystackSupportedCurrencies())
                        ? implode(', ', $PaymentGatewaySupportedCurrenyListEnum::getPaystackSupportedCurrencies())
                        : '' }}
                </div>`
                    );
                    return;
                }

                var handler = PaystackPop.setup({
                    key: '{{ $paystack_credentials->paystack_public_key }}',
                    email: '{{ $user->email }}',
                    amount: '{{ $paystack_credentials->payable_with_charge * 100 }}',
                    currency: "{{ $paystack_credentials->currency_code }}",
                    callback: function(response) {
                        let reference = response.reference;
                        let tnx_id = response.transaction;
                        let _token = "{{ csrf_token() }}";
                        var payable_amount = "{{ $payable_amount }}";
                        var secret_key = "{{ $paystack_credentials->paystack_secret_key }}";

                        $.ajax({
                            type: "post",
                            data: {
                                reference,
                                tnx_id,
                                _token,
                                payable_amount,
                                secret_key,
                            },
                            url: "{{ route('pay-via-paystack') }}",
                            success: function(response) {
                                window.location.href =
                                    "{{ route('payment-addon-success') }}";
                            },
                            error: function(response) {
                                toastr.error("{{ __('Payment failed, please try again') }}");
                                window.location.reload();
                            }
                        });
                    },
                    onClose: function() {
                        alert('window closed');
                    }
                });
                handler.openIframe();
            }
        </script>
    @endif
@endpush
