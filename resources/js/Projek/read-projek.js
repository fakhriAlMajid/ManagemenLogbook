import "../bootstrap";
import { Modal } from "bootstrap";

document.addEventListener("DOMContentLoaded", () => {
    // DOM Elements - grouped and destructured
    const elements = {
        container: document.getElementById("projekContainer"),
        search: document.getElementById("search"),
        status: document.getElementById("status"),
        searchBtn: document.getElementById("searchBtn"),

        errorMsg: document.getElementById("errorMsg"),
        logoutBtn: document.getElementById("logoutBtn"),
        navbarSearchInput: document.getElementById("navbarSearchInput"),
        filterBtn: document.getElementById("filterBtn"),
        filterDropdown: document.getElementById("filterDropdown"),
        sortBtn: document.getElementById("sortBtn"),
        kategoriBtn: document.getElementById("kategoriBtn"),
        kategoriFilterBtn: document.getElementById("kategoriFilterBtn"),
        kategoriFilterDropdown: document.getElementById(
            "kategoriFilterDropdown",
        ),
        kategoriFilterBtnText: document.getElementById("kategoriFilterBtnText"),
        filterCard: document.getElementById("filterCard"),
        addForm: document.getElementById("formAddProjek"),
        modalElement: document.getElementById("addProjekModal"),
        tglMulai: document.getElementById("tgl_mulai"),
        tglSelesai: document.getElementById("tgl_selesai"),
        nama: document.getElementById("nama"),
        kategori: document.getElementById("kategori"),
        pic: document.getElementById("pic"),
        deskripsi: document.getElementById("deskripsi"),
        btnSubmit: document.getElementById("btnSubmit"),
        btnText: document.getElementById("btnText"),
        btnLoader: document.getElementById("btnLoader"),
        modalAlert: document.getElementById("modalAlert"),
    };

    const addModal = elements.modalElement
        ? new Modal(elements.modalElement)
        : null;
    const apiBase = "/api";

    const userData = JSON.parse(localStorage.getItem("user_data") || "{}");
    const currentUserRole = userData.role;

    if (currentUserRole !== "Admin") {
        elements.kategoriBtn?.classList.add("d-none");
    }

    // State
    const state = {
        sortOrder: "desc",
        sortBy: "progress",
        statusFilter: "",
        kategoriFilter: "",
    };

    // Utility functions
    const formatDate = (date) => date.toISOString().split("T")[0];
    const getToday = () => {
        const d = new Date();
        d.setHours(0, 0, 0, 0);
        return d;
    };
    const showAlert = (type, message) => {
        if (elements.modalAlert) {
            elements.modalAlert.innerHTML = `<div class="alert alert-${type} small py-2">${message}</div>`;
        }
    };
    const clearAlert = () => {
        if (elements.modalAlert) elements.modalAlert.innerHTML = "";
    };

    // Auth
    const setAuthToken = (token) => {
        const { logoutBtn } = elements;
        if (!token) {
            delete axios.defaults.headers.common.Authorization;
            localStorage.removeItem("api_token");
            logoutBtn?.classList.add("d-none");
            return;
        }
        axios.defaults.headers.common.Authorization = `Bearer ${token}`;
        localStorage.setItem("api_token", token);
        logoutBtn?.classList.remove("d-none");
    };

    // Load categories
    const loadCategories = async () => {
        try {
            const { data: response } = await axios.get(`${apiBase}/kategori`);
            const categories = Array.isArray(response)
                ? response
                : response.data || [];

            if (elements.kategori) {
                const selectElement = elements.kategori;
                const currentValue = selectElement.value;

                selectElement.innerHTML =
                    '<option value="">-- Select Category --</option>';

                categories.forEach((cat) => {
                    if (cat.ktg_is_active === 0) return;
                    const option = document.createElement("option");
                    option.value = cat.id || cat.ktg_id;
                    option.textContent = cat.nama || cat.ktg_nama;
                    selectElement.appendChild(option);
                });

                if (currentValue) selectElement.value = currentValue;
            }
        } catch (err) {
            console.error("Failed to load categories:", err);
        }
    };

    // Load categories for navbar filter
    const loadCategoriesForNavbar = async () => {
        try {
            const { data: response } = await axios.get(`${apiBase}/kategori`);
            const categories = Array.isArray(response)
                ? response
                : response.data || [];

            const { kategoriFilterDropdown } = elements;
            if (kategoriFilterDropdown) {
                // Keep the "All Categories" option
                kategoriFilterDropdown.innerHTML =
                    '<div class="dropdown-item active" data-value="">All Categories</div>';

                categories.forEach((cat) => {
                    if (cat.ktg_is_active === 0) return;
                    const item = document.createElement("div");
                    item.className = "dropdown-item";
                    item.setAttribute("data-value", cat.id || cat.ktg_id);
                    item.textContent = cat.nama || cat.ktg_nama;
                    kategoriFilterDropdown.appendChild(item);
                });

                // Re-attach event listeners to newly created items
                attachKategoriFilterListeners();
            }
        } catch (err) {
            console.error("Failed to load categories for navbar:", err);
        }
    };

    // Attach kategori filter listeners with event delegation
    const attachKategoriFilterListeners = () => {
        const {
            kategoriFilterDropdown,
            kategoriFilterBtnText,
            kategoriFilterBtn,
        } = elements;
        if (!kategoriFilterDropdown) return;

        kategoriFilterDropdown
            .querySelectorAll(".dropdown-item")
            .forEach((item) => {
                item.addEventListener("click", () => {
                    kategoriFilterDropdown
                        .querySelectorAll(".dropdown-item")
                        .forEach((i) => i.classList.remove("active"));
                    item.classList.add("active");
                    state.kategoriFilter = item.getAttribute("data-value");

                    // Update button text
                    const buttonText = item.textContent || "All Categories";
                    if (kategoriFilterBtnText) {
                        kategoriFilterBtnText.textContent = buttonText;
                    }

                    // Close dropdown
                    kategoriFilterDropdown.classList.add("d-none");
                    kategoriFilterBtn?.classList.remove("active");

                    loadProjects();
                });
            });
    };

    // Date validation
    const initDateValidation = () => {
        const todayStr = formatDate(getToday());
        elements.tglMulai?.setAttribute("min", todayStr);

        if (elements.tglSelesai) {
            elements.tglSelesai.disabled = true;
            elements.tglSelesai.placeholder =
                "Isi tanggal mulai terlebih dahulu";
        }
    };

    const handleTglMulaiChange = ({ target: { value } }) => {
        const { tglSelesai } = elements;
        if (!value) {
            if (tglSelesai) {
                tglSelesai.disabled = true;
                tglSelesai.value = "";
                tglSelesai.removeAttribute("min");
            }
            return;
        }

        const minDate = new Date(value);
        minDate.setDate(minDate.getDate() + 1);
        const minStr = formatDate(minDate);

        if (tglSelesai) {
            tglSelesai.disabled = false;
            tglSelesai.setAttribute("min", minStr);
            tglSelesai.removeAttribute("placeholder");

            if (
                tglSelesai.value &&
                new Date(tglSelesai.value) <= new Date(value)
            ) {
                tglSelesai.value = "";
            }
        }
    };

    // Form validation
    const validateForm = (e) => {
        const tglMulai = elements.tglMulai?.value;
        const tglSelesai = elements.tglSelesai?.value;
        const today = getToday();

        if (!tglMulai) {
            e.preventDefault();
            return showAlert("warning", "Tanggal mulai harus diisi!");
        }

        const mulaiDate = new Date(tglMulai);
        if (mulaiDate < today) {
            e.preventDefault();
            return showAlert(
                "warning",
                "Tanggal mulai tidak boleh kurang dari hari ini!",
            );
        }

        if (!tglSelesai) {
            e.preventDefault();
            return showAlert("warning", "Tanggal selesai harus diisi!");
        }

        const selesaiDate = new Date(tglSelesai);
        if (selesaiDate <= mulaiDate) {
            e.preventDefault();
            return showAlert(
                "warning",
                "Tanggal selesai harus lebih besar dari tanggal mulai!",
            );
        }

        if (selesaiDate <= today) {
            e.preventDefault();
            return showAlert(
                "warning",
                "Tanggal selesai tidak boleh kurang dari atau sama dengan hari ini!",
            );
        }
    };

    // Project loading
    const sortProjects = (projects) => {
        const { sortBy, sortOrder } = state;
        const multiplier = sortOrder === "asc" ? 1 : -1;

        return [...projects].sort((a, b) => {
            let valA, valB;
            switch (sortBy) {
                case "name":
                    valA = (a.nama || "").toLowerCase();
                    valB = (b.nama || "").toLowerCase();
                    break;
                case "date":
                    valA = new Date(a.tanggal_mulai || 0).getTime();
                    valB = new Date(b.tanggal_mulai || 0).getTime();
                    break;
                default:
                    valA = parseFloat(a.persentase_progress || 0);
                    valB = parseFloat(b.persentase_progress || 0);
            }
            return (valA > valB ? 1 : -1) * multiplier;
        });
    };

    const calculateProgressFromBreakdown = async (project) => {
        try {
            const { data: breakdown } = await axios.get(
                `${apiBase}/projek/${project.id}/breakdown`,
            );
            const totalProgress = Array.isArray(breakdown)
                ? breakdown
                      .filter((item) => item.tipe_item === "Kegiatan")
                      .reduce(
                          (sum, item) =>
                              sum + (parseFloat(item.kontribusi_total) || 0),
                          0,
                      )
                : 0;
            project.persentase_progress = Math.round(totalProgress * 100) / 100;
        } catch (err) {
            console.warn(
                `Failed to calculate progress for project ${project.id}:`,
                err,
            );
            // Keep existing progress from API if breakdown fails
            project.persentase_progress ||= 0;
        }
    };

    const renderProjectCards = async (projects) => {
        const { container } = elements;
        try {
            const { data } = await axios.post("/projek/render-cards", {
                projects,
            });
            container.innerHTML = data.html;
        } catch {
            container.innerHTML = projects
                .map(
                    (p) => `
                <div class="col-12 col-sm-6 col-md-4">
                    <div class="card p-3 h-100 shadow-sm border-0" onclick="showProjectDetail(${p.id || p.pjk_id})">
                        <h5 class="mb-1 fw-bold text-primary">${p.nama || p.pjk_nama || "No Name"}</h5>
                        <p class="mb-1 text-muted small">
                            <i class="bi bi-tags me-1"></i>
                            ${p.kategori_nama || p.pjk_kategori_nama || "-"}
                        </p>
                        <p class="mb-1 text-muted small">
                            <i class="bi bi-calendar-event me-1"></i>
                            ${p.tanggal_mulai || p.pjk_tanggal_mulai || "-"} to 
                            ${p.tanggal_selesai || p.pjk_tanggal_selesai || "-"}
                        </p>
                        <p class="mb-2 text-muted small">
                            <i class="bi bi-person-badge me-1"></i> PIC: 
                            <span class="fw-bold">${p.pic || p.pjk_pic || "-"}</span>
                        </p>
                        <div class="progress mt-auto" style="height: 8px;">
                            <div class="progress-bar" style="width: ${p.persentase_progress ?? p.pjk_persentasi_progress ?? 0}%"></div>
                        </div>
                        <p class="mb-0 mt-1 small">Progress: ${p.persentase_progress ?? p.pjk_persentasi_progress ?? 0}%</p>
                    </div>
                </div>
            `,
                )
                .join("");
        }
    };

    const loadProjects = async (searchQuery = "") => {
        const { container, errorMsg, navbarSearchInput } = elements;
        if (errorMsg) errorMsg.textContent = "";
        container.innerHTML =
            '<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div></div>';

        const params = {};
        const finalSearch = searchQuery || navbarSearchInput?.value || "";
        if (finalSearch) params.search = finalSearch;
        if (state.statusFilter) params.status = state.statusFilter;
        if (state.kategoriFilter) params.kategori_id = state.kategoriFilter;

        try {
            const { data: responseData } = await axios.get(
                `${apiBase}/projek`,
                { params },
            );
            let data = Array.isArray(responseData)
                ? responseData
                : responseData.data || [];

            // Calculate progress from breakdown for each project
            await Promise.all(data.map(calculateProgressFromBreakdown));
            data = sortProjects(data);

            if (!data.length) {
                container.innerHTML =
                    '<div class="col-12 text-center text-muted py-4">No projects found.</div>';
                return;
            }

            await renderProjectCards(data);
        } catch (err) {
            console.error(err);
            const msg =
                err.response?.status === 401
                    ? 'Session expired. Please <a href="/login">Login again</a>.'
                    : `Failed to load data: ${err.message || "Unknown error"}`;
            if (errorMsg) errorMsg.innerHTML = msg;
        }
    };

    // Event handlers
    const toggleSort = () => {
        state.sortOrder = state.sortOrder === "asc" ? "desc" : "asc";
        const icon = elements.sortBtn?.querySelector("i");
        if (icon) {
            icon.className =
                state.sortOrder === "asc"
                    ? "bi bi-sort-up"
                    : "bi bi-sort-down-alt";
        }
        loadProjects();
    };

    const handleAddSubmit = async (e) => {
        e.preventDefault();
        const { btnSubmit, btnText, btnLoader, addForm } = elements;

        btnSubmit.disabled = true;
        btnText?.classList.add("d-none");
        btnLoader?.classList.remove("d-none");

        const payload = {
            nama: elements.nama?.value,
            kategori_id: elements.kategori?.value,
            pic: elements.pic?.value,
            deskripsi: elements.deskripsi?.value,
            tgl_mulai: elements.tglMulai?.value,
            tgl_selesai: elements.tglSelesai?.value,
        };

        try {
            await axios.post(`${apiBase}/projek`, payload);
            showAlert("success", "Project successfully added!");
            setTimeout(() => {
                addModal?.hide();
                addForm?.reset();
                clearAlert();
                loadProjects();
            }, 1000);
        } catch (error) {
            showAlert(
                "danger",
                error.response?.data?.message || "Failed to save data.",
            );
        } finally {
            btnSubmit.disabled = false;
            btnText?.classList.remove("d-none");
            btnLoader?.classList.add("d-none");
        }
    };

    // Event listeners
    elements.tglMulai?.addEventListener("change", handleTglMulaiChange);
    elements.modalElement?.addEventListener("show.bs.modal", () => {
        initDateValidation();
        loadCategories();
    });
    elements.addForm?.addEventListener("submit", validateForm, true); // Run first
    elements.addForm?.addEventListener("submit", handleAddSubmit);

    // Kategori filter button toggle
    elements.kategoriFilterBtn?.addEventListener("click", () => {
        elements.kategoriFilterBtn.classList.toggle("active");
        elements.kategoriFilterDropdown?.classList.toggle("d-none");
    });

    elements.sortBtn?.addEventListener("click", () => {
        elements.sortBtn.classList.toggle("active");
        toggleSort();
    });
    elements.status?.addEventListener("change", () => loadProjects());
    elements.logoutBtn?.addEventListener("click", () => {
        setAuthToken(null);
        window.location.href = "/";
    });

    // Global events
    window.addEventListener("navbar-filter", (e) => {
        state.statusFilter = e.detail.status;
        loadProjects();
    });
    window.addEventListener("navbar-search", (e) =>
        loadProjects(e.detail.searchValue),
    );
    window.showProjectDetail = (id) => {
        window.location.href = `/projek/${id}/dashboard`;
    };

    // Init
    const savedToken = localStorage.getItem("api_token");
    if (savedToken) {
        setAuthToken(savedToken);
        // Load categories first, then load projects
        loadCategoriesForNavbar().then(() => {
            loadProjects();
        });
    } else if (elements.errorMsg) {
        elements.errorMsg.innerHTML =
            'You are not logged in. <a href="/login">Login here</a>';
    }
});
