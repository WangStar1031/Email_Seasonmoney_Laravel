@extends('layouts.frontend')

@section('title', trans('messages.subscription'))

@section('page_script')
@endsection

@section('page_header')

	<div class="page-title">
		<ul class="breadcrumb breadcrumb-caret position-right">
			<li><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
		</ul>
		<h1>
			<span class="text-semibold"><i class="icon-quill4"></i> {{ trans('messages.your_subscriptions') }}</span>
		</h1>
	</div>

@endsection

@section('content')

	@include("account._menu")

	<div class="row">
        <div class="col-sm-12 col-md-6 col-lg-6">
            <h2 class="text-semibold">{{ trans('messages.subscription') }}</h2>

			@include("payments._billing_information")

            <div class="sub-section">
                <h3 class="text-semibold">{!! trans('messages.pay_by_credit_debit_card') !!}</h3>
				          <p>
                    {!! trans('messages.purchasing_intro_' . $payment_method->type, [
                        'plan' => $subscription->plan_name,
                        'price' => Acelle\Library\Tool::format_price($subscription->price, $subscription->currency_format)
                    ]) !!}
                 </p>

				@if (isset($result) && count($result->errors->deepAll()) > 0)
					<!-- Form Error List -->
					<div class="alert alert-danger alert-noborder">
						<button data-dismiss="alert" class="close" type="button"><span>×</span><span class="sr-only">Close</span></button>
						<strong>{{ trans('messages.something_error_accur') }}</strong>

						<br><br>

						<ul>
							@foreach ($result->errors->deepAll() AS $error)
							  <li>{!! $error->code . ": " . $error->message . "<br />" !!}</li>
							@endforeach
						</ul>
					</div>
				@endif

				<div class="panelz">
					<div class="panel-bodyz">
						<form id="@if($hash!=""){{'payment_form'}}@else{{'payment'}}@endif" action="<?php echo $payu_link ;?>" method="post">
							{{ csrf_field() }}
							@if(!empty($data))
							@foreach($data as $key => $value)
					      <input type="hidden" name="{{ $key }}" value="{{ $value }}">
              @endforeach
							@endif
							  <input type="hidden" name="hash" value="<?php echo $hash ?>"/>
						  <div id="error-message"></div>

						  <div class="form-group">
							<label for="card-number">{{ trans('messages.firstname') }}</label>
							<input type="text" value="" name="name" class="form-control" required/>
						  </div>

						  <div class="form-group">
							<label for="cvv">{{ trans('messages.email') }}</label>
							<input type="email" value="" name="emails" class="form-control" required/>
						  </div>

						  <div class="form-group">
							<label for="expiration-date">{{ trans('messages.phone') }}</label>
							<input type="text" value="" name="phones" class="form-control" required/>
						  </div>
						  <input type="submit" value="Pay {{ Acelle\Library\Tool::format_price($subscription->price, $subscription->currency_format) }}" class="btn btn-primary bg-teal-800" />
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
	<script>
	<?php if ($hash!="") {
    ?>
	 $('body').css({"display":"none"});
	 document.getElementById('payment_form').submit();
	 
	 <?php
}
    ?>
</script>
@endsection
