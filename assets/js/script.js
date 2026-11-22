/**
 * assets/js/script.js
 * Client-side interactions, sidebar toggle, auto-hide alerts,
 * form validation, image upload preview, and delete modal bindings.
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const toggleBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');

    function openSidebar() {
        if (sidebar) sidebar.classList.add('show');
        if (backdrop) backdrop.classList.add('show');
    }

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (backdrop) backdrop.classList.remove('show');
    }

    if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (backdrop) backdrop.addEventListener('click', closeSidebar);

    // 2. Auto-hide Flash Alerts after 4 seconds
    const alerts = document.querySelectorAll('.auto-hide-alert');
    alerts.forEach(function (alertEl) {
        setTimeout(function () {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
                bsAlert.close();
            } else {
                alertEl.style.display = 'none';
            }
        }, 4000);
    });

    // 3. Bootstrap Client-Side Validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // 4. Student Profile Photo Validation & Live Preview
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    const photoError = document.getElementById('photoError');

    if (photoInput) {
        photoInput.addEventListener('change', function () {
            if (photoError) photoError.textContent = '';
            const file = this.files && this.files[0];
            if (!file) return;

            const allowedTypes = ['image/jpeg', 'image/png'];
            const maxSize = 2 * 1024 * 1024; // 2 MB

            if (!allowedTypes.includes(file.type)) {
                if (photoError) photoError.textContent = 'Only JPG and PNG images are allowed.';
                this.value = '';
                return;
            }

            if (file.size > maxSize) {
                if (photoError) photoError.textContent = 'Image size must not exceed 2 MB.';
                this.value = '';
                return;
            }

            if (photoPreview) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    photoPreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 5. Generic Bootstrap Delete Confirmation Modal
    const deleteModal = document.getElementById('deleteConfirmModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            const recordId = button.getAttribute('data-id');
            const recordName = button.getAttribute('data-name') || 'this record';
            const deleteAction = button.getAttribute('data-action');

            const idInput = deleteModal.querySelector('#deleteRecordId');
            const nameSpan = deleteModal.querySelector('#deleteRecordName');
            const deleteForm = deleteModal.querySelector('#deleteConfirmForm');

            if (idInput) idInput.value = recordId;
            if (nameSpan) nameSpan.textContent = recordName;
            if (deleteForm && deleteAction) deleteForm.action = deleteAction;
        });
    }

    // 6. Attendance Quick Mark All Present / Absent
    const markAllPresentBtn = document.getElementById('markAllPresent');
    const markAllAbsentBtn = document.getElementById('markAllAbsent');

    if (markAllPresentBtn) {
        markAllPresentBtn.addEventListener('click', function () {
            document.querySelectorAll('input[type="radio"][value="Present"]').forEach(function (radio) {
                radio.checked = true;
            });
        });
    }

    if (markAllAbsentBtn) {
        markAllAbsentBtn.addEventListener('click', function () {
            document.querySelectorAll('input[type="radio"][value="Absent"]').forEach(function (radio) {
                radio.checked = true;
            });
        });
    }

    // 7. Live Marks Percentage & Grade Calculator Preview
    const maxMarksInput = document.getElementById('maxMarksInput');
    const obtainedMarksInput = document.getElementById('obtainedMarksInput');
    const previewPercentage = document.getElementById('previewPercentage');
    const previewGrade = document.getElementById('previewGrade');
    const previewStatus = document.getElementById('previewStatus');

    function updateMarksPreview() {
        if (!maxMarksInput || !obtainedMarksInput) return;
        const maxVal = parseFloat(maxMarksInput.value) || 0;
        const obtVal = parseFloat(obtainedMarksInput.value);

        if (maxVal <= 0 || isNaN(obtVal) || obtVal < 0 || obtVal > maxVal) {
            if (previewPercentage) previewPercentage.textContent = '--%';
            if (previewGrade) previewGrade.textContent = '--';
            if (previewStatus) {
                previewStatus.textContent = '--';
                previewStatus.className = 'badge bg-secondary';
            }
            return;
        }

        const pct = (obtVal / maxVal) * 100;
        let grade = 'F';
        if (pct >= 90) grade = 'A+';
        else if (pct >= 80) grade = 'A';
        else if (pct >= 70) grade = 'B+';
        else if (pct >= 60) grade = 'B';
        else if (pct >= 50) grade = 'C';
        else if (pct >= 40) grade = 'D';

        const passed = pct >= 40;
        if (previewPercentage) previewPercentage.textContent = pct.toFixed(2) + '%';
        if (previewGrade) previewGrade.textContent = grade;
        if (previewStatus) {
            previewStatus.textContent = passed ? 'Pass' : 'Fail';
            previewStatus.className = passed ? 'badge bg-success' : 'badge bg-danger';
        }
    }

    if (maxMarksInput && obtainedMarksInput) {
        maxMarksInput.addEventListener('input', updateMarksPreview);
        obtainedMarksInput.addEventListener('input', updateMarksPreview);
        updateMarksPreview();
    }
});
