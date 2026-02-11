@extends('layouts.dashboard_layout')

@section('title', ($artist['display_name'] ?? 'Artist') . ' - Tattoos')

@push('styles')
<style>
    /* Artist Profile Header */
    .artist-profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 0.5rem;
        padding: 2rem;
        color: #fff;
        margin-bottom: 1.5rem;
        position: relative;
        overflow: hidden;
    }

    .artist-profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
    }

    .artist-avatar-lg {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, 0.9);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .artist-avatar-lg-placeholder {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: rgba(255, 255, 255, 0.8);
        border: 4px solid rgba(255, 255, 255, 0.3);
    }

    .artist-profile-name {
        font-size: 1.75rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .artist-profile-location {
        opacity: 0.85;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
    }

    .artist-profile-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-top: 0.75rem;
    }

    .artist-meta-item {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.875rem;
        opacity: 0.9;
    }

    .artist-social-links {
        display: flex;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    .artist-social-btn {
        width: 34px;
        height: 34px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: rgba(255, 255, 255, 0.2);
        color: #fff;
        text-decoration: none;
        transition: background-color 0.2s, transform 0.2s;
        font-size: 1rem;
    }

    .artist-social-btn:hover {
        background-color: rgba(255, 255, 255, 0.35);
        color: #fff;
        transform: scale(1.1);
        text-decoration: none;
    }

    .artist-style-badge {
        display: inline-block;
        font-size: 0.8rem;
        font-weight: 500;
        color: #fff;
        background-color: rgba(255, 255, 255, 0.2);
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        margin-top: 0.5rem;
    }

    /* Tattoo Grid */
    .tattoos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 1.5rem;
    }

    .tattoo-card {
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .tattoo-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    }

    .tattoo-card-link {
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .tattoo-card-link:hover {
        text-decoration: none;
        color: inherit;
    }

    .tattoo-image-wrapper {
        width: 100%;
        height: 240px;
        overflow: hidden;
        background-color: #f0f2f5;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .tattoo-card-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .tattoo-card:hover .tattoo-card-image {
        transform: scale(1.05);
    }

    .tattoo-image-placeholder {
        color: #c1c7cd;
        font-size: 2.5rem;
    }

    .tattoo-card-body {
        padding: 1rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .tattoo-card-title {
        font-size: 1rem;
        font-weight: 600;
        color: #344767;
        margin-bottom: 0.4rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .tattoo-card-tags {
        font-size: 0.8rem;
        color: #8392ab;
        margin-bottom: 0.5rem;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .tattoo-card-details {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding-top: 0.75rem;
        border-top: 1px solid #f0f2f5;
    }

    .tattoo-card-actions {
        padding: 0 1rem 1rem;
        margin-top: auto;
    }

    .tattoo-card-btns {
        display: flex;
        gap: 0.5rem;
    }

    .btn-view-detail {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
        flex: 1;
        padding: 0.45rem 0.75rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: #667eea;
        background-color: #f0edff;
        border: 1px solid #ddd6fe;
        border-radius: 0.375rem;
        text-decoration: none;
        transition: background-color 0.2s, transform 0.15s;
    }

    .btn-view-detail:hover {
        background-color: #e4dffc;
        transform: translateY(-1px);
        color: #667eea;
        text-decoration: none;
    }

    .btn-book-now {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
        flex: 1;
        padding: 0.45rem 0.75rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: #fff;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 0.375rem;
        text-decoration: none;
        transition: opacity 0.2s, transform 0.15s;
    }

    .btn-book-now:hover {
        opacity: 0.9;
        transform: translateY(-1px);
        color: #fff;
        text-decoration: none;
    }

    .tattoo-detail-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        font-size: 0.75rem;
        color: #6c757d;
        background-color: #f8f9fa;
        padding: 0.15rem 0.5rem;
        border-radius: 9999px;
    }

    .tattoo-price {
        font-size: 0.85rem;
        font-weight: 600;
        color: #2dce89;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-state i {
        font-size: 4rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">
        <a href="{{ route('dashboard') }}" class="text-muted">Dashboard</a> /
        <a href="{{ route('dashboard.artists') }}" class="text-muted">Artists</a> /
    </span>
    {{ $artist['display_name'] ?? 'Artist' }}
</h4>

<!-- Artist Profile Header -->
<div class="artist-profile-header">
    <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-3 position-relative" style="z-index: 1;">
        <!-- Avatar -->
        @if(!empty($artist['field_profile_picture']))
        <img src="{{ $artist['field_profile_picture'] }}" 
             alt="{{ $artist['display_name'] ?? 'Artist' }}" 
             class="artist-avatar-lg">
        @else
        <div class="artist-avatar-lg-placeholder">
            <i class="ti ti-user"></i>
        </div>
        @endif

        <!-- Info -->
        <div class="text-center text-md-start">
            <h2 class="artist-profile-name">{{ $artist['display_name'] ?? 'Artist' }}</h2>

            @if(!empty($artist['field_address_city']) || !empty($artist['field_address_country']))
            <div class="artist-profile-location">
                <i class="ti ti-map-pin"></i>
                <span>
                    {{ !empty($artist['field_address_city']) ? $artist['field_address_city'] : '' }}{{ !empty($artist['field_address_city']) && !empty($artist['field_address_country']) ? ', ' : '' }}{{ !empty($artist['field_address_country']) ? $artist['field_address_country'] : '' }}
                </span>
            </div>
            @endif

            @if(!empty($artist['field_profile_primary_style']))
            <span class="artist-style-badge">{{ $artist['field_profile_primary_style'] }}</span>
            @endif

            <div class="artist-profile-meta">
                @if(!empty($artist['field_profile_studio']))
                <div class="artist-meta-item">
                    <i class="ti ti-building"></i>
                    <span>{{ $artist['field_profile_studio'] }}</span>
                </div>
                @endif

                @if(!empty($artist['field_profile_tattooing_since']))
                <div class="artist-meta-item">
                    <i class="ti ti-calendar"></i>
                    <span>Since {{ $artist['field_profile_tattooing_since'] }}</span>
                </div>
                @endif

                @if(isset($artist['tattoo_count']))
                <div class="artist-meta-item">
                    <i class="ti ti-photo"></i>
                    <span>{{ $artist['tattoo_count'] }} tattoo{{ $artist['tattoo_count'] != 1 ? 's' : '' }}</span>
                </div>
                @endif
            </div>

            <!-- Social Links -->
            <div class="artist-social-links">
                @if(!empty($artist['field_profile_instagram']))
                <a href="{{ $artist['field_profile_instagram'] }}" target="_blank" rel="noopener noreferrer" class="artist-social-btn" title="Instagram">
                    <i class="ti ti-brand-instagram"></i>
                </a>
                @endif
                @if(!empty($artist['field_profile_tiktok']))
                <a href="{{ $artist['field_profile_tiktok'] }}" target="_blank" rel="noopener noreferrer" class="artist-social-btn" title="TikTok">
                    <i class="ti ti-brand-tiktok"></i>
                </a>
                @endif
                @if(!empty($artist['field_profile_website']))
                <a href="{{ $artist['field_profile_website'] }}" target="_blank" rel="noopener noreferrer" class="artist-social-btn" title="Website">
                    <i class="ti ti-world"></i>
                </a>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Tattoos Section -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 class="fw-bold mb-0">Tattoos</h5>
    @if(isset($artist['tattoo_count']) && $artist['tattoo_count'] > 0)
    <span class="badge bg-label-primary rounded-pill">{{ $artist['tattoo_count'] }} total</span>
    @endif
</div>

@if($tattoos->count() > 0)
<div class="tattoos-grid mb-4">
    @foreach($tattoos as $tattoo)
    <div class="tattoo-card">
        <a href="{{ route('dashboard.tattoo.show', ['id' => $tattoo['id']]) }}" class="tattoo-card-link">
            <div class="tattoo-image-wrapper">
                @if(!empty($tattoo['filename']))
                <img src="{{ $tattoo['filename'] }}" 
                     alt="{{ $tattoo['title'] ?? 'Tattoo' }}" 
                     class="tattoo-card-image">
                @else
                <div class="tattoo-image-placeholder">
                    <i class="ti ti-photo-off"></i>
                </div>
                @endif
            </div>

            <div class="tattoo-card-body">
                <h6 class="tattoo-card-title">{{ $tattoo['title'] ?? 'Untitled Tattoo' }}</h6>

                @if(!empty($tattoo['tags']))
                <div class="tattoo-card-tags">{{ $tattoo['tags'] }}</div>
                @endif

                <div class="tattoo-card-details">
                    @if(!empty($tattoo['primary_style']))
                    <span class="tattoo-detail-badge">
                        <i class="ti ti-palette" style="font-size: 0.7rem;"></i> {{ $tattoo['primary_style'] }}
                    </span>
                    @endif

                    @if(!empty($tattoo['color']))
                    <span class="tattoo-detail-badge">
                        <i class="ti ti-droplet" style="font-size: 0.7rem;"></i> {{ $tattoo['color'] }}
                    </span>
                    @endif

                    @if(!empty($tattoo['suggested_placement']))
                    <span class="tattoo-detail-badge">
                        <i class="ti ti-map-pin" style="font-size: 0.7rem;"></i> {{ $tattoo['suggested_placement'] }}
                    </span>
                    @endif

                    @if(!empty($tattoo['price']))
                    <span class="tattoo-price ms-auto">
                        {{ $tattoo['currency'] ?? '$' }}{{ number_format($tattoo['price'], 0) }}
                        @if(!empty($tattoo['max_price']) && $tattoo['max_price'] > $tattoo['price'])
                            - {{ $tattoo['currency'] ?? '$' }}{{ number_format($tattoo['max_price'], 0) }}
                        @endif
                    </span>
                    @endif
                </div>
            </div>
        </a>
        <div class="tattoo-card-actions">
            <div class="tattoo-card-btns">
                <a href="{{ route('dashboard.tattoo.show', ['id' => $tattoo['id']]) }}" class="btn-view-detail">
                    <i class="ti ti-eye"></i> View Detail
                </a>
                <a href="{{ route('public.tattoo.book', ['artist_display_name' => \Str::slug($artist['display_name'] ?? $artist['username'] ?? ''), 'tattoo_title' => \Str::slug($tattoo['title'] ?? ''), 'tattoo_id' => $tattoo['id']]) }}" class="btn-book-now" target="_blank">
                    <i class="ti ti-calendar-check"></i> Book Now
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>

<!-- Pagination -->
<div class="d-flex justify-content-center">
    {{ $tattoos->links() }}
</div>
@else
<div class="card">
    <div class="card-body empty-state">
        <i class="ti ti-photo-off"></i>
        <h5 class="text-muted">No tattoos yet</h5>
        <p class="text-muted">This artist hasn't added any tattoos yet.</p>
    </div>
</div>
@endif
@endsection
