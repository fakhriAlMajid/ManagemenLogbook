import axios from "axios";

const apiToken = localStorage.getItem("api_token");
axios.defaults.headers.common["Authorization"] = `Bearer ${apiToken}`;
axios.defaults.headers.common["Accept"] = "application/json";

let usedTaskIds = [];

const updateUsedTaskIds = () => {
    const projectId = window.location.pathname.split("/")[2];
    return axios
        .get(`/api/projek/${projectId}/logbook`)
        .then((res) => {
            const existingLogbooks = res.data.data || [];
            usedTaskIds = existingLogbooks.map((log) =>
                parseInt(log.id_tugas || log.tgs_id),
            );
            console.log("Updated Used Task IDs:", usedTaskIds);
            return usedTaskIds;
        })
        .catch((err) => {
            console.error("Failed to load logbook entries:", err);
            return [];
        });
};

document.addEventListener("DOMContentLoaded", () => {
    const formAdd = document.getElementById("formAddLogbook");

    if (formAdd) {
        formAdd.addEventListener("submit", function (e) {
            e.preventDefault();

            const tgsId = document.getElementById("tgs_id").value;
            const tgsSelect = document.getElementById("tgs_id");
            const selectedOption = tgsSelect.options[tgsSelect.selectedIndex];
            const progressValue = document.getElementById("lbk_progress").value;

            if (!tgsId) {
                Swal.fire(
                    "Validation Error",
                    "Please select a task first",
                    "warning",
                );
                return;
            }

            // Check if selected option is disabled (already in logbook)
            if (selectedOption.disabled) {
                Swal.fire(
                    "Task Already Logged",
                    "This task already has a logbook entry. Please select a different task.",
                    "warning",
                );
                return;
            }

            // Additional check: verify task ID is not in usedTaskIds
            const tgsIdInt = parseInt(tgsId);
            if (usedTaskIds.includes(tgsIdInt)) {
                Swal.fire(
                    "Task Already Logged",
                    "This task already has a logbook entry. Please select a different task.",
                    "warning",
                );
                return;
            }

            const data = {
                tgs_id: tgsId,
                tanggal: document.getElementById("lbk_tanggal").value,
                deskripsi: document.getElementById("lbk_deskripsi").value,
                komentar: document.getElementById("lbk_komentar").value || "",
                progress: progressValue || 0,
            };

            axios
                .post("/api/logbook", data)
                .then(() => {
                    Swal.fire({
                        icon: "success",
                        title: "Success!",
                        showConfirmButton: false,
                        timer: 1500,
                    }).then(() => location.reload());
                })
                .catch((err) => {
                    Swal.fire(
                        "Failed",
                        err.response?.data?.message || "An error occurred",
                        "error",
                    );
                });
        });
    }

    // Reset form when modal is shown
    const modalElement = document.getElementById("modalAddLogbook");
    if (modalElement) {
        modalElement.addEventListener("show.bs.modal", function () {
            const form = document.getElementById("formAddLogbook");
            if (form) {
                form.reset();
            }

            // Update used task IDs and disable options
            updateUsedTaskIds().then(() => {
                const tgsSelect = document.getElementById("tgs_id");
                const options = tgsSelect.querySelectorAll("option");

                options.forEach((option) => {
                    const tgsId = parseInt(option.value);
                    const isUsed = usedTaskIds.includes(tgsId);

                    if (isUsed) {
                        option.disabled = true;
                        // Ganti text jika belum ada [ALREADY LOGGED]
                        if (!option.textContent.includes("[ALREADY LOGGED]")) {
                            option.textContent = `[ALREADY LOGGED] ${option.textContent}`;
                        }
                    } else {
                        option.disabled = false;
                        // Remove [ALREADY LOGGED] prefix jika ada
                        if (option.textContent.includes("[ALREADY LOGGED]")) {
                            option.textContent = option.textContent.replace(
                                "[ALREADY LOGGED] ",
                                "",
                            );
                        }
                    }
                });
            });
        });
    }

    // Handle detail modal
    const detailModal = document.getElementById("modalDetailLogbook");
    if (detailModal) {
        detailModal.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;

            // Format tanggal helper
            const formatDate = (dateStr) => {
                const date = new Date(dateStr);
                return date.toLocaleDateString("id-ID", {
                    year: "numeric",
                    month: "2-digit",
                    day: "2-digit",
                });
            };

            // Get data from button attributes
            const tanggal = button.getAttribute("data-tanggal");
            const task = button.getAttribute("data-task");
            const kode = button.getAttribute("data-kode");
            const deskripsi = button.getAttribute("data-deskripsi");
            const progress = button.getAttribute("data-progress") || 0;
            const komentar = button.getAttribute("data-komentar") || "";
            const pic = button.getAttribute("data-pic");
            const start = button.getAttribute("data-start");
            const end = button.getAttribute("data-end");

            // Update modal content
            document.getElementById("detail-tanggal").textContent =
                formatDate(tanggal);
            document.getElementById("detail-task").textContent = task;
            document.getElementById("detail-kode").textContent = `[${kode}]`;
            document.getElementById("detail-deskripsi").textContent = deskripsi;
            document.getElementById("detail-pic").innerHTML =
                `<span class="badge bg-light text-dark border">${pic}</span>`;
            document.getElementById("detail-start").textContent =
                formatDate(start);
            document.getElementById("detail-end").textContent = formatDate(end);

            // Update progress badge
            const progressBadge = document.getElementById("detail-progress");
            progressBadge.textContent = `${progress}%`;
            progressBadge.className =
                progress >= 100 ? "badge bg-success" : "badge bg-secondary";

            // Update komentar
            const komentarEl = document.getElementById("detail-komentar");
            if (komentar && komentar.trim()) {
                komentarEl.textContent = komentar;
            } else {
                komentarEl.innerHTML = '<em class="text-muted">-</em>';
            }

            // Store lbk_id in hidden field for edit comment form
            const lbkId = button.getAttribute("data-lbk-id");
            document.getElementById("lbk_id_edit").value = lbkId;
            document.getElementById("komentarEdit").value = komentar || "";
        });
    }

    // Handle edit comment form submission
    const formEditComment = document.getElementById("formEditComment");
    if (formEditComment) {
        formEditComment.addEventListener("submit", function (e) {
            e.preventDefault();

            const lbkId = document.getElementById("lbk_id_edit").value;
            const komentar =
                document.getElementById("komentarEdit").value || "";

            axios
                .put(`/api/logbook/${lbkId}`, { lbk_komentar: komentar })
                .then(() => {
                    Swal.fire({
                        icon: "success",
                        title: "Success!",
                        text: "Comment saved successfully",
                        showConfirmButton: false,
                        timer: 1500,
                    }).then(() => location.reload());
                })
                .catch((err) => {
                    Swal.fire(
                        "Failed",
                        err.response?.data?.message || "An error occurred",
                        "error",
                    );
                });
        });
    }
});
