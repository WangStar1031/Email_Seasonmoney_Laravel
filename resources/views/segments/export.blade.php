@extends('layouts.frontend')

@section('title', $list->name . ": " . trans('messages.export'))

@section('page_script')
    <script type="text/javascript" src="{{ URL::asset('assets/js/core/libraries/jquery_ui/interactions.min.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('assets/js/core/libraries/jquery_ui/touch.min.js') }}"></script>

    <script type="text/javascript" src="{{ URL::asset('js/listing.js') }}"></script>
@endsection

@section('page_header')

    @include("lists._header")

@endsection

@section('content')

    @include("lists._menu")

    <h2 class="text-bold text-teal-800"><i class="icon-make-group position-left"></i> {{$segment->name}} - {{ trans('messages.export') }} </h2>
    <p>{!! trans('messages.click_to_start_export', ['total' => $segment->readCache('SubscriberCount', 0)]) !!}</p>
    <div class="text-left">
        <button data-toggle="modal" data-target="#export-segments-modal" class="btn bg-teal mr-10"><i class="icon-check"></i> {{ trans('messages.export') }}</button>
    </div>
    @include("helpers._export",['url' => action('SegmentController@exportList', ["list_uid" => $list->uid, "uid" => $segment->uid]) ])
    @include("lists._modals_export",['list' => $list, 'uid' => $segment->uid])

@endsection
