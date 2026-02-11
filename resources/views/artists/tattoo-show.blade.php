@extends('layouts.dashboard_layout')

@section('title', ($tattoo['title'] ?? 'Tattoo') . ' - Detail')

@push('styles')
<style>
    /* Tattoo Image */
    .tattoo-image-container {
        width: 100%;
        height: 450px;
        overflow: hidden;
        border-radius: 0.5rem;
        background-color: #f0f2f5;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .tattoo-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }

    .tattoo-image-placeholder {
        color: #c1c7cd;
        font-size: 3rem;
    }

    /* Detail Labels */
    .detail-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #8392ab;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.2rem;
    }

    .detail-value {
        font-size: 1rem;
        color: #344767;
        margin-bottom: 1rem;
    }

    .tag-badge {
        display: inline-block;
        padding: 0.2rem 0.65rem;
        background-color: #f3f0ff;
        color: #7c3aed;
        border-radius: 9999px;
        font-size: 0.8rem;
        font-weight: 500;
        margin: 0.15rem;
    }

    .price-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #2dce89;
    }

    /* Artist Mini Card */
    .artist-mini-card {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem;
        background-color: #f8f9fa;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .artist-mini-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .artist-mini-name {
        font-weight: 600;
        color: #344767;
        font-size: 0.95rem;
    }

    .artist-mini-location {
        font-size: 0.8rem;
        color: #8392ab;
    }

    /* Action Buttons */
    .btn-book-now-lg {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.65rem 1.5rem;
        font-size: 0.95rem;
        font-weight: 600;
        color: #fff;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 0.375rem;
        text-decoration: none;
        transition: opacity 0.2s, transform 0.15s;
    }

    .btn-book-now-lg:hover {
        opacity: 0.9;
        transform: translateY(-1px);
        color: #fff;
        text-decoration: none;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem 1.5rem;
    }

    @media (max-width: 575.98px) {
        .info-grid {
            grid-template-columns: 1fr;
        }
        .tattoo-image-container {
            height: 300px;
        }
    }
</style>
@endpush

@section('content')
<h4 class="fw-bold py-3 mb-4">
    <span class="text-muted fw-light">
        <a href="{{ route('dashboard') }}" class="text-muted">Dashboard</a> /
        <a href="{{ route('dashboard.artists') }}" class="text-muted">Artists</a> /
        <a href="{{ route('dashboard.artists.show', ['username' => $artist['username']]) }}" class="text-muted">{{ $artist['display_name'] ?? 'Artist' }}</a> /
    </span>
    {{ $tattoo['title'] ?? 'Tattoo' }}
</h4>

<div class="row">
    <!-- Left: Tattoo Image -->
    <div class="col-12 col-lg-6 mb-4">
        <div class="card">
            <div class="card-body p-3">
                @if(!empty($tattoo['filename']))
                <div class="tattoo-image-container">
                    <img src="{{ $tattoo['filename'] }}" 
                         alt="{{ $tattoo['title'] ?? 'Tattoo' }}" 
                         class="tattoo-image">
                </div>
                @else
                <div class="tattoo-image-container">
                    <div class="tattoo-image-placeholder">
                        <i class="ti ti-photo-off"></i>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right: Tattoo Details -->
    <div class="col-12 col-lg-6 mb-4">
        <div class="card">
            <div class="card-body">
                <h4 class="fw-bold mb-3">{{ $tattoo['title'] ?? 'Untitled Tattoo' }}</h4>

                <!-- Artist Mini Card -->
                <a href="{{ route('dashboard.artists.show', ['username' => $artist['username']]) }}" class="text-decoration-none">
                    <div class="artist-mini-card">
                        <div class="artist-mini-avatar">
                            <i class="ti ti-user"></i>
                        </div>
                        <div>
                            <div class="artist-mini-name">{{ $artist['display_name'] ?? 'Artist' }}</div>
                            @if(!empty($artist['field_address_city']) || !empty($artist['field_address_country']))
                            <div class="artist-mini-location">
                                <i class="ti ti-map-pin" style="font-size: 0.75rem;"></i>
                                {{ !empty($artist['field_address_city']) ? $artist['field_address_city'] : '' }}{{ !empty($artist['field_address_city']) && !empty($artist['field_address_country']) ? ', ' : '' }}{{ !empty($artist['field_address_country']) ? $artist['field_address_country'] : '' }}
                            </div>
                            @endif
                        </div>
                    </div>
                </a>

                <!-- Description -->
                @if(!empty($tattoo['description']))
                <div class="mb-3">
                    <div class="detail-label">Description</div>
                    <div class="detail-value">{!! nl2br(e($tattoo['description'])) !!}</div>
                </div>
                @endif

                <!-- Info Grid -->
                <div class="info-grid">
                    @if(!empty($tattoo['primary_style']))
                    <div>
                        <div class="detail-label">Primary Style</div>
                        <div class="detail-value">{{ $tattoo['primary_style'] }}</div>
                    </div>
                    @endif

                    @if(!empty($tattoo['other_styles']))
                    <div>
                        <div class="detail-label">Other Styles</div>
                        <div class="detail-value">{{ $tattoo['other_styles'] }}</div>
                    </div>
                    @endif

                    @if(!empty($tattoo['color']))
                    <div>
                        <div class="detail-label">Color</div>
                        <div class="detail-value">{{ $tattoo['color'] }}</div>
                    </div>
                    @endif

                    @if(!empty($tattoo['suggested_placement']))
                    <div>
                        <div class="detail-label">Suggested Placement</div>
                        <div class="detail-value">{{ $tattoo['suggested_placement'] }}</div>
                    </div>
                    @endif

                    @if(!empty($tattoo['size_width']) || !empty($tattoo['size_height']))
                    <div>
                        <div class="detail-label">Dimensions</div>
                        <div class="detail-value">
                            @if(!empty($tattoo['size_width']) && !empty($tattoo['size_height']))
                                {{ $tattoo['size_width'] }}cm &times; {{ $tattoo['size_height'] }}cm
                            @elseif(!empty($tattoo['size_width']))
                                Width: {{ $tattoo['size_width'] }}cm
                            @elseif(!empty($tattoo['size_height']))
                                Height: {{ $tattoo['size_height'] }}cm
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(!empty($tattoo['session_time_h']))
                    <div>
                        <div class="detail-label">Session Duration</div>
                        <div class="detail-value">{{ $tattoo['session_time_h'] }} hour{{ $tattoo['session_time_h'] != 1 ? 's' : '' }}</div>
                    </div>
                    @endif

                    @if(!empty($tattoo['min_sessions']) || !empty($tattoo['max_sessions']))
                    <div>
                        <div class="detail-label">Sessions</div>
                        <div class="detail-value">
                            @if(!empty($tattoo['min_sessions']) && !empty($tattoo['max_sessions']))
                                {{ $tattoo['min_sessions'] }} - {{ $tattoo['max_sessions'] }}
                            @elseif(!empty($tattoo['min_sessions']))
                                Min {{ $tattoo['min_sessions'] }}
                            @else
                                Max {{ $tattoo['max_sessions'] }}
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(!empty($tattoo['price_model']))
                    <div>
                        <div class="detail-label">Price Model</div>
                        <div class="detail-value">{{ ucfirst($tattoo['price_model']) }}</div>
                    </div>
                    @endif
                </div>

                <!-- Tags -->
                @if(!empty($tattoo['tags']))
                <div class="mb-3">
                    <div class="detail-label">Tags</div>
                    <div class="mt-1">
                        @foreach(explode(',', $tattoo['tags']) as $tag)
                        <span class="tag-badge">{{ trim($tag) }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Price -->
                @if(!empty($tattoo['price']))
                <div class="mb-3">
                    <div class="detail-label">Price</div>
                    <div class="price-value">
                        {{ $tattoo['currency'] ?? '$' }}{{ number_format($tattoo['price'], 0) }}
                        @if(!empty($tattoo['max_price']) && $tattoo['max_price'] > $tattoo['price'])
                            - {{ $tattoo['currency'] ?? '$' }}{{ number_format($tattoo['max_price'], 0) }}
                        @endif
                    </div>
                    @if(!empty($tattoo['cost_per_session']))
                    <small class="text-muted">{{ $tattoo['currency'] ?? '$' }}{{ number_format($tattoo['cost_per_session'], 0) }} per session</small>
                    @endif
                </div>
                @endif

                <!-- Notes -->
                @if(!empty($tattoo['notes']))
                <div class="mb-3">
                    <div class="detail-label">Notes</div>
                    <div class="detail-value">{!! nl2br(e($tattoo['notes'])) !!}</div>
                </div>
                @endif

                <!-- Book Now Button -->
                <div class="mt-4">
                    <a href="{{ route('public.tattoo.book', ['artist_display_name' => \Str::slug($artist['display_name'] ?? $artist['username'] ?? ''), 'tattoo_title' => \Str::slug($tattoo['title'] ?? ''), 'tattoo_id' => $tattoo['id']]) }}" class="btn-book-now-lg" target="_blank">
                        <i class="ti ti-calendar-check"></i> Book This Tattoo
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
