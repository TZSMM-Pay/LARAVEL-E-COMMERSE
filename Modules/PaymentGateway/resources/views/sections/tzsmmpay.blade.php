<div class="tab-pane fade active show" id="tzsmmpay_tab" role="tabpanel">
    <form action="{{ route('admin.tzsmmpay-update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">

            <div class="form-group col-md-6">
                <label for="">{{ __('Gateway charge (%)') }}</label>
                <input type="text" class="form-control" name="tzsmmpay_charge"
                    value="{{ $payment_setting->tzsmmpay_charge }}">
            </div>

            <div class="form-group col-md-6">
                <label for="">{{ __('tzsmmpay API Key') }}</label>
                <input type="text" class="form-control" name="tzsmmpay_api_key"
                    value="{{ $payment_setting->tzsmmpay_api_key }}">
            </div>
        </div>

        <div class="form-group">
            <label>{{ __('New Image') }}<span
                    class="text-danger">*</span></label>
            <div id="image-preview-tzsmmpay" class="image-preview">
                <label for="image-upload-tzsmmpay"
                    id="image-label-tzsmmpay">{{ __('Image') }}</label>
                <input type="file" name="tzsmmpay_image" id="image-upload-tzsmmpay">
            </div>

        </div>
        <div class="form-group">
            <label class="d-flex align-items-center">
                <input type="hidden" value="inactive" name="tzsmmpay_status" class="custom-switch-input">
                <input type="checkbox" value="active" name="tzsmmpay_status" class="custom-switch-input"
                    {{ $payment_setting?->tzsmmpay_status == 'active' ? 'checked' : '' }}>
                <span class="custom-switch-indicator"></span>
                <span class="custom-switch-description">{{ __('Status') }}</span>
            </label>
        </div>

        <button class="btn btn-primary">{{ __('Update') }}</button>
    </form>
</div>
