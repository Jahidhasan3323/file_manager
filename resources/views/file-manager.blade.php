@extends('master')

@section('fav_title', __('backend/default.about_us'))

@section('styles')
{{--    @include('frontend.partials.owl-carousel.style')--}}
@endsection

@section('content')
    <!-- grid column -->
    <div class="col-lg-12">
        <!-- .card -->
        <div class="card card-fluid">
            <!-- .card-body -->
            <div class="card-body">
                <div id="jstreeFilemanager"></div><!-- /#jstree3 -->
                <div id="app">
                    <example-component></example-component>
                </div>
            </div><!-- /.card-body -->
        </div><!-- /.card -->
    </div><!-- grid column -->
@endsection

