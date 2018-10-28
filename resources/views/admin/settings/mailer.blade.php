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
	
    @if (count($errors) > 0 && $errors->has('smtp_valid'))
        <!-- Form Error List -->
        <div class="alert alert-danger alert-noborder">
            <ul>
                @foreach ($errors->all() as $key => $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <form action="{{ action('Admin\SettingController@mailer') }}" method="POST" class="form-validate-jqueryz">
        {{ csrf_field() }}
        
        <div class="tabbable">
            @include("admin.settings._tabs")

            <div class="tab-content">
                @include("admin.settings._mailer")
            </div>
        </div>
    </form>
        
    <script>
        function toogleMailer() {
            var value = $("select[name='env[MAIL_DRIVER]']").val();
            if (value == 'sendmail') {
                $('.smtp_box').hide();
            } else {
                $('.smtp_box').show();
            }
        }
        
        $(document).ready(function() {
            // SMTP toogle
            toogleMailer();
            $("select[name='env[MAIL_DRIVER]']").change(function() {
                toogleMailer();
            });
        });
    </script>
@endsection
