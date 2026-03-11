@props([
    'id' => null,
    'nama' => 'Tanpa Nama',
    'deskripsi' => '-',
    'progress' => 0,
    'tanggalMulai' => null,
    'tanggalSelesai' => null,
    'user' => '-',
    'pic' => '-',
    'leader' => '-',
    'kategori' => '-',
    'tasksDone' => 0,
    'tasksTotal' => 0
])

@vite(['resources/css/ProjekCard.css', 'resources/js/app.css'])

@php
    $formatDate = function($dateStr) {
        if (!$dateStr) return '-';
        return \Carbon\Carbon::parse($dateStr)->locale('id')->isoFormat('D MMM YYYY');
    };

    $dateRange = $formatDate($tanggalMulai) . ' – ' . $formatDate($tanggalSelesai);

    $progressValue = floatval($progress);

    $textColor = 'text-danger';
    $bgColor = 'bg-danger';
    $checkIcon = '';

    if ($progressValue >= 100) {
        $textColor = 'text-success';
        $bgColor = 'bg-success';
        $checkIcon = '
            <span class="ms-2 d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                <i class="bi bi-check2-circle text-success ms-4"></i>
            </span>';
    } elseif ($progressValue >= 50) {
        $textColor = 'text-warning';
        $bgColor = 'bg-warning';
    }
@endphp

<div class="card h-100 project-card"
     data-project-id="{{ $id }}"
     onclick="showProjectDetail({{ $id }})"
     style="cursor:pointer">

    <div class="card-body d-flex flex-column justify-content-between" style="padding: 1.6rem 1.5rem 1.4rem 1.75rem;">

        <div>
            {{-- Header --}}
            <div class="mb-4">
                <h5 class="nama text-uppercase text-truncate" title="{{ $nama }}">
                    {{ $nama }}
                </h5>
                <span class="date-range">
                    <i class="bi bi-calendar3 me-1" style="font-size:0.65rem; opacity:0.7;"></i>{{ $dateRange }}
                </span>
            </div>

            {{-- Progress --}}
            <div class="mb-1">
                <div class="d-flex align-items-center mb-2">
                    <span class="{{ $textColor }} progress-num">
                        {{ number_format($progressValue, 0) }}<span style="font-size:1.1rem;font-weight:700;letter-spacing:0;vertical-align:top;margin-top:0.5rem;display:inline-block;">%</span>
                    </span>
                    {!! $checkIcon !!}
                </div>

                <div class="progress mb-2" style="height:8px;">
                    <div class="progress-bar {{ $bgColor }}"
                         role="progressbar"
                         style="width: {{ $progressValue }}%">
                    </div>
                </div>

                <span class="tasks-label">
                    <i class="bi bi-check2-square me-1"></i>{{ $tasksDone }}/{{ $tasksTotal }} tasks done
                </span>
            </div>
        </div>

        <hr>

        {{-- Footer Info --}}
        <div class="d-grid gap-2 project-meta">
            <div class="meta-row">
                <span class="meta-icon-pill icon-pic">
                    <i class="bi bi-briefcase-fill"></i>
                </span>
                <div>
                    <div class="meta-key">PIC</div>
                    <div class="meta-val">{{ $pic }}</div>
                </div>
            </div>

            <div class="meta-row">
                <span class="meta-icon-pill icon-leader">
                    <i class="bi bi-award-fill"></i>
                </span>
                <div>
                    <div class="meta-key">Leader</div>
                    <div class="meta-val">{{ $leader }}</div>
                </div>
            </div>

            <div class="meta-row">
                <span class="meta-icon-pill icon-kategori">
                    <i class="bi bi-tags-fill"></i>
                </span>
                <div>
                    <div class="meta-key">Group</div>
                    <div class="meta-val">{{ $kategori }}</div>
                </div>
            </div>

        </div>

        <div class="hover-detail-text">
            <i class="bi bi-arrow-right-circle me-1"></i>View Details
        </div>

    </div>
</div>