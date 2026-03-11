<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jobs - {{ $projek->pjk_nama }}</title>

    @vite(['resources/css/app.css', 'resources/css/NavbarSearchFilter.css', 'resources/css/Sidebar.css', 'resources/css/Jobs.css', 'resources/js/Projek/jobs.js'])

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

@include('components.NavbarSearchFilter', [
    'title'    => 'Logbook Management System',
    'showSearchFilter' => false,
    'userName' => auth()->user()->name ?? 'Guest',
    'userRole' => auth()->user()->role ?? 'No Role'
])

<x-Sidebar :projectId="$projectId" activeMenu="jobs" />

<main class="main-content">

    {{-- Hidden project date bounds for JS validation --}}
    <input type="hidden" id="pjk_start_date" value="{{ $projek->pjk_tanggal_mulai }}">
    <input type="hidden" id="pjk_end_date"   value="{{ $projek->pjk_tanggal_selesai }}">

    {{-- ── Flash Messages ── --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{!! session('error') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- ── Page Header ── --}}
    <div class="page-header-wrap">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if(count($moduls) > 0)
                <button class="btn-add-modul shadow-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#modalAddModul">
                    <i class="bi bi-plus-lg"></i> Add Module
                </button>
            @endif

            @if(count($moduls) === 0)
                <button type="button"
                        class="btn btn-success shadow-sm d-flex align-items-center gap-2"
                        data-bs-toggle="modal"
                        data-bs-target="#modalImportExcel">
                    <i class="bi bi-file-earmark-excel"></i> Import from Excel
                </button>
            @endif
        </div>

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="/projek"><i class="bi bi-house-door me-1"></i>Project</a>
                </li>
                <li class="breadcrumb-item active">Jobs</li>
            </ol>
        </nav>
    </div>

    {{-- ════════════════════════════════════════════
         EMPTY STATE
    ════════════════════════════════════════════ --}}
    @if(count($moduls) === 0)
        <div class="card border-0 shadow-sm rounded-3 py-5">
            <div class="card-body text-center py-4">
                <i class="bi bi-folder-plus d-block mb-3" style="font-size: 3.5rem; color: #c8d8e8;"></i>
                <h4 class="fw-bold" style="color: #0f2d45;">No Modules Yet</h4>
                <p class="text-muted mb-4 mx-auto" style="max-width: 400px; font-size: 0.88rem;">
                    This project doesn't have a module structure yet. Add your first module or import from Excel to start managing tasks.
                </p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <button class="btn btn-primary px-4 shadow-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#modalAddModul">
                        <i class="bi bi-plus-lg me-2"></i>Add Module Now
                    </button>
                    <button class="btn btn-success px-4 shadow-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#modalImportExcel">
                        <i class="bi bi-file-earmark-excel me-2"></i>Import Excel
                    </button>
                </div>
            </div>
        </div>

    @else
    {{-- ════════════════════════════════════════════
         MODULE LOOP
    ════════════════════════════════════════════ --}}
    @foreach($moduls as $index => $modul)
        <div class="card mb-4">

            {{-- Module Header --}}
            <div class="card-header card-header-dark py-3 d-flex justify-content-between align-items-center"
                 style="cursor: pointer;"
                 onclick="window.openModalEditModul({{ $modul->mdl_id }}, '{{ $modul->mdl_nama }}')">
                <span class="d-flex align-items-center gap-2 text-light">
                    <i class="bi bi-layers-half opacity-75"></i>
                    MODULE {{ $index + 1 }}: {{ strtoupper($modul->mdl_nama) }}
                </span>
                <i class="bi bi-pencil-square"></i>
            </div>

            <div class="card-body p-3 p-md-4">

                @foreach($modul->kegiatans as $kegiatan)
                <div class="kegiatan-wrapper mb-4">

                    {{-- Kegiatan Header --}}
                    <div class="bg-kegiatan"
                         style="cursor: pointer;"
                         onclick="window.openModalEditKegiatan({{ $kegiatan->kgt_id }}, '{{ $kegiatan->kgt_nama }}')">
                        <span class="d-flex align-items-center gap-2">
                            <i class="bi bi-chevron-right opacity-60" style="font-size: 0.7rem;"></i>
                            {{ $kegiatan->kgt_nama }}
                        </span>
                        <i class="bi bi-pencil-square"></i>
                    </div>

                    {{-- Task Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mt-2 mb-0">
                            <thead>
                                <tr>
                                    <th width="44">No</th>
                                    <th>Task</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th class="text-center">Weight</th>
                                    <th class="text-center">Progress</th>
                                    <th>Code</th>
                                    <th>PIC</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($kegiatan->tugas as $tidx => $tgs)
                                    <tr onclick="window.openModalEditTugas({{ $tgs->tgs_id }})">
                                        <td class="text-muted" style="font-size: 0.76rem;">{{ $tidx + 1 }}</td>
                                        <td class="fw-medium">{{ $tgs->tgs_nama }}</td>
                                        <td style="font-size: 0.8rem; white-space: nowrap;">
                                            {{ date('d/m/Y', strtotime($tgs->tgs_tanggal_mulai)) }}
                                        </td>
                                        <td style="font-size: 0.8rem; white-space: nowrap;">
                                            {{ date('d/m/Y', strtotime($tgs->tgs_tanggal_selesai)) }}
                                        </td>
                                        <td class="text-center" style="font-size: 0.82rem; font-weight: 600;">
                                            {{ $tgs->tgs_bobot }}
                                        </td>
                                        <td class="text-center">
                                            <span class="{{ $tgs->tgs_status == 'Selesai' ? 'badge bg-success' : 'badge bg-secondary' }}">
                                                {{ number_format($tgs->tgs_persentase_progress ?? $tgs->tgs_persentasi_progress, 0) }}%
                                                {{ $tgs->tgs_status }}
                                            </span>
                                        </td>
                                        <td class="text-muted small">{{ $tgs->tgs_kode_prefix }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $tgs->pic_name }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <span class="text-muted">No tasks yet.</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Add Task --}}
                    <button class="btn btn-link btn-sm text-decoration-none p-0 mt-2 text-muted d-flex align-items-center gap-1"
                            onclick="openModalTugas({{ $kegiatan->kgt_id }}, '{{ $kegiatan->kgt_nama }}')">
                        <i class="bi bi-plus-circle"></i> Add New Task
                    </button>

                </div>
                @endforeach

                {{-- Add Activity --}}
                <button class="btn-add-kegiatan mt-1"
                        onclick="openModalKegiatan({{ $modul->mdl_id }}, '{{ $modul->mdl_nama }}')">
                    <i class="bi bi-plus-lg"></i> Add New Activity
                </button>

            </div>
        </div>
    @endforeach
    @endif

</main>

{{-- ══════════════════════════════════════════════════
     MODAL: Add Module
══════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalAddModul" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formAddModul" class="modal-content">
            <div class="modal-header card-header-dark">
                <h5 class="modal-title">
                    <i class="bi bi-layers-half me-2 opacity-75"></i>Add New Module
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="pjk_id" value="{{ $projectId }}">
                <div class="mb-3">
                    <label class="form-label">Module Name</label>
                    <input type="text" name="nama" class="form-control" required placeholder="e.g. Frontend Development">
                </div>
                <div class="mb-3">
                    <label class="form-label">Sequence (No)</label>
                    <input type="number" name="urut" class="form-control" value="{{ count($moduls) + 1 }}" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check2-circle me-2"></i>Save Module
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════
     MODAL: Add Activity
══════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalAddKegiatan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formAddKegiatan" class="modal-content">
            <div class="modal-header card-header-dark">
                <h5 class="modal-title">
                    <i class="bi bi-collection me-2 opacity-75"></i>
                    Add Activity <span class="fw-normal opacity-75">(<span id="title_mdl"></span>)</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="mdl_id" id="input_mdl_id">
                <div class="mb-3">
                    <label class="form-label">Activity Name</label>
                    <input type="text" name="nama" class="form-control" placeholder="e.g. Requirement Analysis" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check2-circle me-2"></i>Save Activity
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════
     MODAL: Add Task
══════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalAddTugas" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="formAddTugas" class="modal-content">
            <div class="modal-header card-header-dark">
                <h5 class="modal-title">
                    <i class="bi bi-plus-square me-2 opacity-75"></i>
                    Add Task <span class="fw-normal opacity-75">(<span id="title_kgt"></span>)</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="kgt_id" id="input_kgt_id">
                <div id="alertAddTugas" class="mb-3"></div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Task Name</label>
                        <input type="text" name="nama" class="form-control" required placeholder="Describe the task...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="tgl_mulai" id="add_tgl_mulai" class="form-control" required>
                        <small class="text-muted d-block mt-1">Must be within project timeline</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">End Date</label>
                        <input type="date" name="tgl_selesai" id="add_tgl_selesai" class="form-control" required>
                        <small class="text-muted d-block mt-1">Must be after start date</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Weight</label>
                        <input type="number" name="bobot" class="form-control" min="1" max="100" required placeholder="1 – 100">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">PIC (User)</label>
                        <select name="usr_id" class="form-select" required id="select_pic">
                            <option value="">Select PIC...</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check2-circle me-2"></i>Save Task
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════
     MODAL: Edit Task
══════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalEditTugas" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form id="formEditTugas" class="modal-content">
            <div class="modal-header card-header-dark">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2 opacity-75"></i>
                    Edit Task: <span id="display_edit_tgs_nama" class="fw-normal opacity-75"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="alertEditTugas" class="mb-3"></div>
                <input type="hidden" id="edit_tgs_id">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Task Name</label>
                        <input type="text" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Code</label>
                        <input type="text" id="edit_kode" class="form-control" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">PIC (Project Member)</label>
                        <select id="edit_usr_id" class="form-select" required></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Weight</label>
                        <input type="number" id="edit_bobot" class="form-control" min="1" max="100" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Progress (%)</label>
                        <input type="number" id="edit_progress" class="form-control" min="0" max="100" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">End Date</label>
                        <input type="date" id="edit_tgl_selesai" class="form-control" required>
                        <small class="text-muted d-block mt-1">Within project timeline</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-danger" id="btnHapusTugas">
                    <i class="bi bi-trash3 me-1"></i> Delete Task
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════
     MODAL: Edit Activity
══════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalEditKegiatan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formEditKegiatan" class="modal-content">
            <div class="modal-header card-header-dark">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2 opacity-75"></i>Edit Activity
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_kgt_id">
                <div class="mb-3">
                    <label class="form-label">Activity Name</label>
                    <input type="text" id="edit_kgt_nama" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-danger" id="btnHapusKegiatan">
                    <i class="bi bi-trash3 me-1"></i> Delete Activity
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════
     MODAL: Edit Module
══════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalEditModul" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form id="formEditModul" class="modal-content">
            <div class="modal-header card-header-dark">
                <h5 class="modal-title">
                    <i class="bi bi-layers-half me-2 opacity-75"></i>Edit Module
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_mdl_id">
                <div class="mb-3">
                    <label class="form-label">Module Name</label>
                    <input type="text" id="edit_mdl_nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Sequence (No)</label>
                    <input type="number" id="edit_mdl_urut" class="form-control" min="1" required>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="button" class="btn btn-danger" id="btnHapusModul">
                    <i class="bi bi-trash3 me-1"></i> Delete Module
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════
     MODAL: Import Excel
══════════════════════════════════════════════════ --}}
<div class="modal fade" id="modalImportExcel" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('jobs.import', $projectId) }}"
              method="POST"
              enctype="multipart/form-data"
              class="modal-content">
            @csrf
            <div class="modal-header card-header-dark">
                <h5 class="modal-title">
                    <i class="bi bi-file-earmark-excel me-2 opacity-75"></i>Import Jobs dari Excel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Pastikan format kolom sesuai template sistem.
                    <a href="{{ asset('templates/LogbookImport1.xlsx') }}" download class="fw-semibold ms-1">
                        <i class="bi bi-download me-1"></i>Download Template
                    </a>
                </div>
                <div class="mb-3">
                    <label class="form-label">Pilih File Excel (.xlsx)</label>
                    <input type="file" name="file_excel" class="form-control" accept=".xlsx,.xls,.csv" required>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-upload me-2"></i>Mulai Import
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>