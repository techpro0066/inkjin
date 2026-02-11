@extends('layouts.dashboard_layout')

@section('title', 'Artists')

@push('styles')
<style>
    .artists-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 1.5rem;
    }

    .artist-card {
        background-color: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .artist-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
    }

    .artist-card-link {
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        height: 100%;
    }

    .artist-card-link:hover {
        text-decoration: none;
        color: inherit;
    }

    .artist-card-image-wrapper {
        width: 100%;
        height: 200px;
        overflow: hidden;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .artist-card-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, 0.9);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .artist-card-avatar-placeholder {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: rgba(255, 255, 255, 0.8);
        border: 4px solid rgba(255, 255, 255, 0.3);
    }

    .artist-card-body {
        padding: 1.25rem;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }

    .artist-card-name {
        font-size: 1.1rem;
        font-weight: 600;
        color: #344767;
        margin-bottom: 0.4rem;
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .artist-card-location {
        color: #8392ab;
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .artist-card-studio {
        color: #8392ab;
        font-size: 0.82rem;
        margin-bottom: 0.4rem;
    }

    .artist-card-style {
        display: inline-block;
        font-size: 0.75rem;
        font-weight: 500;
        color: #7c3aed;
        background-color: #f3f0ff;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        margin-bottom: 0.75rem;
        align-self: flex-start;
    }

    .artist-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 0.75rem;
        border-top: 1px solid #f0f2f5;
        margin-top: auto;
    }

    .artist-card-stats {
        display: flex;
        gap: 0.75rem;
        font-size: 0.82rem;
        color: #8392ab;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .social-links-mini {
        display: flex;
        gap: 0.35rem;
    }

    .social-link-mini {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: transform 0.2s, opacity 0.2s;
        opacity: 0.7;
    }

    .social-link-mini:hover {
        transform: scale(1.15);
        opacity: 1;
        text-decoration: none;
    }

    .social-link-mini.instagram {
        color: #e4405f;
    }

    .social-link-mini.tiktok {
        color: #000000;
    }

    .social-link-mini.website {
        color: #6c757d;
    }

    .search-box {
        max-width: 400px;
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
    <span class="text-muted fw-light">Dashboard /</span> Artists
</h4>

<!-- Search -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('dashboard.artists') }}" class="d-flex align-items-center gap-3">
                    <div class="input-group search-box">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search artists by name, style, city..." value="{{ $search ?? '' }}">
                    </div>
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if(!empty($search))
                        <a href="{{ route('dashboard.artists') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Artists Grid -->
@if($artists->count() > 0)
<div class="artists-grid mb-4">
    @foreach($artists as $artist)
    <div class="artist-card">
        <a href="{{ route('dashboard.artists.show', ['username' => $artist['username']]) }}" class="artist-card-link">
            <div class="artist-card-image-wrapper">
                @if(!empty($artist['field_profile_picture']))
                <img src="{{ $artist['field_profile_picture'] }}" 
                     alt="{{ $artist['display_name'] ?? $artist['username'] ?? 'Artist' }}" 
                     class="artist-card-avatar">
                @else
                <div class="artist-card-avatar-placeholder">
                    <i class="ti ti-user"></i>
                </div>
                @endif
            </div>
            
            <div class="artist-card-body">
                <h5 class="artist-card-name">
                    {{ $artist['display_name'] ?? $artist['username'] ?? 'Artist' }}
                </h5>
                
                @if(!empty($artist['field_address_city']) || !empty($artist['field_address_country']))
                <div class="artist-card-location">
                    <i class="ti ti-map-pin" style="font-size: 0.9rem;"></i>
                    <span>
                        {{ !empty($artist['field_address_city']) ? $artist['field_address_city'] : '' }}{{ !empty($artist['field_address_city']) && !empty($artist['field_address_country']) ? ', ' : '' }}{{ !empty($artist['field_address_country']) ? $artist['field_address_country'] : '' }}
                    </span>
                </div>
                @endif
                
                @if(!empty($artist['field_profile_studio']))
                <div class="artist-card-studio">
                    <i class="ti ti-building"></i> {{ $artist['field_profile_studio'] }}
                </div>
                @endif
                
                @if(!empty($artist['field_profile_primary_style']))
                <span class="artist-card-style">{{ $artist['field_profile_primary_style'] }}</span>
                @endif
                
                <div class="artist-card-footer">
                    <div class="artist-card-stats">
                        @if(isset($artist['tattoo_count']) && $artist['tattoo_count'] > 0)
                        <div class="stat-item">
                            <i class="ti ti-photo"></i>
                            <span>{{ $artist['tattoo_count'] }} tattoo{{ $artist['tattoo_count'] != 1 ? 's' : '' }}</span>
                        </div>
                        @endif
                    </div>
                    
                    <div class="social-links-mini">
                        @if(!empty($artist['field_profile_instagram']))
                        <a href="{{ $artist['field_profile_instagram'] }}" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="social-link-mini instagram"
                           onclick="event.stopPropagation(); event.preventDefault(); window.open(this.href, '_blank');">
                            <i class="ti ti-brand-instagram"></i>
                        </a>
                        @endif
                        @if(!empty($artist['field_profile_tiktok']))
                        <a href="{{ $artist['field_profile_tiktok'] }}" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="social-link-mini tiktok"
                           onclick="event.stopPropagation(); event.preventDefault(); window.open(this.href, '_blank');">
                            <i class="ti ti-brand-tiktok"></i>
                        </a>
                        @endif
                        @if(!empty($artist['field_profile_website']))
                        <a href="{{ $artist['field_profile_website'] }}" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="social-link-mini website"
                           onclick="event.stopPropagation(); event.preventDefault(); window.open(this.href, '_blank');">
                            <i class="ti ti-world"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>

<!-- Pagination -->
<div class="d-flex justify-content-center">
    {{ $artists->appends(['search' => $search])->links() }}
</div>
@else
<div class="card">
    <div class="card-body empty-state">
        <i class="ti ti-palette"></i>
        <h5 class="text-muted">No artists found</h5>
        @if(!empty($search))
            <p class="text-muted mb-3">No artists match your search "{{ $search }}".</p>
            <a href="{{ route('dashboard.artists') }}" class="btn btn-outline-primary">View All Artists</a>
        @else
            <p class="text-muted">There are no artists available at the moment.</p>
        @endif
    </div>
</div>
@endif
@endsection
