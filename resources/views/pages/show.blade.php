@extends('layouts.app')

@section('title', $page->title)

@if($page->meta_description)
@section('meta_description', $page->meta_description)
@endif

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <article class="page-content">
                <header class="page-header mb-4">
                    <h1 class="page-title">{{ $page->title }}</h1>
                </header>

                <div class="page-body">
                    {!! nl2br(e($page->content)) !!}
                </div>

                <footer class="page-footer mt-5 pt-3 border-top">
                    <small class="text-muted">
                        Last updated: {{ $page->updated_at->format('F j, Y') }}
                    </small>
                </footer>
            </article>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .page-content {
        background: white;
        padding: 2rem;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .page-title {
        color: #2c3e50;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .page-body {
        line-height: 1.7;
        color: #333;
        font-size: 1.1rem;
    }

    .page-body p {
        margin-bottom: 1.2rem;
    }

    .page-footer {
        border-top: 1px solid #e9ecef !important;
    }

    @media (max-width: 768px) {
        .page-content {
            padding: 1.5rem;
            margin: 0 1rem;
        }

        .page-title {
            font-size: 1.75rem;
        }

        .page-body {
            font-size: 1rem;
        }
    }
</style>
@endpush