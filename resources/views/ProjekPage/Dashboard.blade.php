<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $projek->pjk_nama }} — Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="{{ asset('css/Sidebar.css') }}">
    @vite([
        'resources/js/app.js',
        'resources/css/app.css',
        'resources/css/NavbarSearchFilter.css',
        'resources/css/ProjekRead.css',
        'resources/css/Dashboard.css'
    ])
</head>
<body>

<x-Sidebar :projectId="$projek->pjk_id" activeMenu="beranda" />

@include('components.NavbarSearchFilter', [
    'title'      => 'Logbook Management System',
    'showSearchFilter' => false,
    'userName'   => auth()->user()->name ?? 'Guest',
    'userRole'   => auth()->user()->role ?? 'No Role',
    'userAvatar' => auth()->user()->avatar ?? null,
])

<main class="main-content">

    {{-- ── Page Header ── --}}
    <div class="page-header-wrap">
        <a href="/projek" class="btn-back-custom shadow-sm">
            <i class="bi bi-chevron-left"></i>
            <span>Project List</span>
        </a>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="/projek"><i class="bi bi-house-door me-1"></i>Project</a>
                </li>
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>
    </div>

    {{-- ── Dashboard Card ── --}}
    <div class="dashboard-card card">
        <div class="card-body p-3 p-md-4">

            {{-- Project Title --}}
            <div class="page-header mb-3">
                <h2>
                    <i class="bi bi-bar-chart-line me-2 opacity-75" style="color: #ff7d00;"></i>
                    {{ $projek->pjk_nama }}
                </h2>
                <p class="subtitle">
                    <i class="bi bi-grid me-1"></i>Project Overview
                </p>
            </div>
            <hr class="mb-4">

            <div class="row g-4">

                {{-- ════════════ LEFT: Breakdown Table ════════════ --}}
                <div class="col-lg-9">
                    <div class="table-responsive rounded border">
                        <table class="table custom-table mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 5%">No</th>
                                    <th style="width: 33%">Activity Description / Module</th>
                                    <th class="text-center" style="width: 14%">% Complete</th>
                                    <th class="text-center" style="width: 10%">Weight</th>
                                    <th class="text-center" style="width: 14%">Weight Portion</th>
                                    <th class="text-center" style="width: 14%">Contribution</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $counter = 1; @endphp
                                @foreach($breakdown as $item)
                                    @if($item->tipe_item === 'Modul')
                                        <tr class="modul-row">
                                            <td class="text-center">
                                                <i class="bi bi-layers-half"></i>
                                            </td>
                                            <td colspan="2">MODULE: {{ $item->nama_item }}</td>
                                            <td class="text-center">{{ $item->bobot_angka }}</td>
                                            <td class="text-center">{{ number_format($item->prosentase_item, 2) }}%</td>
                                            <td></td>
                                        </tr>
                                        @php $counter = 1; @endphp
                                    @else
                                        <tr>
                                            <td class="text-center text-muted">
                                                {{ chr(64 + $counter++) }}
                                            </td>
                                            <td class="ps-4" style="font-size: 0.84rem;">
                                                {{ $item->nama_item }}
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <span class="progress-text">
                                                        {{ number_format($item->progress_persen, 2) }}%
                                                    </span>
                                                    <div class="progress d-none d-md-flex" style="height: 6px; min-width: 52px; flex: 1;">
                                                        <div class="progress-bar" style="width: {{ $item->progress_persen }}%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center" style="font-size: 0.82rem; font-weight: 600;">
                                                {{ $item->bobot_angka }}
                                            </td>
                                            <td class="text-center text-muted" style="font-size: 0.8rem; font-family: 'JetBrains Mono', monospace;">
                                                {{ number_format($item->prosentase_item, 2) }}%
                                            </td>
                                            <td class="text-center fw-bold text-success">
                                                {{ number_format($item->kontribusi_total, 2) }}%
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Total Progress ── --}}
                    @php
                        $totalRealProgress = collect($breakdown)->where('tipe_item', 'Kegiatan')->sum('kontribusi_total');
                    @endphp
                    <div class="total-progress-wrap">
                        <span class="label">
                            <i class="bi bi-graph-up-arrow me-2 opacity-75" style="color: #ff7d00;"></i>
                            Total Project Progress
                        </span>
                        <span class="value">{{ number_format($totalRealProgress, 2) }}%</span>
                    </div>
                </div>

                {{-- ════════════ RIGHT: Team Card ════════════ --}}
                <div class="col-lg-3">
                    <div class="team-card">
                        <div class="team-header d-flex align-items-center gap-2">
                            <i class="bi bi-people opacity-75"></i>
                            Team Members
                        </div>
                        <div class="team-body">
                            @foreach($team as $member)
                                <div class="member-item">
                                    <div class="avatar-circle">
                                        @if($member->usr_avatar_url)
                                            <img src="{{ $member->usr_avatar_url }}"
                                                 alt="{{ $member->usr_first_name }}">
                                        @else
                                            {{ strtoupper(substr($member->usr_first_name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <div class="lh-1">
                                        <div class="member-name">
                                            {{ $member->usr_first_name }} {{ $member->usr_last_name }}
                                        </div>
                                        <div class="member-role">
                                            {{ $member->mpk_role_projek }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            </div>{{-- end row --}}
        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>