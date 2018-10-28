@extends('layouts.backend')

@section('title', $server->name)

@section('page_script')
    <script type="text/javascript" src="{{ URL::asset('assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('assets/js/core/libraries/jquery_ui/interactions.min.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('assets/js/core/libraries/jquery_ui/touch.min.js') }}"></script>

    <script type="text/javascript" src="{{ URL::asset('js/validate.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/listing.js') }}"></script>
@endsection

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li><a href="{{ action("Admin\SendingDomainController@index") }}">{{ trans('messages.sending_domains') }}</a></li>
        </ul>
        <h1>
            <span class="text-semibold"><i class="icon-pencil"></i> {{ $server->name }}</span>
        </h1>
    </div>

@endsection

@section('content')

    <div class="row">
        <div class="col-sm-12 col-md-10 col-lg-10">
            <p>{!! trans('messages.sending_domain.wording') !!}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12 col-md-12">
            <h3>{{ trans('messages.sending_domain.title') }}</h3>
        </div>
    </div>
    
    <div class="row">
        <div class="col-sm-12 col-md-8">
            <ul class="dotted-list topborder section section-flex">
                <li>
                    <div class="unit size1of3">
                        <strong>{{ trans('messages.domain_name') }}</strong>
                    </div>
                    <div class="size2of3">
                        <mc:flag class="text-bold">{{ $server->name }}</mc:flag>
                    </div>
                </li>
                <li>
                    <div class="unit size1of3">
                        <strong>{{ trans("messages.signing_enabled") }}</strong>
                    </div>
                    <div class="size2of3">
                        <mc:flag class="text-bold"><i class="table-checkmark-{{ $server->signing_enabled }}"></i></mc:flag>
                    </div>
                </li>
                <li>
                    <div class="unit size1of3">
                        <strong>{{ trans('messages.dkim_private') }}</strong>
                    </div>
                    <div class="size2of3">
                        <mc:flag>
                            @include('helpers.form_control', [
                                'label' => '',
                                'type' => 'textarea',
                                'class' => 'dkim_box code',
                                'readonly' => 'readonly',
                                'name' => 'dkim_private',
                                'value' => $server->dkim_private,
                                'help_class' => 'sending_domain',
                                'rules' => Acelle\Model\SendingDomain::rules()
                            ])
                        </mc:flag>
                    </div>
                </li>
                <li>
                    <div class="unit size1of3">
                        <strong>{{ trans('messages.dkim_public') }}</strong>
                    </div>
                    <div class="size2of3">
                        <mc:flag>
                            @include('helpers.form_control', [
                                'label' => '',
                                'type' => 'textarea',
                                'class' => 'dkim_box code',
                                'readonly' => 'readonly',
                                'name' => 'dkim_public',
                                'value' => $server->dkim_public,
                                'help_class' => 'sending_domain',
                                'rules' => Acelle\Model\SendingDomain::rules()
                            ])
                        </mc:flag>
                    </div>
                </li>
            </ul>
        </div>
    </div>
        
    <div class="row">
        <div class="col-sm-12 col-md-12 mt-20">
            <h3>{{ trans('messages.sending_domain.dkim_title') }}</h3>
            <p>{!! trans('messages.sending_domain.dkim_wording') !!}</p>
            <p>{!! trans('messages.sending_domain.spf_wording') !!}</p>
            <div class="scrollbar-boxx dim-box">
                <div class="listing-form"
					data-url="{{ action('Admin\SendingDomainController@records', $server->uid) }}"
					per-page="1">
                    <div class="pml-table-container">                        
                    </div>
                </div>                
            </div>
        </div>
    </div>
    <hr >
    <div class="text-left">
        <a callback="" data-method="POST" href="{{ action('Admin\SendingDomainController@verify', $server->uid) }}" class="btn btn-primary bg-teal ajax_link">{{ trans('messages.sending_domain.verify') }}</a>
    </div>

@endsection
