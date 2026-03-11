<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Project List - LogbookManagement</title>

    @vite([
        'resources/js/app.js',
        'resources/css/app.css',
        'resources/css/NavbarSearchFilter.css',
        'resources/css/ProjekRead.css',
        'resources/js/Projek/read-projek.js',
        'resources/js/Components/navbar.js'
    ])

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

    {{-- ── Auth Guard ── --}}
    <script>
        const token    = localStorage.getItem('api_token');
        const userData = localStorage.getItem('user_data');
        if (!token || !userData) { window.location.href = '/login'; }
    </script>

    <script>
        let currentUser = {};
        try { currentUser = JSON.parse(localStorage.getItem('user_data')) || {}; }
        catch (e) { console.error('Failed to parse user data:', e); }
    </script>

    @include('components.NavbarSearchFilter', [
        'logo'                  => 'Logo',
        'title'                 => 'Project Management',
        'userName'              => 'User',
        'userRole'              => 'User',
        'userAvatar'            => null,
        'searchPlaceholder'     => 'Search Project...',
        'showNotificationBadge' => true,
        'notificationCount'     => 3
    ])

    <div class="main-content py-4">

        {{-- ── Page Header ── --}}
        <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
            <h1 class="h4 mb-0">
                <i class="bi bi-folder2-open me-2" style="color: #ff7d00; opacity: 0.85;"></i>Project List
            </h1>
            <div class="d-flex gap-2 flex-wrap">
                <a id="kategoriBtn"
                   href="/kategori"
                   class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1"
                   style="display:none !important;">
                    <i class="bi bi-tags"></i>
                    <span>Manage Groups</span>
                </a>
                <button type="button"
                        class="btn btn-primary btn-sm d-flex align-items-center gap-1"
                        data-bs-toggle="modal"
                        data-bs-target="#addProjekModal">
                    <i class="bi bi-plus-lg"></i>
                    <span>Add Project</span>
                </button>
            </div>
        </div>

        {{-- ── Project Cards Grid ── --}}
        <div id="projekContainer" class="row g-4">
            <div class="col-12 text-center py-5">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="text-muted mt-3" style="font-size: 0.86rem;">Loading projects...</p>
            </div>
        </div>

        <div id="errorMsg" class="mt-2 text-center"></div>

    </div>

    {{-- ══════════════════════════════════════════════════
         MODAL: Add Project
    ══════════════════════════════════════════════════ --}}
    <div class="modal fade" id="addProjekModal" tabindex="-1"
         aria-labelledby="addProjekModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="addProjekModalLabel">
                        <i class="bi bi-folder-plus me-2 opacity-75"></i>Create New Project
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <form id="formAddProjek">

                        <div class="mb-3">
                            <label class="form-label">
                                Project Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="nama" required
                                   placeholder="e.g. Website Redesign Q1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Category <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="kategori" required>
                                <option value="">— Select Category —</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Project PIC <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="pic" required
                                   placeholder="Full name of PIC">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Description
                                <span class="text-muted fw-normal ms-1" style="text-transform: none; font-size: 0.7rem;">(Optional)</span>
                            </label>
                            <textarea class="form-control" id="deskripsi" rows="2"
                                      placeholder="Short details about the project..."></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">
                                    Start Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="tgl_mulai" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">
                                    End Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="tgl_selesai" required>
                            </div>
                        </div>

                        <div id="modalAlert" class="mt-3"></div>

                    </form>
                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Cancel</button>
                    <button type="submit"
                            form="formAddProjek"
                            id="btnSubmit"
                            class="btn btn-primary px-4">
                        <span id="btnText">
                            <i class="bi bi-check2-circle me-1"></i>Save Project
                        </span>
                        <span id="btnLoader"
                              class="spinner-border spinner-border-sm d-none"
                              role="status"
                              aria-hidden="true"></span>
                    </button>
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @endpush
</body>
</html>