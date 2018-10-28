@extends('layouts.frontend')

@section('title', $list->name . ": " . trans('messages.update_page', ['name' => trans('messages.' . $layout->alias)]))

@section('page_script')
	<script type="text/javascript" src="{{ URL::asset('tinymce/tinymce.min.js') }}"></script>

    <script type="text/javascript" src="{{ URL::asset('js/editor.js') }}"></script>
@endsection

@section('page_header')

			@include("lists._header")

@endsection

@section('content')

				@include("lists._menu")

				<!--<h2 class="text-bold text-teal-800 mb-10"><i class="icon-certificate position-left"></i> {{ trans('messages.update_page') }}</h2>-->

                <h2>{{ trans('messages.' . $layout->alias) }}</h2>

                @if ($layout->alias == 'sign_up_form')
                    <p class="alert alert-info mt-20 mb-20">{{ trans('messages.sign_up_form_url') }}<br /> <a target="_blank" href="{{ action('PageController@signUpForm', ['list_uid' => $list->uid]) }}" class="text-semibold">{{ action('PageController@signUpForm', ['list_uid' => $list->uid]) }}</a></p>
                @endif

                <form id="update-page" action="{{ action('PageController@update', ['list' => $list->uid, 'alias' => $layout->alias]) }}" method="POST" class="form-validate-jqueryz">
					{{ csrf_field() }}
					
					@if ($page->canHasOutsideUrl())
						<div class="form-group control-radio">
							<div class="radio_box" data-popup='tooltip' title="">
								<label class="main-control">
									<input
										{{ ($page->use_outside_url ? 'checked' : '') }}
										checked type="radio"
										name="use_outside_url"
										value="1" class="styled" /><rtitle>{{ trans('messages.form_page.use_outside_url') }}</rtitle>
									<div class="desc text-normal mb-10">
										{{ trans('messages.form_page.use_outside_url.intro') }}
									</div>
								</label>
								<div class="radio_more_box">
									
									@include('helpers.form_control', [
										'type' => 'text',
										'name' => 'outside_url',
										'value' => $page->outside_url,
										'rules' => ['outside_url' => 'required'],
										'placeholder' => trans('messages.form_page.enter_outside_url'),
									])
									<div class="">
										<button type="submit" class="btn bg-teal mr-10"><i class="icon-check"></i> {{ trans('messages.save_change') }}</button>
									</div>
						
								</div>
							</div>
							<hr>
							<div class="radio_box" data-popup='tooltip' title="">
								<label class="main-control">
									<input type="radio"
										{{ (!$page->use_outside_url ? 'checked' : '') }}
										name="use_outside_url"
										value="0" class="styled" /><rtitle>{{ trans('messages.form_page.use_built_in_page') }}</rtitle>
									<div class="desc text-normal mb-10">
										{{ trans('messages.form_page.use_built_in_page.intro') }}
									</div>
								</label>
								<div class="radio_more_box">
									@include('pages._form')
									
									<hr />
									<div class="">
										<a page-url="{{ action('PageController@preview', ['list_uid' => $list->uid, 'alias' => $layout->alias]) }}" class="btn btn-info bg-grey-800 mr-10 preview-page-button" data-toggle="modal" data-target="#preview_page"><i class="icon-eye"></i> {{ trans('messages.preview') }}</a>
										<button type="submit" class="btn bg-teal mr-10"><i class="icon-check"></i> {{ trans('messages.save_change') }}</button>
									</div>
								</div>
							</div>
						</div>
					@else
						@include('pages._form')
						
						<hr />
						<div class="">
							<a page-url="{{ action('PageController@preview', ['list_uid' => $list->uid, 'alias' => $layout->alias]) }}" class="btn btn-info bg-grey-800 mr-10 preview-page-button" data-toggle="modal" data-target="#preview_page"><i class="icon-eye"></i> {{ trans('messages.preview') }}</a>
							<button type="submit" class="btn bg-teal mr-10"><i class="icon-check"></i> {{ trans('messages.save_change') }}</button>
						</div>
					@endif
										
					
                </form>


				<!-- Full width modal -->
				<div id="preview_page" class="modal fade">
					<div class="modal-dialog modal-full">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal">&times;</button>
								<h5 class="modal-title"></h5>
							</div>

							<div class="modal-body">
								<iframe name="preview_page_frame" class="preview_page_frame" src="/"></iframe>
							</div>

							<div class="modal-footer">
								<button type="button" class="btn btn-info bg-grey-800" data-dismiss="modal">{{ trans('messages.close') }}</button>
							</div>
						</div>
					</div>
				</div>
				<!-- /full width modal -->

@endsection
