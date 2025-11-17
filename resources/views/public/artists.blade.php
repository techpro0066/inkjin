<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse all tattoo artists on InkJin">
    <title>All Artists | InkJin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        .header-section {
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid #e9ecef;
        }
        
        .logo-img {
            height: 40px;
            width: auto;
        }
        
        .page-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 2rem;
        }
        
        .artists-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .artist-card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .artist-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
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
            height: 280px;
            overflow: hidden;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .artist-card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .artist-card-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ffffff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .artist-card-avatar-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #adb5bd;
        }
        
        .artist-card-body {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .artist-card-name {
            font-size: 1.25rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.5rem;
            line-height: 1.3;
            min-height: 2.6rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .artist-card-location {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            min-height: 1.5rem;
        }
        
        .location-icon {
            width: 16px;
            height: 16px;
        }
        
        .artist-card-studio {
            color: #6c757d;
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
            min-height: 1.2rem;
        }
        
        .artist-card-style {
            color: #495057;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            min-height: 1.2rem;
        }
        
        .artist-card-description {
            color: #6c757d;
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 1rem;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .artist-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
            margin-top: auto;
        }
        
        .artist-card-stats {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: #6c757d;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .social-links-mini {
            display: flex;
            gap: 0.5rem;
        }
        
        .social-link-mini {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: transform 0.2s;
            font-size: 0.875rem;
        }
        
        .social-link-mini:hover {
            transform: scale(1.1);
            text-decoration: none;
        }
        
        .social-link-mini.instagram {
            background-color: #e4405f;
            color: #ffffff;
        }
        
        .social-link-mini.tiktok {
            background-color: #000000;
            color: #ffffff;
        }
        
        .social-link-mini.website {
            background-color: #6c757d;
            color: #ffffff;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header-section">
        <div class="container py-3">
            <div class="d-flex align-items-center justify-content-between">
                <a href="/" class="navbar-brand">
                    <img src="{{ asset('images/logo.png') }}" alt="InkJin" class="logo-img" onerror="this.style.display='none'">
                    <span class="fw-bold">InkJin</span>
                </a>
                <nav class="d-flex gap-3">
                    <a href="{{ route('public.artists.list') }}" class="text-decoration-none text-dark fw-medium">Artists</a>
                </nav>
            </div>
        </div>
    </header>
    
    <div class="container py-5">
        <h1 class="page-title">All Artists</h1>
        
        @if(count($artists) > 0)
        <div class="artists-grid">
            @foreach($artists as $artist)
            <div class="artist-card">
                <a href="{{ route('public.artist.db', ['username' => $artist['username']]) }}" class="artist-card-link">
                    <div class="artist-card-image-wrapper">
                        @if(!empty($artist['field_profile_picture']))
                        <img src="{{ $artist['field_profile_picture'] }}" 
                             alt="{{ $artist['display_name'] ?? $artist['username'] ?? 'Artist' }}" 
                             class="artist-card-avatar">
                        @else
                        <div class="artist-card-avatar-placeholder">
                            <span>👤</span>
                        </div>
                        @endif
                    </div>
                    
                    <div class="artist-card-body">
                        <h3 class="artist-card-name">
                            {{ $artist['display_name'] ?? $artist['username'] ?? 'Artist' }}
                        </h3>
                        
                        @if(!empty($artist['field_address_city']) || !empty($artist['field_address_country']))
                        <div class="artist-card-location">
                            <svg class="location-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>
                                @if(!empty($artist['field_address_city']))
                                    {{ $artist['field_address_city'] }}
                                @endif
                                @if(!empty($artist['field_address_city']) && !empty($artist['field_address_country']))
                                    , 
                                @endif
                                @if(!empty($artist['field_address_country']))
                                    {{ $artist['field_address_country'] }}
                                @endif
                            </span>
                        </div>
                        @endif
                        
                        @if(!empty($artist['field_profile_studio']))
                        <div class="artist-card-studio">
                            📍 {{ $artist['field_profile_studio'] }}
                        </div>
                        @endif
                        
                        @if(!empty($artist['field_profile_primary_style']))
                        <div class="artist-card-style">
                            🎨 {{ $artist['field_profile_primary_style'] }}
                        </div>
                        @endif
                        
                        @if(!empty($artist['field_profile_description']))
                        <div class="artist-card-description">
                            {{ $artist['field_profile_description'] }}
                        </div>
                        @endif
                        
                        <div class="artist-card-footer">
                            <div class="artist-card-stats">
                                @if(isset($artist['tattoo_count']) && $artist['tattoo_count'] > 0)
                                <div class="stat-item">
                                    <span>🖼️</span>
                                    <span>{{ $artist['tattoo_count'] }}</span>
                                </div>
                                @endif
                                @if(isset($artist['followed_count']) && $artist['followed_count'] > 0)
                                <div class="stat-item">
                                    <span>👥</span>
                                    <span>{{ $artist['followed_count'] }}</span>
                                </div>
                                @endif
                            </div>
                            
                            <div class="social-links-mini">
                                @if(!empty($artist['field_profile_instagram']))
                                <a href="{{ $artist['field_profile_instagram'] }}" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="social-link-mini instagram"
                                   onclick="event.stopPropagation();">
                                    📷
                                </a>
                                @endif
                                @if(!empty($artist['field_profile_tiktok']))
                                <a href="{{ $artist['field_profile_tiktok'] }}" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="social-link-mini tiktok"
                                   onclick="event.stopPropagation();">
                                    🎵
                                </a>
                                @endif
                                @if(!empty($artist['field_profile_website']))
                                <a href="{{ $artist['field_profile_website'] }}" 
                                   target="_blank" 
                                   rel="noopener noreferrer"
                                   class="social-link-mini website"
                                   onclick="event.stopPropagation();">
                                    🌐
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>
        @else
        <div class="empty-state">
            <div class="empty-state-icon">🎨</div>
            <h3>No artists found</h3>
            <p>There are no artists available at the moment.</p>
        </div>
        @endif
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

