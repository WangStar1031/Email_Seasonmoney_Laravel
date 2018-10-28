@extends('layouts.frontend')

@section('title', trans('messages.campaigns') . " - " . trans('messages.setup'))
	
@section('page_script')
	<script type="text/javascript" src="{{ URL::asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
		
    <script type="text/javascript" src="{{ URL::asset('js/validate.js') }}"></script>
@endsection

@section('page_header')
	
			<div class="page-title">
				<ul class="breadcrumb breadcrumb-caret position-right">
					<li><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
                    <li><a href="{{ action("AutomationController@index") }}">{{ trans('messages.automations') }}</a></li>
                    <li><a href="{{ action("AutomationController@workflow", $auto_event->automation->uid) }}">{{ $auto_event->automation->name }}</a></li>
                    <li><a href="{{ action("AutomationController@workflow", $auto_event->automation->uid) }}">{{ trans('messages.emails') }}</a></li>
				</ul>
				<h1>
					<span class="text-semibold"><i class="icon-paperplane"></i> {{ $campaign->name }}</span>
				</h1>

				@include('auto_events.campaigns._steps', ['current' => 1])
			</div>

@endsection

@section('content')
                <form action="{{ action('AutoEventController@campaignSetup', ['uid' => $auto_event->uid, 'campaign_uid' => $campaign->uid]) }}" method="POST" class="form-validate-jqueryz">
					{{ csrf_field() }}
                    <input type="hidden" name="_method" value="PATCH">
					
					<div class="row">
						<div class="col-md-6 list_select_box" target-box="segments-select-box" segments-url="{{ action('SegmentController@selectBox') }}">
							@include('helpers.form_control', ['type' => 'text',
								'name' => 'name',
								'label' => trans('messages.name_your_email'),
								'value' => $campaign->name,
								'rules' => $campaign->automatedCampaignRules(),
								'help_class' => 'campaign'
							])
							
                            @include('helpers.form_control', ['type' => 'text',
								'name' => 'subject',
								'label' => trans('messages.email_subject'),
								'value' => $campaign->subject,
								'rules' => $campaign->automatedCampaignRules(),
								'help_class' => 'campaign'
							])
                                                            
                            @include('helpers.form_control', ['type' => 'text',
								'name' => 'from_name',
								'label' => trans('messages.from_name'),
								'value' => $campaign->from_name,
								'rules' => $campaign->automatedCampaignRules(),
								'help_class' => 'campaign'
							])
                            
							<div class="hiddable-cond" data-control="[name=use_default_sending_server_from_email]" data-hide-value="1">
								@include('helpers.form_control', ['type' => 'text',
									'name' => 'from_email',
									'label' => trans('messages.from_email'),
									'value' => $campaign->from_email,
									'rules' => $campaign->automatedCampaignRules(),
									'help_class' => 'campaign'
								])
							</div>
								
							@include('helpers.form_control', ['type' => 'checkbox2',
								'name' => 'use_default_sending_server_from_email',
								'label' => trans('messages.use_sending_server_default_value'),
								'value' => $campaign->use_default_sending_server_from_email,
								'rules' => $campaign->automatedCampaignRules(),
								'help_class' => 'campaign',
								'options' => ['0','1'],
							])
                                                            
                            @include('helpers.form_control', ['type' => 'text',
								'name' => 'reply_to',
								'label' => trans('messages.reply_to'),
								'value' => $campaign->reply_to,
								'rules' => $campaign->automatedCampaignRules(),
								'help_class' => 'campaign'
							])
							<div class="hide">
								@include('helpers.form_control', ['type' => 'select',
									'name' => 'tracking_domain_uid',
									'label' => trans('messages.tracking_domain'),
									'value' => (is_object($campaign->trackingDomain) ? $campaign->trackingDomain->uid : NULL),
									'rules' => $campaign->automatedCampaignRules(),
									'help_class' => 'campaign',
									'include_blank' => trans('messages.default'),
									'options' => $sendingDomainOptions
								])
							</div>
						</div>
						<div class="col-md-6 segments-select-box">
							@include('helpers.form_control', [
								'type' => 'radio',
								'name' => 'type',
								'class' => '',
								'label' => trans('messages.choose_email_type'),
								'value' => $campaign->type,
								'options' => Acelle\Model\Campaign::getTypeSelectOptions(),
								'rules' => $campaign->automatedCampaignRules(),
							])
							
                            <div class="form-group checkbox-right-switch">
								
								<div class="hiddable-box" data-control="input[name=type]" data-hide-value="plain-text">
									@include('helpers.form_control', ['type' => 'checkbox',
															'name' => 'track_open',
															'label' => trans('messages.track_opens'),
															'value' => $campaign->track_open,
															'options' => [false,true],
															'help_class' => 'campaign',
															'rules' => $campaign->automatedCampaignRules()
														])
							
									@include('helpers.form_control', ['type' => 'checkbox',
															'name' => 'track_click',
															'label' => trans('messages.track_clicks'),
															'value' => $campaign->track_click,
															'options' => [false,true],
															'help_class' => 'campaign',
															'rules' => $campaign->automatedCampaignRules()
														])
								</div>
								
                                
                                @include('helpers.form_control', ['type' => 'checkbox',
                                                                'name' => 'sign_dkim',
                                                                'label' => trans('messages.sign_dkim'),
                                                                'value' => $campaign->sign_dkim,
                                                                'options' => [false,true],
                                                                'help_class' => 'campaign',
                                                                'rules' => $campaign->automatedCampaignRules()
                                                            ])
								<div class="hiddable-box" data-control="input[name=type]" data-hide-value="regular">
									<div class="alert alert-warning">
										{!! trans('messages.automation_email.plain_text.open_click_tracking_wanring') !!}
									</div>
								</div>
                            </div>
						</div>
					</div>
					<hr>
					<div class="text-right">
						<button class="btn bg-teal-800">{{ trans('messages.save_and_next') }} <i class="icon-arrow-right7"></i> </button>
                        <a href="{{ action("AutomationController@workflow", $auto_event->automation->uid) }}" class="btn bg-grey-800">
							<i class="icon-stack-play mr-5"></i> {{ trans('messages.back_to_workflow') }}
						</a>
					</div>
					
				<form>
					
				
@endsection
