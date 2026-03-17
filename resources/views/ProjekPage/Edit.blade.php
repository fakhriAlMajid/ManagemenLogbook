<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Project - {{ $projectId }}</title>

    @vite([
        'resources/css/app.css',
        'resources/css/NavbarSearchFilter.css',
        'resources/css/Sidebar.css',
        'resources/css/EditProjek.css',
        'resources/js/Projek/edit-projek.js'
    ])

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

@include('components.NavbarSearchFilter', [
    'title'            => 'Logbook Management',
    'showSearchFilter' => false,
    'userName'         => auth()->user()->name ?? 'User'
])

<x-Sidebar :projectId="$projectId" activeMenu="edit" />

<main class="main-content">
    <div class="container-fluid">

        {{-- ── Page Header ── --}}
        <div class="page-header-wrap">
            <h4><i class="bi bi-pencil-square me-2 opacity-75"></i>Edit Project Details</h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="/projek"><i class="bi bi-house-door me-1"></i>Project</a>
                    </li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </nav>
        </div>

        <div class="row g-4">

            {{-- ════════════ PRIMARY INFO CARD ════════════ --}}
            <div class="col-lg-8">
                <div class="card border h-100">
                    <div class="card-header card-header-dark py-3 d-flex align-items-center gap-2 text-light">
                        <i class="bi bi-info-circle opacity-75"></i>
                        Primary Information
                    </div>
                    <div class="card-body p-4">
                        <form id="formEditProjek">

                            <div class="mb-3">
                                <label class="form-label">Project Name</label>
                                <input type="text" id="pjk_nama" class="form-control" required
                                       placeholder="e.g. Website Redesign Q1">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Group</label>
                                <select id="pjk_kategori" class="form-select" required>
                                    <option value="">— Select Group —</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea id="pjk_deskripsi" class="form-control" rows="4"
                                          placeholder="Briefly describe the project scope and goals..."></textarea>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="form-label">Project PIC <span class="text-muted fw-normal text-lowercase">(External)</span></label>
                                    <input type="text" id="pjk_pic" class="form-control" required
                                           placeholder="Full name of PIC">
                                </div>
                                <div class="d-none">
                                    <label class="form-label">Project Status</label>
                                    <select id="pjk_status" class="form-select">
                                        <option value="Pending">Pending</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Completed">Completed</option>
                                        <option value="OnHold">On Hold</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mt-0">
                                <div class="col-md-6">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" id="pjk_tgl_mulai" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Date</label>
                                    <input type="date" id="pjk_tgl_selesai" class="form-control" required>
                                </div>
                            </div>

                            <hr class="my-4">

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <button type="button" id="btnHapusProjek"
                                        class="btn btn-outline-danger"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteConfirmModal">
                                    <i class="bi bi-trash3 me-2"></i>Delete Project
                                </button>
                                <button type="submit" class="btn btn-primary px-5 shadow-sm">
                                    <i class="bi bi-check2-circle me-2"></i>Save Changes
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>

            {{-- ════════════ TEAM MEMBERS CARD ════════════ --}}
            <div class="col-lg-4">
                <div class="card border h-100">
                    <div class="card-header card-header-dark py-3 d-flex justify-content-between align-items-center text-light">
                        <span class="d-flex align-items-center gap-2">
                            <i class="bi bi-people opacity-75"></i>
                            Team Members
                        </span>
                        <span class="badge" id="memberCount">0</span>
                    </div>

                    <div class="card-body p-0">
                        <div id="teamMembersList"
                             class="list-group list-group-flush"
                             style="max-height: 500px; overflow-y: auto;">
                            <div class="text-center p-5 text-muted">
                                <div class="spinner-border spinner-border-sm mb-2" role="status"></div>
                                <div style="font-size: 0.84rem;">Loading members...</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white p-3 border-top">
                        <button type="button" id="btnAddTeamMember"
                                class="btn btn-outline-primary w-100 dashed-border">
                            <i class="bi bi-plus-lg me-2"></i>Add Team Member
                        </button>
                    </div>
                </div>
            </div>

        </div>{{-- end row --}}
    </div>
</main>

{{-- ══════════════════════════════════════════════════
     MODAL: Add Member
     ══════════════════════════════════════════════════ --}}
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 overflow-hidden">

            <div class="modal-header modal-header-dark p-3">
                <h5 class="modal-title fw-bold text-white">
                    <i class="bi bi-person-plus me-2 opacity-75"></i>Select New Member
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">

                {{-- Search --}}
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" id="searchUser"
                           class="form-control border-start-0 ps-2"
                           placeholder="Search by name...">
                    <button class="btn btn-primary" type="button" id="btnSearchTrigger">Search</button>
                </div>

                <div class="d-flex text-muted mb-2 px-1" style="font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em;">
                    <span>Name</span>
                </div>

                {{-- User List --}}
                <div id="userSelectionList" class="user-list-container mb-4">
                    <div class="text-center py-5 text-muted">
                        <div class="spinner-border spinner-border-sm mb-2" role="status"></div>
                        <div style="font-size: 0.82rem;">Loading users...</div>
                    </div>
                </div>

                {{-- Role Input --}}
                <div class="mb-3">
                    <label class="form-label text-dark-blue">Role in Project</label>
                    <input type="text" id="inputRole" class="form-control"
                           placeholder="e.g. Frontend Dev, QA, UI Designer">
                </div>

                <div class="d-grid">
                    <button type="button" id="btnSubmitAddMember"
                            class="btn btn-primary py-2 fw-bold" disabled>
                        <span id="btnSubmitText">
                            <i class="bi bi-person-check me-2"></i>Add Member
                        </span>
                        <span id="btnSubmitLoader"
                              class="spinner-border spinner-border-sm ms-2"
                              role="status"
                              aria-hidden="true"
                              style="display: none;"></span>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════
     MODAL: Edit Member Role
     ══════════════════════════════════════════════════ --}}
<div class="modal fade" id="editMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">

            <div class="modal-header modal-header-dark p-3">
                <h5 class="modal-title fw-bold text-white">
                    <i class="bi bi-pencil me-2 opacity-75"></i>Edit Member Role
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">
                <form id="formEditMember">
                    <input type="hidden" id="editMemberId">

                    <div class="mb-3">
                        <label class="form-label text-muted" style="text-transform: none; font-weight: 500;">Member Name</label>
                        <input type="text" id="editMemberName" class="form-control bg-light" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark-blue">Role in Project</label>
                        <input type="text" id="editMemberRole" class="form-control" required
                               placeholder="e.g. Backend Dev">
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary fw-bold">
                            <i class="bi bi-check2-circle me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════
     MODAL: Delete Project Confirmation
     ══════════════════════════════════════════════════ --}}
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">

            <div class="modal-header modal-header-dark p-3">
                <h5 class="modal-title fw-bold text-white">
                    <i class="bi bi-exclamation-triangle me-2 opacity-75"></i>Delete Project
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4">

                <div class="alert alert-danger small mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>This action cannot be undone!</strong> All modules, activities, tasks and logbooks will be permanently deleted.
                </div>

                <p class="text-muted mb-3" style="font-size: 0.86rem;">
                    To confirm deletion, type the following text exactly:
                </p>

                <div class="alert alert-light border mb-3 py-2 px-3">
                    <code id="confirmationText" class="fw-bold text-danger"></code>
                </div>

                <div class="mb-4">
                    <label class="form-label">Confirmation Text</label>
                    <input type="text" id="deleteConfirmInput" class="form-control"
                           placeholder="Type the confirmation text...">
                </div>

                <div class="d-grid gap-2">
                    <button type="button" id="btnConfirmDelete"
                            class="btn btn-danger fw-bold" disabled>
                        <i class="bi bi-trash3 me-2"></i>Delete Project Permanently
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>