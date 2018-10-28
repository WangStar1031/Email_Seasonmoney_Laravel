@extends('layouts.backend')

@section('title', trans('messages.settings'))

@section('page_script')
    <script type="text/javascript" src="{{ URL::asset('assets/js/core/libraries/jquery_ui/interactions.min.js') }}"></script>
	<script type="text/javascript" src="{{ URL::asset('assets/js/core/libraries/jquery_ui/touch.min.js') }}"></script>

	<script type="text/javascript" src="{{ URL::asset('js/listing.js') }}"></script>
@endsection

@section('page_header')

			<div class="page-title">
				<ul class="breadcrumb breadcrumb-caret position-right">
					<li><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
				</ul>
				<h1>
					<span class="text-gear"><i class="icon-list2"></i> {{ trans('messages.settings') }}</span>
				</h1>
			</div>

@endsection

@section('content')
			<form action="{{ action('Admin\SettingController@advanced') }}" method="POST" class="form-validate-jqueryz">
				{{ csrf_field() }}

				<div class="tabbable">

					<div class="tab-content">

						@include("admin.settings._advanced")
                        
                        <div class="text-left">
                            <button class="btn bg-teal"><i class="icon-check"></i> {{ trans('messages.save') }}</button>
                        </div>

					</div>
				</div>


			</form>

@endsection
