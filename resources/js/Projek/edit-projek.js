import axios from "axios";

const apiToken = localStorage.getItem("api_token");
if (apiToken) {
    axios.defaults.headers.common["Authorization"] = `Bearer ${apiToken}`;
}
axios.defaults.headers.common["Accept"] = "application/json";

const apiBase = "/api";

document.addEventListener("DOMContentLoaded", function () {
    const urlParts = window.location.pathname.split("/");
    const projectId = urlParts[2]; // Pastikan urutan URL sesuai (misal: /projek/1/edit)

    const tglMulaiInput = document.getElementById("pjk_tgl_mulai");
    const tglSelesaiInput = document.getElementById("pjk_tgl_selesai");
    const kategoriSelect = document.getElementById("pjk_kategori");

    let currentUserRole = null;
    let userAccountRole = null;

    // Load categories
    const loadCategories = async () => {
        try {
            const { data: response } = await axios.get(`${apiBase}/kategori`);
            const categories = Array.isArray(response)
                ? response
                : response.data || [];

            if (kategoriSelect) {
                const currentValue = kategoriSelect.value;

                // Keep the default option
                kategoriSelect.innerHTML =
                    '<option value="">-- Select Category --</option>';

                // Add categories to select
                categories.forEach((cat) => {
                    // Perbaikan: gunakan loose equality agar false / "0" / 0 tetap tertangkap
                    if (cat.ktg_is_active == 0 || cat.ktg_is_active === false)
                        return;

                    const option = document.createElement("option");
                    option.value = cat.id || cat.ktg_id;
                    option.textContent = cat.nama || cat.ktg_nama;
                    kategoriSelect.appendChild(option);
                });

                if (currentValue) kategoriSelect.value = currentValue;
            }
        } catch (err) {
            console.error("Failed to load categories:", err);
        }
    };

    loadCategories();

    function initializeDateValidation() {
        const today = new Date();
        const todayString = today.toISOString().split("T")[0];

        if (tglMulaiInput) {
            tglMulaiInput.setAttribute("min", todayString);
        }

        if (tglSelesaiInput) {
            tglSelesaiInput.setAttribute("min", todayString);
        }
    }

    if (tglMulaiInput) {
        tglMulaiInput.addEventListener("change", function () {
            const tglMulaiValue = this.value;

            if (!tglMulaiValue) {
                if (tglSelesaiInput) {
                    tglSelesaiInput.value = "";
                    tglSelesaiInput.removeAttribute("min");
                }
            } else {
                const mulaiDate = new Date(tglMulaiValue);
                const selesaiMinDate = new Date(mulaiDate);
                selesaiMinDate.setDate(selesaiMinDate.getDate() + 1);
                const selesaiMinDateString = selesaiMinDate
                    .toISOString()
                    .split("T")[0];

                if (tglSelesaiInput) {
                    tglSelesaiInput.setAttribute("min", selesaiMinDateString);
                }

                if (tglSelesaiInput && tglSelesaiInput.value) {
                    const selesaiDate = new Date(tglSelesaiInput.value);
                    if (selesaiDate <= mulaiDate) {
                        tglSelesaiInput.value = "";
                    }
                }
            }
        });
    }

    function loadProjectData() {
        axios
            .get(`/api/projek/${projectId}`)
            .then((res) => {
                const data = res.data.detail;
                document.getElementById("pjk_nama").value = data.nama;
                document.getElementById("pjk_deskripsi").value = data.deskripsi;
                document.getElementById("pjk_pic").value = data.pic;
                document.getElementById("pjk_status").value = data.status;
                document.getElementById("pjk_kategori").value =
                    data.kategori_id || "";
                document.getElementById("pjk_tgl_mulai").value =
                    data.tanggal_mulai;
                document.getElementById("pjk_tgl_selesai").value =
                    data.tanggal_selesai;

                initializeDateValidation();

                // Set confirmation text untuk delete modal
                const confirmText = `Delete project ${data.nama}`;
                document.getElementById("confirmationText").textContent =
                    confirmText;

                // Load current user's role in this project
                loadCurrentUserRole();
            })
            .catch(() =>
                Swal.fire("Error", "Failed to retrieve project data", "error"),
            );
    }

    function loadCurrentUserRole() {
        axios
            .get(`/api/projek/${projectId}/member`)
            .then((res) => {
                const members = res.data;

                // Get current user ID from localStorage
                const userData = JSON.parse(
                    localStorage.getItem("user_data") || "{}",
                );
                const currentUserId = userData.usr_id || userData.id;

                userAccountRole = userData.role;

                const currentMember = members.find(
                    (m) => m.user.id === currentUserId,
                );
                if (currentMember) {
                    currentUserRole = currentMember.role;
                }

                // Show/Hide buttons based on role
                const btnAddTeamMember =
                    document.getElementById("btnAddTeamMember");
                const btnHapusProjek =
                    document.getElementById("btnHapusProjek");

                if (
                    currentUserRole === "Ketua" ||
                    userAccountRole === "Admin"
                ) {
                    if (btnAddTeamMember)
                        btnAddTeamMember.style.display = "block";
                    if (btnHapusProjek) btnHapusProjek.style.display = "block";
                } else {
                    if (btnAddTeamMember)
                        btnAddTeamMember.style.display = "none";
                    if (btnHapusProjek) btnHapusProjek.style.display = "none";
                }

                // Load members after getting user role
                loadMembers();
            })
            .catch(() => {
                console.error("Failed to load user role");
                loadMembers();
            });
    }

    // PERBAIKAN: Fungsi ini harus dipanggil agar datanya muncul saat halaman dibuka
    loadProjectData();

    const form = document.getElementById("formEditProjek");
    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();

            const tglMulaiValue =
                document.getElementById("pjk_tgl_mulai").value;
            const tglSelesaiValue =
                document.getElementById("pjk_tgl_selesai").value;

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            // Validate tgl_mulai
            if (!tglMulaiValue) {
                Swal.fire(
                    "Validation Error",
                    "Tanggal mulai harus diisi!",
                    "warning",
                );
                return;
            }

            const mulaiDate = new Date(tglMulaiValue);
            if (mulaiDate < today) {
                Swal.fire(
                    "Validation Error",
                    "Tanggal mulai tidak boleh kurang dari hari ini!",
                    "warning",
                );
                return;
            }

            // Validate tgl_selesai
            if (!tglSelesaiValue) {
                Swal.fire(
                    "Validation Error",
                    "Tanggal selesai harus diisi!",
                    "warning",
                );
                return;
            }

            const selesaiDate = new Date(tglSelesaiValue);
            if (selesaiDate <= mulaiDate) {
                Swal.fire(
                    "Validation Error",
                    "Tanggal selesai harus lebih besar dari tanggal mulai!",
                    "warning",
                );
                return;
            }

            const payload = {
                nama: document.getElementById("pjk_nama").value,
                kategori_id: document.getElementById("pjk_kategori").value,
                deskripsi: document.getElementById("pjk_deskripsi").value,
                pic: document.getElementById("pjk_pic").value,
                status: document.getElementById("pjk_status").value,
                tgl_mulai: tglMulaiValue,
                tgl_selesai: tglSelesaiValue,
            };

            axios
                .put(`/api/projek/${projectId}`, payload)
                .then(() =>
                    Swal.fire({
                        icon: "success",
                        title: "Success!",
                        timer: 1500,
                        showConfirmButton: false,
                        toast: true,
                        position: "top-end",
                    }).then(() => loadProjectData()),
                )
                .catch((err) =>
                    Swal.fire({
                        icon: "error",
                        title: "Failed",
                        text: err.response?.data?.message || "Error",
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 2000,
                    }),
                );
        });
    }

    // ========== DELETE PROJECT CONFIRMATION ==========
    const deleteConfirmInput = document.getElementById("deleteConfirmInput");
    const btnConfirmDelete = document.getElementById("btnConfirmDelete");
    const deleteConfirmModal = document.getElementById("deleteConfirmModal");

    // Event listener untuk input teks konfirmasi
    if (deleteConfirmInput) {
        deleteConfirmInput.addEventListener("input", function () {
            const confirmText =
                document.getElementById("confirmationText").textContent;
            const inputValue = this.value;

            // Enable button hanya jika text match persis
            if (inputValue === confirmText) {
                btnConfirmDelete.disabled = false;
            } else {
                btnConfirmDelete.disabled = true;
            }
        });
    }

    // Event listener untuk button confirm delete
    if (btnConfirmDelete) {
        btnConfirmDelete.addEventListener("click", function () {
            const confirmText =
                document.getElementById("confirmationText").textContent;
            const inputValue = deleteConfirmInput.value;

            // Double-check validation
            if (inputValue !== confirmText) {
                Swal.fire(
                    "Error",
                    "Confirmation text does not match!",
                    "error",
                );
                return;
            }

            // Disable button dan ubah text menjadi loading
            btnConfirmDelete.disabled = true;
            const originalText = btnConfirmDelete.innerHTML;
            btnConfirmDelete.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

            axios
                .delete(`/api/projek/${projectId}`)
                .then(() => {
                    Swal.fire({
                        icon: "success",
                        title: "Project Deleted",
                        text: "Project has been permanently deleted.",
                        showConfirmButton: false,
                        timer: 2000,
                        toast: true,
                        position: "top-end",
                    }).then(() => {
                        window.location.href = "/projek";
                    });
                })
                .catch((err) => {
                    btnConfirmDelete.disabled = false;
                    btnConfirmDelete.innerHTML = originalText;
                    Swal.fire(
                        "Error",
                        err.response?.data?.message ||
                            "Failed to delete project",
                        "error",
                    );
                });
        });
    }

    // Reset modal saat dibuka
    if (deleteConfirmModal) {
        deleteConfirmModal.addEventListener("show.bs.modal", function () {
            deleteConfirmInput.value = "";
            btnConfirmDelete.disabled = true;
            btnConfirmDelete.innerHTML = "Delete Project Permanently";
        });
    }

    // ==========================================================
    // LOGIKA KELOLA TIM
    // ==========================================================

    const modalEditMemberEl = document.getElementById("editMemberModal");
    let modalEditMember = null;
    if (modalEditMemberEl) {
        modalEditMember = new bootstrap.Modal(modalEditMemberEl);
    }

    function loadMembers() {
        const listContainer = document.getElementById("teamMembersList");
        const countBadge = document.getElementById("memberCount");

        axios
            .get(`/api/projek/${projectId}/member`)
            .then((res) => {
                const members = res.data;
                if (countBadge) countBadge.textContent = members.length;

                if (members.length === 0) {
                    listContainer.innerHTML = `<div class="text-center p-4 text-muted small">No team members assigned yet.</div>`;
                    return;
                }

                let html = "";
                members.forEach((m) => {
                    const nameParts = m.user.name.split(" ");
                    let initials = nameParts[0].charAt(0);
                    if (nameParts.length > 1)
                        initials += nameParts[1].charAt(0);
                    initials = initials.toUpperCase();

                    // Only show remove button if user is Ketua
                    const removeButton =
                        currentUserRole === "Ketua" ||
                        userAccountRole === "Admin"
                            ? `<button class="btn btn-sm btn-link text-danger btn-remove-member" 
                                    data-id="${m.id}" 
                                    title="Remove Member"
                                    onclick="event.stopPropagation(); removeMember(${m.id})">
                                <i class="bi bi-x-circle-fill fs-5"></i>
                            </button>`
                            : "";
                    const onClickEdit =
                        currentUserRole === "Ketua" ||
                        userAccountRole === "Admin"
                            ? `openEditMemberModal(${m.id}, '${m.user.name}', '${m.role}')"`
                            : "";

                    html += `
                        <div class="list-group-item member-item d-flex justify-content-between align-items-center py-3 px-3" 
                             style="cursor: pointer;"
                             onclick="${onClickEdit}">
                            
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle me-3">${initials}</div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">${m.user.name}</h6>
                                    <small class="text-muted"><i class="bi bi-briefcase me-1"></i>${m.role}</small>
                                </div>
                            </div>
                            
                            ${removeButton}
                        </div>
                    `;
                });
                listContainer.innerHTML = html;
            })
            .catch((err) => {
                if (listContainer)
                    listContainer.innerHTML = `<div class="text-center p-3 text-danger">Failed to load members.</div>`;
            });
    }

    window.openEditMemberModal = function (id, name, role) {
        document.getElementById("editMemberId").value = id;
        document.getElementById("editMemberName").value = name;
        document.getElementById("editMemberRole").value = role;

        if (modalEditMember) modalEditMember.show();
    };

    window.removeMember = function (memberId) {
        Swal.fire({
            title: "Remove Member?",
            text: "Are you sure you want to remove this user?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Yes, remove",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/projek/${projectId}/member/${memberId}`)
                    .then(() => {
                        Swal.fire({
                            icon: "success",
                            title: "Member removed",
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 2000,
                        });
                        loadMembers();
                    })
                    .catch((err) => {
                        const errorMessage =
                            err.response?.data?.message ||
                            "Failed to remove member";
                        const errorIcon =
                            err.response?.status === 422 ? "warning" : "error";
                        Swal.fire({
                            title:
                                errorIcon === "warning"
                                    ? "Cannot Remove Member"
                                    : "Error",
                            text: errorMessage,
                            icon: errorIcon,
                        });
                    });
            }
        });
    };

    // Handle Submit Form Edit Member
    const formEditMember = document.getElementById("formEditMember");
    if (formEditMember) {
        formEditMember.addEventListener("submit", function (e) {
            e.preventDefault();
            const memberId = document.getElementById("editMemberId").value;
            const newRole = document.getElementById("editMemberRole").value;

            axios
                .put(`/api/projek/${projectId}/member/${memberId}`, {
                    role: newRole,
                })
                .then(() => {
                    if (modalEditMember) modalEditMember.hide();
                    Swal.fire({
                        icon: "success",
                        title: "Role Updated",
                        showConfirmButton: false,
                        timer: 1500,
                        toast: true,
                        position: "top-end",
                    });
                    loadMembers();
                })
                .catch((err) =>
                    Swal.fire(
                        "Error",
                        err.response?.data?.message || "Failed to update role",
                        "error",
                    ),
                );
        });
    }

    let availableUsers = [];
    let selectedUserId = null;

    const modalAddMemberEl = document.getElementById("addMemberModal");
    const modalAddMember = modalAddMemberEl
        ? new bootstrap.Modal(modalAddMemberEl)
        : null;

    const userListContainer = document.getElementById("userSelectionList");
    const searchInput = document.getElementById("searchUser");
    const inputRole = document.getElementById("inputRole");
    const btnSubmitMember = document.getElementById("btnSubmitAddMember");
    const btnSearchTrigger = document.getElementById("btnSearchTrigger");
    const btnAddTeamMember = document.getElementById("btnAddTeamMember");

    // Handle Add Team Member button click
    if (btnAddTeamMember) {
        btnAddTeamMember.addEventListener("click", function (e) {
            e.preventDefault();
            if (modalAddMember) {
                modalAddMember.show();
            }
        });
    }

    window.selectUserItem = function (id) {
        selectedUserId = id;
        const keyword = searchInput.value.toLowerCase();
        const currentList = availableUsers.filter((u) =>
            u.name.toLowerCase().includes(keyword),
        );
        renderUserList(currentList);
        checkSubmitButton();
    };

    function renderUserList(users) {
        if (users.length === 0) {
            userListContainer.innerHTML =
                '<div class="text-center py-4 text-muted">No users found.</div>';
            return;
        }
        let html = "";
        users.forEach((u) => {
            const nameParts = u.name.split(" ");
            let initials = nameParts[0].charAt(0);
            if (nameParts.length > 1) initials += nameParts[1].charAt(0);
            initials = initials.toUpperCase();

            const isSelected = u.id == selectedUserId ? "selected" : "";
            const btnClass =
                u.id == selectedUserId ? "btn-success" : "btn-select-user";
            const btnContent =
                u.id == selectedUserId
                    ? '<i class="bi bi-check-lg"></i>'
                    : "+ Add Member";
            const pointerEvents =
                u.id == selectedUserId ? "pointer-events: none;" : "";

            html += `
                <div class="user-select-item ${isSelected}" onclick="selectUserItem(${u.id})">
                    <div class="d-flex align-items-center">
                        <div class="user-avatar-dark">${initials}</div>
                        <div>
                            <div class="fw-semibold text-dark">${u.name}</div>
                            <small class="text-muted" style="font-size:0.75rem">${u.email}</small>
                        </div>
                    </div>
                    <button class="${btnClass}" style="border:none; border-radius:6px; padding:5px 12px; font-size:0.8rem; ${pointerEvents}">
                        ${btnContent}
                    </button>
                </div>
            `;
        });
        userListContainer.innerHTML = html;
    }

    function checkSubmitButton() {
        if (selectedUserId && inputRole.value.trim() !== "") {
            btnSubmitMember.disabled = false;
        } else {
            btnSubmitMember.disabled = true;
        }
    }

    function executeSearch() {
        const keyword = searchInput.value.toLowerCase();
        const filteredUsers = availableUsers.filter((u) =>
            u.name.toLowerCase().includes(keyword),
        );
        renderUserList(filteredUsers);
    }

    if (modalAddMemberEl) {
        modalAddMemberEl.addEventListener("show.bs.modal", function () {
            selectedUserId = null;
            inputRole.value = "";
            searchInput.value = "";
            btnSubmitMember.disabled = true;
            document.getElementById("btnSubmitText").style.display = "inline";
            document.getElementById("btnSubmitLoader").style.display = "none";
            userListContainer.innerHTML = `<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm mb-2"></div><div>Loading users...</div></div>`;

            axios
                .get(`/api/users?role=User&exclude_project=${projectId}`)
                .then((res) => {
                    availableUsers = res.data;
                    renderUserList(availableUsers);
                })
                .catch(() => {
                    userListContainer.innerHTML =
                        '<div class="text-center py-4 text-danger">Error loading users.</div>';
                });
        });

        if (btnSearchTrigger)
            btnSearchTrigger.addEventListener("click", executeSearch);

        if (searchInput) {
            searchInput.addEventListener("keypress", function (e) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    executeSearch();
                }
            });
            searchInput.addEventListener("input", function () {
                if (this.value === "") renderUserList(availableUsers);
            });
        }

        inputRole.addEventListener("input", checkSubmitButton);

        btnSubmitMember.addEventListener("click", function () {
            const role = inputRole.value;
            if (!selectedUserId || !role) return;

            const payload = {
                user_id: selectedUserId,
                role: role,
                pjk_id: projectId,
            };

            document.getElementById("btnSubmitText").style.display = "none";
            document.getElementById("btnSubmitLoader").style.display =
                "inline-block";
            btnSubmitMember.disabled = true;

            axios
                .post(`/api/projek/${projectId}/member`, payload)
                .then(() => {
                    if (modalAddMember) modalAddMember.hide();
                    Swal.fire({
                        icon: "success",
                        title: "Member Added",
                        showConfirmButton: false,
                        timer: 1500,
                        toast: true,
                        position: "top-end",
                    });
                    loadMembers();
                })
                .catch((err) => {
                    Swal.fire(
                        "Error",
                        err.response?.data?.message || "Failed",
                        "error",
                    );
                    document.getElementById("btnSubmitText").style.display =
                        "inline";
                    document.getElementById("btnSubmitLoader").style.display =
                        "none";
                    btnSubmitMember.disabled = false;
                });
        });
    }
});
