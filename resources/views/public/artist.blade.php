<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $artist['display_name'] ?? $artist['username'] ?? 'Artist' }} - Tattoo Artist Profile">
    <title>{{ $artist['display_name'] ?? $artist['username'] ?? 'Artist' }} | InkJin</title>
    
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
        
        .artist-card {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .artist-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e9ecef;
        }
        
        .artist-avatar-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #adb5bd;
        }
        
        .artist-name {
            font-size: 2rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 0.5rem;
        }
        
        .location-text {
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .location-icon {
            width: 20px;
            height: 20px;
        }
        
        .social-links {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .social-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            text-decoration: none;
            transition: transform 0.2s;
        }
        
        .social-link:hover {
            transform: scale(1.1);
            text-decoration: none;
        }
        
        .social-link.instagram {
            color: #e4405f;
        }
        
        .social-link.instagram:hover {
            color: #c13584;
        }
        
        .social-link.tiktok {
            color: #000000;
        }
        
        .social-link.tiktok:hover {
            color: #333333;
        }
        
        .social-link.website {
            color: #0d6efd;
        }
        
        .social-link.website:hover {
            color: #0a58ca;
        }
        
        .social-icon {
            width: 20px;
            height: 20px;
        }
        
        .artist-info-section {
            margin-bottom: 1.5rem;
        }
        
        .info-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 1rem;
            color: #212529;
        }
        
        .tattoos-section {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 24px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 1.5rem;
        }
        
        .tattoo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .tattoo-card {
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .tattoo-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: inherit;
        }
        
        .tattoo-image-wrapper {
            width: 100%;
            height: 250px;
            overflow: hidden;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .tattoo-card-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .tattoo-card-placeholder {
            color: #adb5bd;
            font-size: 1rem;
        }
        
        .tattoo-card-body {
            padding: 1rem;
        }
        
        .tattoo-card-title {
            font-size: 1rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .tattoo-card-tags {
            font-size: 0.875rem;
            color: #6c757d;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .tattoo-count-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: #e9ecef;
            color: #495057;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 991.98px) {
            .artist-info {
                text-align: center;
            }
            
            .social-links {
                justify-content: center;
            }
            
            .tattoo-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1rem;
            }
        }
        
        @media (max-width: 575.98px) {
            .tattoo-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <!-- Header with InkJin Logo -->
    <header class="header-section">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between py-3">
                        <a href="/" class="d-flex align-items-center gap-3 text-decoration-none">
                            @if(file_exists(public_path('assets/img/branding/main-logo.png')))
                                <img src="{{ asset('assets/img/branding/main-logo.png') }}" 
                                     alt="InkJin" 
                                     class="logo-img">
                            @elseif(file_exists(public_path('assets/img/branding/logo.png')))
                                <img src="{{ asset('assets/img/branding/logo.png') }}" 
                                     alt="InkJin" 
                                     class="logo-img">
                            @elseif(file_exists(public_path('main-logo.png')))
                                <img src="{{ asset('main-logo.png') }}" 
                                     alt="InkJin" 
                                     class="logo-img">
                            @elseif(file_exists(public_path('logo.png')))
                                <img src="{{ asset('logo.png') }}" 
                                     alt="InkJin" 
                                     class="logo-img">
                            @else
                                <span class="fs-3 fw-bold text-dark">InkJin</span>
                            @endif
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <div class="container py-5">
        <!-- Artist Profile Section -->
        <div class="artist-card">
            <div class="row align-items-center">
                <div class="col-12 col-md-auto text-center text-md-start mb-4 mb-md-0">
                    <!-- Artist Profile Picture -->
                    @if(!empty($artist['field_profile_picture']))
                    <img src="{{ $artist['field_profile_picture'] }}" 
                         alt="{{ $artist['display_name'] ?? $artist['username'] ?? 'Artist' }}" 
                         class="artist-avatar">
                    @else
                    <div class="artist-avatar-placeholder">
                        <span>👤</span>
                    </div>
                    @endif
                </div>
                
                <!-- Artist Info -->
                <div class="col-12 col-md artist-info">
                    <h1 class="artist-name">
                        {{ $artist['display_name'] ?? $artist['username'] ?? 'Artist' }}
                    </h1>
                    
                    <!-- Location -->
                    @if(!empty($artist['field_address_city']) || !empty($artist['field_address_country']))
                    <div class="location-text">
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
                    
                    <!-- Social Links -->
                    <div class="social-links">
                        @if(!empty($artist['field_profile_instagram']))
                        <a href="{{ $artist['field_profile_instagram'] }}" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="social-link instagram">
                            <svg class="social-icon" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                            Instagram
                        </a>
                        @endif
                        
                        @if(!empty($artist['field_profile_tiktok']))
                        <a href="{{ $artist['field_profile_tiktok'] }}" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="social-link tiktok">
                            <svg class="social-icon" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                            </svg>
                            TikTok
                        </a>
                        @endif
                        
                        @if(!empty($artist['field_profile_website']))
                        <a href="{{ $artist['field_profile_website'] }}" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="social-link website">
                            <svg class="social-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                            </svg>
                            Website
                        </a>
                        @endif
                    </div>
                    
                    <!-- Additional Artist Information -->
                    <div class="row">
                        @if(!empty($artist['field_profile_description']))
                        <div class="col-12 artist-info-section">
                            <div class="info-label">About</div>
                            <div class="info-value">{!! nl2br(e($artist['field_profile_description'])) !!}</div>
                        </div>
                        @endif
                        
                        @if(!empty($artist['field_profile_studio']))
                        <div class="col-12 col-md-6 artist-info-section">
                            <div class="info-label">Studio</div>
                            <div class="info-value">
                                {{ $artist['field_profile_studio'] }}
                                @if(!empty($artist['field_profile_studio_link']))
                                <a href="{{ $artist['field_profile_studio_link'] }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none">
                                    <svg class="social-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle; margin-left: 4px;">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                                @endif
                            </div>
                        </div>
                        @endif
                        
                        @if(!empty($artist['field_profile_tattooing_since']))
                        <div class="col-12 col-md-6 artist-info-section">
                            <div class="info-label">Tattooing Since</div>
                            <div class="info-value">{{ $artist['field_profile_tattooing_since'] }}</div>
                        </div>
                        @endif
                        
                        @if(!empty($artist['field_profile_primary_style']))
                        <div class="col-12 col-md-6 artist-info-section">
                            <div class="info-label">Primary Style</div>
                            <div class="info-value">{{ $artist['field_profile_primary_style'] }}</div>
                        </div>
                        @endif
                        
                        @if(!empty($artist['field_profile_style']))
                        <div class="col-12 col-md-6 artist-info-section">
                            <div class="info-label">Styles</div>
                            <div class="info-value">{{ $artist['field_profile_style'] }}</div>
                        </div>
                        @endif
                        
                        @if(!empty($artist['field_profile_mobile_phone']))
                        <div class="col-12 col-md-6 artist-info-section">
                            <div class="info-label">Phone</div>
                            <div class="info-value">
                                <a href="tel:{{ $artist['field_profile_mobile_phone'] }}" class="text-decoration-none">
                                    {{ $artist['field_profile_mobile_phone'] }}
                                </a>
                            </div>
                        </div>
                        @endif
                        
                        @if(!empty($artist['followed_count']))
                        <div class="col-12 col-md-6 artist-info-section">
                            <div class="info-label">Followers</div>
                            <div class="info-value">
                                @if(is_array($artist['followed_count']) && isset($artist['followed_count'][0]['followed_count']))
                                    {{ $artist['followed_count'][0]['followed_count'] }}
                                @elseif(is_numeric($artist['followed_count']))
                                    {{ $artist['followed_count'] }}
                                @else
                                    0
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tattoos Section -->
        <div class="tattoos-section">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h2 class="section-title mb-0">Tattoos</h2>
                @if(!empty($artist['artist_tattoo_count']) && isset($artist['artist_tattoo_count'][0]['tattoo_count']))
                <span class="tattoo-count-badge">
                    {{ $artist['artist_tattoo_count'][0]['tattoo_count'] }} Tattoo{{ $artist['artist_tattoo_count'][0]['tattoo_count'] != 1 ? 's' : '' }}
                </span>
                @endif
            </div>
            
            @if(count($tattoos) > 0)
            <div class="tattoo-grid">
                @foreach($tattoos as $tattoo)
                @php
                    $tattooSlug = slugify($tattoo['title'] ?? '');
                    $artistSlug = slugify($artist['display_name'] ?? $artist['username'] ?? '');
                    $routeName = $tattooRoute ?? 'public.tattoo';
                    
                    if ($routeName === 'public.tattoo.db') {
                        $tattooUrl = route('public.tattoo.db', [
                            'artist_display_name' => $artistSlug,
                            'tattoo_title' => $tattooSlug,
                            'tattoo_id' => $tattoo['nid'] ?? ''
                        ]);
                    } else {
                        $tattooUrl = route('public.tattoo', [
                            'artist_name' => $artistSlug,
                            'tattoo_name' => $tattooSlug,
                            'tattoo_id' => $tattoo['nid'] ?? ''
                        ]);
                    }
                @endphp
                <a href="{{ $tattooUrl }}" class="tattoo-card">
                    <div class="tattoo-image-wrapper">
                        @if(!empty($tattoo['field_tattoo_image_preview']))
                        <img src="{{ $tattoo['field_tattoo_image_preview'] }}" 
                             alt="{{ $tattoo['title'] ?? 'Tattoo' }}" 
                             class="tattoo-card-image">
                        @else
                        <span class="tattoo-card-placeholder">No Image</span>
                        @endif
                    </div>
                    <div class="tattoo-card-body">
                        <div class="tattoo-card-title">
                            {{ $tattoo['title'] ?? 'Untitled Tattoo' }}
                        </div>
                        @if(!empty($tattoo['field_tags_names']))
                        <div class="tattoo-card-tags">
                            {{ $tattoo['field_tags_names'] }}
                        </div>
                        @endif
                    </div>
                </a>
                @endforeach
            </div>
            @else
            <div class="text-center py-5">
                <p class="text-muted">No tattoos available yet.</p>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

