<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Master Group</title>

    @vite([
        'resources/css/app.css', 
        'resources/css/NavbarSearchFilter.css', 
        'resources/css/Kategori.css', 
        'resources/js/Kategori/kategori.js'
    ])
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    @include('components.NavbarSearchFilter', [
        'title' => 'Master Group',
        'showSearchFilter' => false,
        'userName' => auth()->user()->name ?? 'Guest',
        'userRole' => auth()->user()->role ?? 'No Role'
    ])

    <main class="container py-4">
        {{-- Page Header --}}
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="fw-bold mb-1">Manage Project Groups</h4>
                <p class="mb-0 text-white-50 small">Organize and manage groups for your projects</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="/projek">Project</a></li>
                    <li class="breadcrumb-item active">Master Group</li>
                </ol>
            </nav> 
        </div>

        {{-- Main Card --}}
        <div class="card kategori-card">
            <div class="kategori-card-header">
                <h6>
                    <i class="bi bi-tags"></i>
                    Group List
                </h6>
                <button class="btn btn-add-kategori text-white" data-bs-toggle="modal" data-bs-target="#modalAddKategori">
                    <i class="bi bi-plus-lg"></i>
                    Add Group
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-kategori mb-0">
                        <thead>
                            <tr>
                                <th width="5%" class="text-center">NO</th>
                                <th width="30%">GROUP NAME</th>
                                <th width="35%">DESCRIPTION</th>
                                <th width="15%" class="text-center">STATUS</th>
                                <th width="15%" class="text-center">ACTION</th>
                            </tr>
                        </thead>
                        <tbody id="tableBodyKategori">
                            <tr>
                                <td colspan="5" class="loading-row">
                                    <div class="loading-spinner"></div>
                                    <p class="text-muted mt-3 mb-0">Memuat data kategori...</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    {{-- MODAL ADD KATEGORI --}}
    <div class="modal fade" id="modalAddKategori" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formAddKategori" class="modal-content">
                <div class="modal-header text-white">
                    <h5 class="modal-title">Add New Group</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Group Name <span class="text-danger">*</span></label>
                        <input type="text" id="add_nama" class="form-control" placeholder="Example: IT Development" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Description <span class="text-muted fw-normal">(Optional)</span></label>
                        <textarea id="add_deskripsi" class="form-control" rows="3" placeholder="Describe this group..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Save
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL EDIT KATEGORI --}}
    <div class="modal fade" id="modalEditKategori" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formEditKategori" class="modal-content">
                <div class="modal-header text-white">
                    <h5 class="modal-title">Edit Group</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Group Name <span class="text-danger">*</span></label>
                        <input type="text" id="edit_nama" class="form-control" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Description <span class="text-muted fw-normal">(Optional)</span></label>
                        <textarea id="edit_deskripsi" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>