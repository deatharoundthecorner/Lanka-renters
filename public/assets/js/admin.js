// Lanka Renters Admin Dashboard - Vanilla JavaScript Application Logic

document.addEventListener('DOMContentLoaded', function () {
    initMobileSidebar();
    initTableFilters();
    initSettlementCalculator();
    initCharts();
    initSegmentedTabs();
});

/* Mobile Sidebar Drawer Toggle */
function initMobileSidebar() {
    const toggleBtn = document.getElementById('mobileNavToggle');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const sidebar = document.getElementById('adminSidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.add('mobile-open');
        });
    }

    if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', function () {
            sidebar.classList.remove('mobile-open');
        });
    }
}

/* Toast Notifications */
function showToast(message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let iconSvg = type === 'success' 
        ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" color="#16A34A"><polyline points="20 6 9 17 4 12"></polyline></svg>`
        : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" color="#DC2626"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>`;

    toast.innerHTML = `${iconSvg} <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(30px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

/* Client-Side Searching & Filtering */
function initTableFilters() {
    const searchInput = document.getElementById('filterSearch');
    const districtSelect = document.getElementById('filterDistrict');
    const typeSelect = document.getElementById('filterType');
    const incidentTypeSelect = document.getElementById('filterIncidentType');
    const statusSelect = document.getElementById('filterStatus');
    const prioritySelect = document.getElementById('filterPriority');
    const resetBtn = document.getElementById('filterResetBtn');

    function applyFilters() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const district = districtSelect ? districtSelect.value.toLowerCase() : '';
        const type = typeSelect ? typeSelect.value.toLowerCase() : '';
        const incidentType = incidentTypeSelect ? incidentTypeSelect.value.toLowerCase() : '';
        const status = statusSelect ? statusSelect.value.toLowerCase() : '';
        const priority = prioritySelect ? prioritySelect.value.toLowerCase() : '';

        // Filter Table Rows
        const tableRows = document.querySelectorAll('.custom-table tbody tr');
        tableRows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const matchesQuery = !query || rowText.includes(query);
            const matchesDistrict = !district || rowText.includes(district);
            const matchesType = !type || rowText.includes(type);
            const matchesStatus = !status || rowText.includes(status);
            const matchesPriority = !priority || rowText.includes(priority);

            if (matchesQuery && matchesDistrict && matchesType && matchesStatus && matchesPriority) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        // Filter Incident Cards
        const incidentCards = document.querySelectorAll('.incident-card');
        incidentCards.forEach(card => {
            const cardText = card.textContent.toLowerCase();
            const matchesQuery = !query || cardText.includes(query);
            const matchesDistrict = !district || cardText.includes(district);
            const matchesIncidentType = !incidentType || cardText.includes(incidentType);

            if (matchesQuery && matchesDistrict && matchesIncidentType) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (districtSelect) districtSelect.addEventListener('change', applyFilters);
    if (typeSelect) typeSelect.addEventListener('change', applyFilters);
    if (incidentTypeSelect) incidentTypeSelect.addEventListener('change', applyFilters);
    if (statusSelect) statusSelect.addEventListener('change', applyFilters);
    if (prioritySelect) prioritySelect.addEventListener('change', applyFilters);

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            if (districtSelect) districtSelect.value = '';
            if (typeSelect) typeSelect.value = '';
            if (incidentTypeSelect) incidentTypeSelect.value = '';
            if (statusSelect) statusSelect.value = '';
            if (prioritySelect) prioritySelect.value = '';
            applyFilters();
            showToast("Filters reset successfully.", "info");
        });
    }
}

/* Modals System */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
    }
}

function openDocModal(docTitle, docType) {
    const titleEl = document.getElementById('docModalTitle');
    const typeEl = document.getElementById('docModalType');
    if (titleEl) titleEl.innerText = docTitle;
    if (typeEl) typeEl.innerText = `Document Format: ${docType} (Verified Document Placeholder)`;
    openModal('docViewerModal');
    showToast(`Documents opened successfully.`, "info");
}

function triggerApprove(targetName, targetId) {
    const textEl = document.getElementById('approveModalText');
    if (textEl) textEl.innerText = `Are you sure you want to approve request for ${targetName} (${targetId})?`;
    window.currentTargetId = targetId;
    openModal('approveModal');
}

function confirmApprove() {
    closeModal('approveModal');
    showToast(`Approved successfully.`, "success");
}

function triggerReject(targetName, targetId) {
    const textEl = document.getElementById('rejectModalText');
    if (textEl) textEl.innerText = `Are you sure you want to reject request for ${targetName} (${targetId})?`;
    window.currentTargetId = targetId;
    openModal('rejectModal');
}

function confirmReject() {
    closeModal('rejectModal');
    showToast(`Request rejected successfully.`, "danger");
}

function triggerSuspend(targetName, targetId) {
    const textEl = document.getElementById('suspendModalText');
    if (textEl) textEl.innerText = `Are you sure you want to suspend customer ${targetName} (${targetId})?`;
    openModal('suspendModal');
}

function confirmSuspend() {
    closeModal('suspendModal');
    showToast(`Customer suspended successfully.`, "danger");
}

function retryEmail(emailId, recipient) {
    if (confirm(`Are you sure you want to retry sending email ${emailId} to ${recipient}?`)) {
        showToast(`Email retry completed for ${emailId}.`, "success");
    }
}

function triggerManualEmail() {
    openModal('manualEmailModal');
}

function sendManualEmail(e) {
    if(e) e.preventDefault();
    closeModal('manualEmailModal');
    showToast(`Email sent successfully.`, "success");
}

/* ANNOUNCEMENTS DYNAMIC LIVE PREVIEW & CRUD */
function updateLivePreview() {
    const title = document.getElementById('annTitle') ? document.getElementById('annTitle').value : '';
    const type = document.getElementById('annType') ? document.getElementById('annType').value : 'Maintenance';
    const audience = document.getElementById('annTarget') ? document.getElementById('annTarget').value : 'All Users';
    const priority = document.getElementById('annPriority') ? document.getElementById('annPriority').value : 'Normal';
    const message = document.getElementById('annMessage') ? document.getElementById('annMessage').value : '';

    const prevTitle = document.getElementById('livePrevTitle');
    const prevBody = document.getElementById('livePrevBody');
    const prevType = document.getElementById('livePrevType');
    const prevPriority = document.getElementById('livePrevPriority');
    const prevAudience = document.getElementById('livePrevAudience');

    if (prevTitle) prevTitle.innerText = title.trim() ? title : 'Scheduled System Maintenance';
    if (prevBody) prevBody.innerText = message.trim() ? message : 'Lanka Renters will temporarily be unavailable for scheduled system maintenance.';
    if (prevType) prevType.innerText = type;
    if (prevPriority) {
        prevPriority.innerText = priority + ' Priority';
        prevPriority.className = `badge badge-${priority.toLowerCase()}`;
    }
    if (prevAudience) prevAudience.innerText = audience;
}

function openCreateAnnouncementModal() {
    const form = document.getElementById('announcementForm');
    if (form) form.reset();
    document.getElementById('annFormId').value = '';
    document.getElementById('annModalHeaderTitle').innerText = 'Create Announcement';
    document.getElementById('annFormSubmitBtn').innerText = 'Create Announcement';
    updateLivePreview();
    openModal('announcementModal');
}

function saveAnnouncement(e) {
    if (e) e.preventDefault();
    const id = document.getElementById('annFormId').value;
    closeModal('announcementModal');

    if (id) {
        showToast("Announcement updated successfully.", "success");
    } else {
        showToast("Announcement created successfully.", "success");
    }
}

function openViewAnnouncement(annId, title, type, audience, priority, status, date, author, msg) {
    document.getElementById('viewAnnId').innerText = annId;
    document.getElementById('viewAnnTitle').innerText = title;
    document.getElementById('viewAnnType').innerText = type;
    document.getElementById('viewAnnAudience').innerText = audience;
    document.getElementById('viewAnnPublish').innerText = date + ', 10:00 AM';
    document.getElementById('viewAnnExpiry').innerText = 'N/A';
    document.getElementById('viewAnnAuthor').innerText = author;
    document.getElementById('viewAnnMessage').innerText = msg;
    openModal('viewAnnouncementModal');
}

function openEditAnnouncement(annId, title, type, audience, priority, status, msg) {
    document.getElementById('annFormId').value = annId;
    document.getElementById('annTitle').value = title;
    document.getElementById('annType').value = type;
    document.getElementById('annTarget').value = audience;
    document.getElementById('annPriority').value = priority;
    document.getElementById('annStatus').value = status;
    document.getElementById('annMessage').value = msg;

    document.getElementById('annModalHeaderTitle').innerText = 'Edit Announcement (' + annId + ')';
    document.getElementById('annFormSubmitBtn').innerText = 'Save Changes';
    updateLivePreview();
    openModal('announcementModal');
}

function openDeleteAnnouncement(annId, title) {
    window.targetDeleteAnnId = annId;
    document.getElementById('deleteAnnTitleText').innerText = `${title} (${annId})`;
    openModal('deleteAnnouncementModal');
}

function confirmDeleteAnnouncement() {
    closeModal('deleteAnnouncementModal');
    showToast("Announcement deleted successfully.", "danger");
}

function togglePublishStatus(annId, currentStatus) {
    if (currentStatus === 'Published') {
        showToast(`Announcement ${annId} unpublished successfully.`, "info");
    } else {
        showToast(`Announcement ${annId} published. Notification delivery queued.`, "success");
    }
}

/* SEGMENTED TAB SWITCHER & DASHBOARD NAVIGATION */
function switchTab(tabName) {
    const pendingSec = document.getElementById('sectionPending');
    const registeredSec = document.getElementById('sectionRegistered');
    const tabPending = document.getElementById('tabPending');
    const tabRegistered = document.getElementById('tabRegistered');

    if (tabName === 'registered') {
        if (pendingSec) pendingSec.style.display = 'none';
        if (registeredSec) registeredSec.style.display = 'block';
        if (tabPending) tabPending.classList.remove('active');
        if (tabRegistered) tabRegistered.classList.add('active');
    } else {
        if (pendingSec) pendingSec.style.display = 'block';
        if (registeredSec) registeredSec.style.display = 'none';
        if (tabPending) tabPending.classList.add('active');
        if (tabRegistered) tabRegistered.classList.remove('active');
    }
}

function initSegmentedTabs() {
    const urlParams = new URLSearchParams(window.location.search);
    const view = urlParams.get('view');
    if (view === 'registered') {
        switchTab('registered');
    } else {
        switchTab('pending');
    }
}

/* Dynamic Settlement Calculator */
function initSettlementCalculator() {
    const grossInput = document.getElementById('calcGrossAmount');
    const rateInput = document.getElementById('calcCommissionRate');
    const commOutput = document.getElementById('calcCommissionAmount');
    const netOutput = document.getElementById('calcOwnerAmount');
    const calcBtn = document.getElementById('calcCalculateBtn');

    function calculate() {
        if (!grossInput || !rateInput) return;
        const gross = parseFloat(grossInput.value) || 0;
        const rate = parseFloat(rateInput.value) || 0;

        const commission = gross * (rate / 100);
        const net = gross - commission;

        if (commOutput) commOutput.innerText = `Rs. ${commission.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
        if (netOutput) netOutput.innerText = `Rs. ${net.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    }

    if (grossInput) grossInput.addEventListener('input', calculate);
    if (rateInput) rateInput.addEventListener('input', calculate);
    if (calcBtn) {
        calcBtn.addEventListener('click', function() {
            calculate();
            showToast("Settlement calculated successfully.", "success");
        });
    }
}

/* CSV Report Exporter */
function downloadCSVReport(reportTitle) {
    const rows = [
        ["Report Title", reportTitle],
        ["Generated Date", new Date().toLocaleDateString()],
        ["Status", "Official Lanka Renters Report"],
        [],
        ["ID", "Name / Item", "District", "Amount (LKR)", "Status"],
        ["REF-101", "Kasun Perera", "Colombo", "45000.00", "Completed"],
        ["REF-102", "Nimal Silva", "Kandy", "75000.00", "Completed"],
        ["REF-103", "Tharindu Fernando", "Galle", "120000.00", "Pending"],
        ["REF-104", "Sanduni Perera", "Kurunegala", "35000.00", "Completed"]
    ];

    let csvContent = "data:text/csv;charset=utf-8," + rows.map(e => e.join(",")).join("\n");
    let encodedUri = encodeURI(csvContent);
    let link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `${reportTitle.toLowerCase().replace(/\s+/g, '_')}_report.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showToast(`Downloaded ${reportTitle} successfully.`, "success");
}

/* Pure JS / SVG Canvas Charts Initialization */
function initCharts() {
    renderRevenueChart();
    renderBookingDonutChart();
    renderRegistrationChart();
}

function renderRevenueChart() {
    const canvas = document.getElementById('revenueChartCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    
    // Monthly data (Jan to Dec) in thousands
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const data = [65, 80, 95, 110, 130, 145, 160, 185, 170, 190, 210, 240];

    canvas.width = canvas.parentElement.clientWidth;
    canvas.height = 220;

    const padding = 35;
    const width = canvas.width - padding * 2;
    const height = canvas.height - padding * 2;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    const barWidth = width / months.length - 10;
    const maxVal = 260;

    months.forEach((month, index) => {
        const val = data[index];
        const barHeight = (val / maxVal) * height;
        const x = padding + index * (barWidth + 10);
        const y = canvas.height - padding - barHeight;

        // Draw bar
        ctx.fillStyle = '#0B57D0';
        ctx.beginPath();
        ctx.roundRect(x, y, barWidth, barHeight, [4, 4, 0, 0]);
        ctx.fill();

        // Draw X-axis label
        ctx.fillStyle = '#7A8AA3';
        ctx.font = '11px Inter, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(month, x + barWidth / 2, canvas.height - 10);
    });
}

function renderBookingDonutChart() {
    const canvas = document.getElementById('bookingChartCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    canvas.width = canvas.parentElement.clientWidth;
    canvas.height = 220;

    const data = [
        { label: 'Completed', value: 1650, color: '#16A34A' },
        { label: 'Active', value: 420, color: '#2563EB' },
        { label: 'Pending', value: 280, color: '#D97706' },
        { label: 'Cancelled', value: 136, color: '#DC2626' }
    ];

    const total = data.reduce((sum, item) => sum + item.value, 0);
    const centerX = canvas.width / 3;
    const centerY = canvas.height / 2;
    const radius = 70;

    let startAngle = -Math.PI / 2;

    data.forEach(item => {
        const sliceAngle = (item.value / total) * 2 * Math.PI;

        ctx.fillStyle = item.color;
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
        ctx.closePath();
        ctx.fill();

        startAngle += sliceAngle;
    });

    // Donut hole
    ctx.fillStyle = '#FFFFFF';
    ctx.beginPath();
    ctx.arc(centerX, centerY, 42, 0, 2 * Math.PI);
    ctx.fill();

    // Legend
    let legendY = 40;
    data.forEach(item => {
        const legendX = canvas.width / 1.7;

        ctx.fillStyle = item.color;
        ctx.fillRect(legendX, legendY, 12, 12);

        ctx.fillStyle = '#111827';
        ctx.font = '500 12px Inter, sans-serif';
        ctx.textAlign = 'left';
        ctx.fillText(`${item.label}: ${item.value}`, legendX + 20, legendY + 10);

        legendY += 32;
    });
}

function renderRegistrationChart() {
    const canvas = document.getElementById('registrationChartCanvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    canvas.width = canvas.parentElement.clientWidth;
    canvas.height = 220;

    const categories = ['Customers', 'Owners', 'Drivers', 'Vehicles'];
    const counts = [1248, 186, 324, 512];
    const colors = ['#0B57D0', '#1683E8', '#16A34A', '#D97706'];

    const padding = 35;
    const width = canvas.width - padding * 2;
    const height = canvas.height - padding * 2;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    const barHeight = height / categories.length - 12;
    const maxVal = 1400;

    categories.forEach((cat, index) => {
        const val = counts[index];
        const barWidth = (val / maxVal) * width;
        const x = padding + 80;
        const y = padding + index * (barHeight + 12);

        // Draw bar
        ctx.fillStyle = colors[index];
        ctx.beginPath();
        ctx.roundRect(x, y, barWidth, barHeight, [0, 4, 4, 0]);
        ctx.fill();

        // Category label
        ctx.fillStyle = '#53627A';
        ctx.font = '500 12px Inter, sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText(cat, x - 10, y + barHeight / 1.5);

        // Value label
        ctx.fillStyle = '#111827';
        ctx.font = '600 12px Inter, sans-serif';
        ctx.textAlign = 'left';
        ctx.fillText(val, x + barWidth + 8, y + barHeight / 1.5);
    });
}
