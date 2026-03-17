@props([
    'title'                 => 'Project Management',
    'showSearchFilter'      => true,
    'userName'              => null,
    'userRole'              => null,
    'userAvatar'            => null,
    'showNotificationBadge' => true,
    'notificationCount'     => 3,
    'searchPlaceholder'     => 'Search Projects...'
])

@vite(['resources/js/Components/navbar.js'])

<nav class="navbar-search-filter">

    {{-- ════════════════ TOP BAR ════════════════ --}}
    <div class="navbar-top">

        {{-- Left: Logo + Title --}}
        <div class="navbar-left">
            <div class="navbar-logo">
                <img class="navbar-logo-img"
                     src="{{ asset('images/LogoPutihTr.png') }}"
                     alt="Logo">
            </div>
            <h1 class="navbar-title">{{ $title }}</h1>
        </div>

        {{-- Right: User info + Avatar --}}
        <div class="navbar-right">
            <div class="user-info">

                {{-- Name & Role --}}
                <div class="user-details">
                    <span class="user-name" id="nav-user-name">
                        {{ $userName ?? 'Loading...' }}
                    </span>
                    <span class="user-role" id="nav-user-role">
                        {{ $userRole ?? '...' }}
                    </span>
                </div>

                {{-- Avatar + Dropdown --}}
                <div class="avatar-notification">
                    <div class="user-avatar" id="avatarBtn">
                        @if($userAvatar)
                            <img src="{{ $userAvatar }}" alt="User Avatar">
                        @else
                            <div class="avatar-placeholder">
                                {{ strtoupper(substr($userName ?? 'U', 0, 1)) }}
                            </div>
                        @endif
                    </div>

                    {{-- Avatar dropdown --}}
                    <div id="avatarDropdown" class="custom-dropdown-menu avatar-dropdown d-none">
                        <a href="/profile/edit" class="dropdown-item">
                            <i class="bi bi-person-gear"></i> Edit Profile
                        </a>
                        <hr class="dropdown-divider">
                        <div class="dropdown-item text-danger" id="logoutBtn">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>{{-- end navbar-top --}}

    {{-- ════════════════ BOTTOM SEARCH / FILTER BAR ════════════════ --}}
    @if($showSearchFilter)
    <div class="navbar-bottom">

        {{-- Search --}}
        <div class="search-container">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text"
                       class="form-control border-start-0 border-end-0 ps-2"
                       placeholder="{{ $searchPlaceholder }}"
                       id="navbarSearchInput">
                <button class="btn btn-primary" type="button" id="navbarSearchBtn">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>

        {{-- Filter & Sort buttons --}}
        <div class="action-buttons">

            {{-- Status filter --}}
            <div class="dropdown-wrapper">
                <button class="btn-filter" id="filterBtn">
                    <i class="bi bi-funnel"></i>
                    <span id="filterBtnText">Filter</span>
                </button>
                <div id="filterDropdown" class="custom-dropdown-menu d-none">
                    <div class="dropdown-item active" data-value="">
                        <i class="bi bi-grid-3x3-gap"></i> All Projects
                    </div>
                    <div class="dropdown-item" data-value="In Progress">
                        <i class="bi bi-arrow-repeat"></i> In Progress
                    </div>
                    <div class="dropdown-item" data-value="Completed">
                        <i class="bi bi-check-circle"></i> Completed
                    </div>
                </div>
            </div>

            {{-- Group filter --}}
            <div class="dropdown-wrapper">
                <button class="btn-filter" id="groupFilterBtn">
                    <i class="bi bi-tags"></i>
                    <span id="groupFilterBtnText">All Groups</span>
                </button>
                <div id="groupFilterDropdown" class="custom-dropdown-menu d-none">
                    <div class="dropdown-item active" data-value="">
                        <i class="bi bi-collection"></i> All Groups
                    </div>
                </div>
            </div>

            {{-- Sort --}}
            <button class="btn-sort" id="sortBtn">
                <i class="bi bi-arrow-down-up"></i>
                <span>Sort</span>
            </button>

        </div>
    </div>
    @endif

</nav>

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    @vite(['resources/css/NavbarSearchFilter.css'])
@endpush