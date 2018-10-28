<ul class="nav navbar-nav">
				<li rel0="HomeController">
					<a href="{{ action('HomeController@index') }}">
						<i class="icon-home"></i> {{ trans('messages.dashboard') }}
					</a>
				</li>
				<li rel0="CampaignController">
					<a href="{{ action('CampaignController@index') }}">
						<i class="icon-paperplane"></i> {{ trans('messages.campaigns') }}
					</a>
				</li>
				<li rel0="AutomationController">
					<a href="{{ action('AutomationController@index') }}">
						<i class="icon-alarm-check"></i> {{ trans('messages.Automations') }}
					</a>
				</li>
				<li
					rel0="MailListController"
					rel1="FieldController"
					rel2="SubscriberController"
					rel3="SegmentController"
				>
					<a href="{{ action('MailListController@index') }}"><i class="icon-address-book2"></i> {{ trans('messages.lists') }}</a>
				</li>
                <li rel0="TemplateController">
					<a href="{{ action('TemplateController@index') }}">
						<i class="icon-magazine"></i> {{ trans('messages.templates') }}
					</a>
				</li>

				@if (
					Auth::user()->admin->getPermission("sending_domain_read") != 'no'
					|| Auth::user()->admin->getPermission("sending_server_read") != 'no'
					|| Auth::user()->admin->getPermission("bounce_handler_read") != 'no'
					|| Auth::user()->admin->getPermission("fbl_handler_read") != 'no'
					|| Auth::user()->admin->getPermission("email_verification_server_read") != 'no'
					|| Auth::user()->admin->can('read', new \Acelle\Model\SubAccount())
				)
					<li class="dropdown language-switch"
						rel0="BounceHandlerController"
						rel1="FeedbackLoopHandlerController"
						rel2="SendingServerController"
						rel3="SendingDomainController"
						rel3="SubAccountController"
					>
						<a class="dropdown-toggle" data-toggle="dropdown">
							<i class="glyphicon glyphicon-transfer"></i> {{ trans('messages.sending') }}
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							@if (Auth::user()->admin->getPermission("sending_server_read") != 'no')
								<li rel0="SendingServerController">
									<a href="{{ action('Admin\SendingServerController@index') }}">
										<i class="icon-server"></i> {{ trans('messages.sending_severs') }}
									</a>
								</li>
							@endif
							@if (Auth::user()->admin->can('read', new \Acelle\Model\SubAccount()))
								<li rel0="SubAccountController">
									<a href="{{ action('Admin\SubAccountController@index') }}">
										<i class="icon-drive"></i> {{ trans('messages.sub_accounts') }}
									</a>
								</li>
							@endif
							@if (Auth::user()->admin->getPermission("bounce_handler_read") != 'no')
								<li rel0="BounceHandlerController">
									<a href="{{ action('Admin\BounceHandlerController@index') }}">
										<i class="glyphicon glyphicon-share"></i> {{ trans('messages.bounce_handlers') }}
									</a>
								</li>
							@endif
							@if (Auth::user()->admin->getPermission("fbl_handler_read") != 'no')
								<li rel0="FeedbackLoopHandlerController">
									<a href="{{ action('Admin\FeedbackLoopHandlerController@index') }}">
										<i class="glyphicon glyphicon-retweet"></i> {{ trans('messages.feedback_loop_handlers') }}
									</a>
								</li>
							@endif
							@if (Auth::user()->admin->getPermission("sending_domain_read") != 'no')
								<li rel0="SendingDomainController">
									<a href="{{ action('Admin\SendingDomainController@index') }}">
										<i class="icon-earth"></i> {{ trans('messages.sending_domains') }}
									</a>
								</li>
							@endif
							@if (Auth::user()->admin->getPermission("email_verification_server_read") != 'no')
								<li rel0="EmailVerificationServerController">
									<a href="{{ action('Admin\EmailVerificationServerController@index') }}">
										<i class="icon-database-check"></i> {{ trans('messages.email_verification_servers') }}
									</a>
								</li>
							@endif
						</ul>
					</li>
				@endif
				<li class="dropdown language-switch"
					rel1="LayoutController"
					rel2="LanguageController"
					rel3="SettingController"
				>
					<a class="dropdown-toggle" data-toggle="dropdown">
						<i class="icon-gear"></i> {{ trans('messages.setting') }}
                        <span class="caret"></span>
					</a>
                    <ul class="dropdown-menu">
						@if (
							Auth::user()->admin->getPermission("setting_general") != 'no' ||
							Auth::user()->admin->getPermission("setting_sending") != 'no' ||
							Auth::user()->admin->getPermission("setting_system_urls") != 'no' ||
							Auth::user()->admin->getPermission("setting_background_job") != 'no'
						)
							<li rel0="SettingController">
								<a href="{{ action('Admin\SettingController@index') }}">
									<i class="icon-equalizer2"></i> {{ trans('messages.all_settings') }}
								</a>
							</li>
						@endif
						@if (Auth::user()->admin->getPermission("layout_read") != 'no')
							<li rel0="LayoutController">
								<a href="{{ action('Admin\LayoutController@index') }}">
									<i class="glyphicon glyphicon-file"></i> {{ trans('messages.page_form_layout') }}
								</a>
							</li>
						@endif
						@if (Auth::user()->admin->getPermission("language_read") != 'no')
							<li rel0="LanguageController">
								<a href="{{ action('Admin\LanguageController@index') }}">
									<i class="glyphicon glyphicon-flag"></i> {{ trans('messages.language') }}
								</a>
							</li>
						@endif
                    </ul>
				</li>

				@if (
					Auth::user()->admin->getPermission("report_blacklist") != 'no'
					|| Auth::user()->admin->getPermission("report_tracking_log") != 'no'
					|| Auth::user()->admin->getPermission("report_bounce_log") != 'no'
					|| Auth::user()->admin->getPermission("report_feedback_log") != 'no'
					|| Auth::user()->admin->getPermission("report_open_log") != 'no'
					|| Auth::user()->admin->getPermission("report_click_log") != 'no'
					|| Auth::user()->admin->getPermission("report_unsubscribe_log") != 'no'
				)
					<li class="dropdown language-switch"
						rel0="TrackingLogController"
						rel1="OpenLogController"
						rel2="ClickLogController"
						rel3="FeedbackLogController"
						rel4="BlacklistController"
						rel5="UnsubscribeLogController"
						rel6="BounceLogController"
					>
						<a class="dropdown-toggle" data-toggle="dropdown">
							<i class="icon-file-text2"></i> {{ trans('messages.report') }}
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu">
							@if (Auth::user()->admin->getPermission("report_blacklist") != 'no')
								<li rel0="BlacklistController">
									<a href="{{ action('Admin\BlacklistController@index') }}">
										<i class="glyphicon glyphicon-minus-sign"></i> {{ trans('messages.blacklist') }}
									</a>
								</li>
							@endif
							@if (Auth::user()->admin->getPermission("report_tracking_log") != 'no')
								<li rel0="TrackingLogController">
									<a href="{{ action('Admin\TrackingLogController@index') }}">
										<i class="icon-file-text2"></i> {{ trans('messages.tracking_log') }}
									</a>
								</li>
							@endif
							@if (Auth::user()->admin->getPermission("report_bounce_log") != 'no')
								<li rel0="BounceLogController">
									<a href="{{ action('Admin\BounceLogController@index') }}">
										<i class="icon-file-text2"></i> {{ trans('messages.bounce_log') }}
									</a>
								</li>
							@endif
							@if (Auth::user()->admin->getPermission("report_feedback_log") != 'no')
								<li rel0="FeedbackLogController">
									<a href="{{ action('Admin\FeedbackLogController@index') }}">
										<i class="icon-file-text2"></i> {{ trans('messages.feedback_log') }}
									</a>
								</li>
							@endif
							@if (Auth::user()->admin->getPermission("report_open_log") != 'no')
								<li rel0="OpenLogController">
									<a href="{{ action('Admin\OpenLogController@index') }}">
										<i class="icon-file-text2"></i> {{ trans('messages.open_log') }}
									</a>
								</li>
							@endif
							@if (Auth::user()->admin->getPermission("report_click_log") != 'no')
								<li rel0="ClickLogController">
									<a href="{{ action('Admin\ClickLogController@index') }}">
										<i class="icon-file-text2"></i> {{ trans('messages.click_log') }}
									</a>
								</li>
							@endif
							@if (Auth::user()->admin->getPermission("report_unsubscribe_log") != 'no')
								<li rel0="UnsubscribeLogController">
									<a href="{{ action('Admin\UnsubscribeLogController@index') }}">
										<i class="icon-file-text2"></i> {{ trans('messages.unsubscribe_log') }}
									</a>
								</li>
							@endif
						</ul>
					</li>
				@endif
			</ul>

			<ul class="nav navbar-nav navbar-right">
				<!--<li class="dropdown language-switch">
					<a class="dropdown-toggle" data-toggle="dropdown">
						{{ Acelle\Model\Language::getByCode(Config::get('app.locale'))->name }}
						<span class="caret"></span>
					</a>

					<ul class="dropdown-menu">
						@foreach(Acelle\Model\Language::getAll() as $language)
							<li class="{{ Acelle\Model\Language::getByCode(Config::get('app.locale'))->code == $language->code ? "active" : "" }}">
								<a>{{ $language->name }}</a>
							</li>
						@endforeach
					</ul>
                </li>-->

				<!--<li class="dropdown">
					<a href="#" class="dropdown-toggle top-quota-button" data-toggle="dropdown" data-url="{{ action("AccountController@quotaLog") }}">
						<i class="icon-stats-bars4"></i>
						<span class="visible-xs-inline-block position-right">{{ trans('messages.used_quota') }}</span>
					</a>
				</li>-->

				@include('layouts._top_activity_log')

				<li class="dropdown dropdown-user">
					<a class="dropdown-toggle" data-toggle="dropdown">
						<img src="{{ action('CustomerController@avatar', Auth::user()->customer->uid) }}" alt="">
						<span>{{ Auth::user()->customer->displayName() }}</span>
						<i class="caret"></i>
					</a>

					<ul class="dropdown-menu dropdown-menu-right">
						@can("admin_access", Auth::user())
							<li><a href="{{ action("Admin\HomeController@index") }}"><i class="icon-enter2"></i> {{ trans('messages.admin_view') }}</a></li>
							<li class="divider"></li>
						@endif
						<li class="dropdown">
							<a href="#" class="top-quota-button" data-url="{{ action("AccountController@quotaLog") }}">
								<i class="icon-stats-bars4"></i>
								<span class="">{{ trans('messages.used_quota') }}</span>
							</a>
						</li>
						@if (Auth::user()->customer->can("read", new Acelle\Model\Subscription()))
							<li rel0="AccountController\subscription">
								<a href="{{ action('AccountController@subscription') }}">
									<i class="icon-quill4"></i> {{ trans('messages.subscriptions') }}
								</a>
							</li>
						@endif
						<li><a href="{{ action("AccountController@profile") }}"><i class="icon-profile"></i> {{ trans('messages.account') }}</a></li>
						@if (Auth::user()->customer->canUseApi())
							<li rel0="AccountController/api">
								<a href="{{ action("AccountController@api") }}" class="level-1">
									<i class="icon-key position-left"></i> {{ trans('messages.api') }}
								</a>
							</li>
						@endif
						<li><a href="{{ url("/logout") }}"><i class="icon-switch2"></i> {{ trans('messages.logout') }}</a></li>
					</ul>
				</li>
			</ul>
