// Lanka Renters Global JavaScript
(function () {
    const incidents = [
        {
            id: 'INC-1004',
            type: 'Accident',
            status: 'Pending',
            vehicle: 'Toyota Corolla',
            district: 'Colombo',
            location: 'Borella',
            subtitle: 'Rear-end collision, insurance report pending.',
        },
        {
            id: 'INC-1009',
            type: 'Breakdown',
            status: 'Approved',
            vehicle: 'Suzuki Alto',
            district: 'Galle',
            location: 'Unawatuna',
            subtitle: 'Battery failure during rental. Replacement underway.',
        },
        {
            id: 'INC-1016',
            type: 'Accident',
            status: 'Disputed',
            vehicle: 'Honda Civic',
            district: 'Kandy',
            location: 'Peradeniya',
            subtitle: 'Minor collision at junction, dispute with owner.',
        },
        {
            id: 'INC-1021',
            type: 'Breakdown',
            status: 'Pending',
            vehicle: 'Nissan X-Trail',
            district: 'Negombo',
            location: 'Airport Road',
            subtitle: 'Engine warning light and towing required.',
        },
    ];

    const statusMap = {
        Pending: 'status-pending',
        Disputed: 'status-disputed',
        Approved: 'status-approved',
    };

    function getElement(id) {
        return document.getElementById(id);
    }

    function createStatusBadge(status) {
        const badge = document.createElement('span');
        badge.className = `status-badge ${statusMap[status] || ''}`;
        badge.textContent = status;
        return badge;
    }

    function createIncidentCard(incident) {
        const card = document.createElement('article');
        card.className = 'incident-card';

        const left = document.createElement('div');
        left.className = 'incident-card-left';

        const titleRow = document.createElement('div');
        titleRow.className = 'incident-card-title';

        const idLabel = document.createElement('span');
        idLabel.className = 'incident-id';
        idLabel.textContent = incident.id;

        const typeLabel = document.createElement('span');
        typeLabel.className = 'incident-type';
        typeLabel.textContent = incident.type;

        titleRow.appendChild(idLabel);
        titleRow.appendChild(typeLabel);
        titleRow.appendChild(createStatusBadge(incident.status));

        const location = document.createElement('div');
        location.className = 'vehicle-info';
        location.textContent = `${incident.vehicle} · ${incident.location}, ${incident.district}`;

        const subtitle = document.createElement('div');
        subtitle.className = 'vehicle-info';
        subtitle.textContent = incident.subtitle;

        left.appendChild(titleRow);
        left.appendChild(location);
        left.appendChild(subtitle);

        const right = document.createElement('div');
        right.className = 'incident-card-right';

        const viewButton = document.createElement('button');
        viewButton.type = 'button';
        viewButton.className = 'btn btn-outline';
        viewButton.textContent = 'View evidence';
        viewButton.addEventListener('click', () => openEvidenceModal(incident));

        const contactButton = document.createElement('button');
        contactButton.type = 'button';
        contactButton.className = 'btn btn-primary';
        contactButton.textContent = 'Contact owners for replacement';
        contactButton.addEventListener('click', () => alert(`Contact owner for ${incident.id}`));

        right.appendChild(viewButton);
        right.appendChild(contactButton);

        card.appendChild(left);
        card.appendChild(right);
        return card;
    }

    function filterIncidents(filters) {
        return incidents.filter((incident) => {
            if (filters.district && incident.district !== filters.district) return false;
            if (filters.vehicleType && incident.vehicle !== filters.vehicleType) return false;
            if (filters.status && incident.status !== filters.status) return false;
            return true;
        });
    }

    function renderIncidentList(filters) {
        const list = getElement('incidentList');
        if (!list) return;

        const visibleIncidents = filterIncidents(filters);
        list.innerHTML = '';

        if (visibleIncidents.length === 0) {
            const emptyState = document.createElement('div');
            emptyState.className = 'card';
            emptyState.textContent = 'No incidents match the selected filters.';
            list.appendChild(emptyState);
            return;
        }

        visibleIncidents.forEach((incident) => {
            list.appendChild(createIncidentCard(incident));
        });
    }

    function populateSelect(select, values) {
        const existing = new Set();
        values.forEach((value) => {
            if (!value || existing.has(value)) return;
            existing.add(value);
            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            select.appendChild(option);
        });
    }

    function openEvidenceModal(incident) {
        const modal = getElement('evidenceModal');
        const body = getElement('modalBody');
        const title = getElement('modalTitle');
        const subtitle = getElement('modalSubTitle');

        if (!modal || !body || !title || !subtitle) return;

        title.textContent = `${incident.id} evidence`;
        subtitle.textContent = `${incident.type} report from ${incident.location}`;
        body.innerHTML = `<p>${incident.subtitle}</p><p class="vehicle-info">Vehicle: ${incident.vehicle}</p><p class="vehicle-info">District: ${incident.district}</p>`;
        modal.classList.remove('hidden');
    }

    function closeEvidenceModal() {
        const modal = getElement('evidenceModal');
        if (modal) modal.classList.add('hidden');
    }

    function attachModalHandlers() {
        const modal = getElement('evidenceModal');
        const closeButton = getElement('modalClose');
        const actionButton = getElement('modalAction');

        if (closeButton) closeButton.addEventListener('click', closeEvidenceModal);
        if (actionButton) {
            actionButton.addEventListener('click', () => {
                alert('Incident marked as reviewed.');
                closeEvidenceModal();
            });
        }

        if (modal) {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeEvidenceModal();
                }
            });
        }
    }

    function initIncidentPage() {
        const districtSelect = getElement('incidentDistrict');
        const vehicleSelect = getElement('incidentVehicleType');
        const statusSelect = getElement('incidentStatus');

        if (!districtSelect || !vehicleSelect || !statusSelect) return;

        populateSelect(districtSelect, incidents.map((item) => item.district));
        populateSelect(vehicleSelect, incidents.map((item) => item.vehicle));

        const filters = {
            district: '',
            vehicleType: '',
            status: '',
        };

        function updateFilters() {
            filters.district = districtSelect.value;
            filters.vehicleType = vehicleSelect.value;
            filters.status = statusSelect.value;
            renderIncidentList(filters);
        }

        districtSelect.addEventListener('change', updateFilters);
        vehicleSelect.addEventListener('change', updateFilters);
        statusSelect.addEventListener('change', updateFilters);

        attachModalHandlers();
        renderIncidentList(filters);
    }

    document.addEventListener('DOMContentLoaded', initIncidentPage);
})();

