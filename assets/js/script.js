// Config
const API_URL = window.location.pathname.replace(/\/[^/]*$/, '') + '/api';
let token = localStorage.getItem('token');
let user = JSON.parse(localStorage.getItem('user') || '{}');

// Check auth
if (!token) {
    window.location.href = 'login.html';
}

// Set user name
document.getElementById('userName').textContent = user.username || 'Usuario';

// State
let places = [];
let categories = [];
let currentFilter = 'all';
let markers = {};
let userLocation = null;
let userLocationMarker = null;

// Initialize map with zoom controls at bottom
const map = L.map('map', {
    zoomControl: false
}).setView([19.4326, -99.1332], 12);

// Add zoom control at bottom left
L.control.zoom({
    position: 'bottomleft'
}).addTo(map);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// Mini map for modal
let miniMap = null;
let miniMarker = null;

// Get user location on load
function initUserLocation() {
    if (navigator.geolocation) {
        const btn = document.getElementById('locationBtn');
        btn.classList.add('loading');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                map.setView([userLocation.lat, userLocation.lng], 14);
                updateUserLocationMarker();
                btn.classList.remove('loading');
            },
            (error) => {
                console.log('Geolocation error:', error);
                btn.classList.remove('loading');
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    }
}

function updateUserLocationMarker() {
    if (!userLocation) return;

    if (userLocationMarker) {
        map.removeLayer(userLocationMarker);
    }

    const userIcon = L.divIcon({
        className: 'user-location-marker',
        html: `<div style="background: #3498db; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 10px rgba(52,152,219,0.5);"></div>`,
        iconSize: [16, 16],
        iconAnchor: [8, 8]
    });

    userLocationMarker = L.marker([userLocation.lat, userLocation.lng], { icon: userIcon })
        .addTo(map)
        .bindPopup('Tu ubicación');
}

function goToMyLocation() {
    const btn = document.getElementById('locationBtn');

    if (userLocation) {
        map.setView([userLocation.lat, userLocation.lng], 16);
        updateUserLocationMarker();
        return;
    }

    if (navigator.geolocation) {
        btn.classList.add('loading');

        navigator.geolocation.getCurrentPosition(
            (position) => {
                userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                map.setView([userLocation.lat, userLocation.lng], 16);
                updateUserLocationMarker();
                btn.classList.remove('loading');
                showToast('Ubicación encontrada');
            },
            (error) => {
                btn.classList.remove('loading');
                showToast('No se pudo obtener tu ubicación');
            },
            { enableHighAccuracy: true, timeout: 10000 }
        );
    } else {
        showToast('Tu navegador no soporta geolocalización');
    }
}

// API Helper
async function api(endpoint, options = {}) {
    const headers = {
        'Authorization': 'Bearer ' + token,
        ...options.headers
    };

    if (!(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
    }

    const response = await fetch(API_URL + endpoint, {
        ...options,
        headers
    });

    if (response.status === 401) {
        logout();
        return;
    }

    return response;
}

// Toast
function showToast(message) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

// Modal functions
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

function openModal(id) {
    document.getElementById(id).classList.add('active');
}

// Toggle sidebar (mobile)
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}

// Logout
function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    window.location.href = 'login.html';
}

// Profile modal
function openProfileModal() {
    document.getElementById('profileName').textContent = user.username || 'Usuario';
    document.getElementById('profileEmail').textContent = user.email || '';
    document.getElementById('profileUsername').value = user.username || '';
    document.getElementById('profileEmailInput').value = user.email || '';
    document.getElementById('passwordForm').reset();
    openModal('profileModal');
}

// Update profile
document.getElementById('profileForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const newUsername = document.getElementById('profileUsername').value.trim();
    const newEmail = document.getElementById('profileEmailInput').value.trim();

    if (!newUsername || !newEmail) {
        showToast('Completa todos los campos');
        return;
    }

    try {
        const res = await api('/auth/profile.php', {
            method: 'PUT',
            body: JSON.stringify({
                username: newUsername,
                email: newEmail
            })
        });

        const data = await res.json();

        if (res.ok) {
            // Update local storage
            token = data.token;
            user = data.user;
            localStorage.setItem('token', token);
            localStorage.setItem('user', JSON.stringify(user));

            document.getElementById('userName').textContent = user.username;
            document.getElementById('profileName').textContent = user.username;
            document.getElementById('profileEmail').textContent = user.email;

            showToast('Perfil actualizado');
        } else {
            showToast(data.error || 'Error al actualizar');
        }
    } catch (e) {
        showToast('Error de conexión');
    }
});

// Change password
document.getElementById('passwordForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (newPassword !== confirmPassword) {
        showToast('Las contraseñas no coinciden');
        return;
    }

    if (newPassword.length < 6) {
        showToast('La contraseña debe tener al menos 6 caracteres');
        return;
    }

    try {
        const res = await api('/auth/profile.php', {
            method: 'PUT',
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            })
        });

        const data = await res.json();

        if (res.ok) {
            token = data.token;
            localStorage.setItem('token', token);
            document.getElementById('passwordForm').reset();
            showToast('Contraseña actualizada');
        } else {
            showToast(data.error || 'Error al cambiar contraseña');
        }
    } catch (e) {
        showToast('Error de conexión');
    }
});

// Load categories
async function loadCategories() {
    try {
        const res = await api('/categories/index.php');
        const data = await res.json();
        categories = data.categories || [];
        updateCategorySelect();
        renderCategoriesList();
    } catch (e) {
        console.error('Error loading categories:', e);
    }
}

function updateCategorySelect() {
    const select = document.getElementById('placeCategory');
    select.innerHTML = '<option value="">Sin categoría</option>';
    categories.forEach(cat => {
        select.innerHTML += `<option value="${cat.id}">${cat.icon} ${cat.name}</option>`;
    });
}

function renderCategoriesList() {
    const container = document.getElementById('categoriesList');
    if (categories.length === 0) {
        container.innerHTML = '<p style="color: #999; text-align: center;">No hay categorías personalizadas</p>';
        return;
    }

    container.innerHTML = categories.map(cat => `
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; border-bottom: 1px solid #eee;">
                    <span class="category-badge" style="background: ${cat.color}20; color: ${cat.color}">
                        ${cat.icon} ${cat.name}
                    </span>
                    <span style="color: #999; font-size: 12px;">${cat.places_count || 0} lugares</span>
                </div>
            `).join('');
}

// Load places
async function loadPlaces() {
    try {
        const res = await api('/places/index.php');
        const data = await res.json();
        places = data.places || [];
        renderPlaces();
        updateMapMarkers();
    } catch (e) {
        console.error('Error loading places:', e);
    }
}

function renderPlaces() {
    const container = document.getElementById('placesList');
    let filtered = places;

    if (currentFilter === 'pending') {
        filtered = places.filter(p => !p.visited);
    } else if (currentFilter === 'visited') {
        filtered = places.filter(p => p.visited);
    }

    if (filtered.length === 0) {
        container.innerHTML = `
                    <div class="empty-state">
                        <span>📍</span>
                        <p>No hay lugares ${currentFilter === 'pending' ? 'pendientes' : currentFilter === 'visited' ? 'visitados' : ''}</p>
                    </div>
                `;
        return;
    }

    container.innerHTML = filtered.map(place => {
        const category = categories.find(c => c.id == place.category_id);
        return `
                    <div class="place-card ${place.visited ? 'visited' : 'pending'}" onclick="showPlaceDetails(${place.id})">
                        <div class="place-header">
                            <div class="place-name">${place.name}</div>
                            ${category ? `<span class="place-category" style="background: ${category.color}20; color: ${category.color}">${category.icon}</span>` : ''}
                        </div>
                        ${place.address ? `<div class="place-address">📍 ${place.address}</div>` : ''}
                        <div class="place-footer">
                            <div class="stars">${'★'.repeat(place.rating || 0)}${'☆'.repeat(5 - (place.rating || 0))}</div>
                            <div class="place-meta">${place.visited ? '✓ Visitado' : '⏳ Pendiente'}</div>
                        </div>
                    </div>
                `;
    }).join('');
}

function updateMapMarkers() {
    // Clear existing markers
    Object.values(markers).forEach(m => map.removeLayer(m));
    markers = {};

    places.forEach(place => {
        const category = categories.find(c => c.id == place.category_id);
        const color = category ? category.color : (place.visited ? '#00b894' : '#fdcb6e');
        const icon = category ? category.icon : '📍';

        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `<div style="background: ${color}; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 3px 10px rgba(0,0,0,0.3); border: 3px solid white;">${icon}</div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 18]
        });

        const marker = L.marker([place.latitude, place.longitude], { icon: customIcon })
            .addTo(map)
            .bindPopup(`
                        <div class="popup-content">
                            <h3>${place.name}</h3>
                            <p>${place.address || 'Sin dirección'}</p>
                            <div class="stars" style="margin-bottom: 10px;">${'★'.repeat(place.rating || 0)}${'☆'.repeat(5 - (place.rating || 0))}</div>
                            <div class="popup-actions">
                                <button onclick="showPlaceDetails(${place.id})" style="background: #3498db; color: white;">Ver</button>
                                ${!place.visited ? `<button onclick="openVisitModal(${place.id})" style="background: #00b894; color: white;">Visitado</button>` : ''}
                            </div>
                        </div>
                    `);

        markers[place.id] = marker;
    });

    // Re-add user location marker
    updateUserLocationMarker();
}

// Filter buttons
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = btn.dataset.filter;
        renderPlaces();
    });
});

// Add place modal
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Agregar Lugar';
    document.getElementById('placeForm').reset();
    document.getElementById('placeId').value = '';
    document.getElementById('selectedCoords').textContent = 'Latitud: --, Longitud: --';
    document.getElementById('placeLat').value = '';
    document.getElementById('placeLng').value = '';
    document.getElementById('submitPlaceBtn').textContent = 'Guardar Lugar';

    openModal('placeModal');

    setTimeout(() => {
        const startLat = userLocation ? userLocation.lat : 19.4326;
        const startLng = userLocation ? userLocation.lng : -99.1332;

        if (!miniMap) {
            miniMap = L.map('miniMap', { zoomControl: false }).setView([startLat, startLng], 14);
            L.control.zoom({ position: 'bottomright' }).addTo(miniMap);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(miniMap);

            miniMap.on('click', (e) => {
                setMiniMapMarker(e.latlng.lat, e.latlng.lng);
            });
        } else {
            miniMap.setView([startLat, startLng], 14);
            if (miniMarker) {
                miniMap.removeLayer(miniMarker);
                miniMarker = null;
            }
        }
        miniMap.invalidateSize();
    }, 100);
}

function setMiniMapMarker(lat, lng) {
    if (miniMarker) {
        miniMap.removeLayer(miniMarker);
    }
    miniMarker = L.marker([lat, lng]).addTo(miniMap);
    miniMap.setView([lat, lng], 15);
    document.getElementById('placeLat').value = lat;
    document.getElementById('placeLng').value = lng;
    document.getElementById('selectedCoords').textContent = `Latitud: ${lat.toFixed(6)}, Longitud: ${lng.toFixed(6)}`;
}

// Save place
document.getElementById('placeForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const lat = document.getElementById('placeLat').value;
    const lng = document.getElementById('placeLng').value;

    if (!lat || !lng) {
        showToast('Por favor selecciona una ubicación en el mapa');
        return;
    }

    const placeId = document.getElementById('placeId').value;
    const rating = document.querySelector('input[name="rating"]:checked');

    const data = {
        name: document.getElementById('placeName').value,
        tiktok_link: document.getElementById('placeTiktok').value || null,
        category_id: document.getElementById('placeCategory').value || null,
        address: document.getElementById('placeAddress').value || null,
        latitude: parseFloat(lat),
        longitude: parseFloat(lng),
        rating: rating ? parseInt(rating.value) : 0,
        notes: document.getElementById('placeNotes').value || null
    };

    try {
        let res;
        if (placeId) {
            res = await api(`/places/update.php?id=${placeId}`, {
                method: 'PUT',
                body: JSON.stringify(data)
            });
        } else {
            res = await api('/places/index.php', {
                method: 'POST',
                body: JSON.stringify(data)
            });
        }

        if (res.ok) {
            showToast(placeId ? 'Lugar actualizado' : 'Lugar agregado');
            closeModal('placeModal');
            loadPlaces();
        } else {
            const err = await res.json();
            showToast(err.error || 'Error al guardar');
        }
    } catch (e) {
        showToast('Error de conexión');
    }
});

// Visit modal
function openVisitModal(placeId) {
    document.getElementById('visitPlaceId').value = placeId;
    document.getElementById('visitForm').reset();
    document.getElementById('uploadPreview').innerHTML = '';

    // Set current datetime
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('visitDate').value = now.toISOString().slice(0, 16);

    openModal('visitModal');
}

// File preview
document.getElementById('mediaFiles').addEventListener('change', (e) => {
    const preview = document.getElementById('uploadPreview');
    preview.innerHTML = '';

    Array.from(e.target.files).forEach((file, index) => {
        const reader = new FileReader();
        reader.onload = (ev) => {
            const isVideo = file.type.startsWith('video/');
            preview.innerHTML += `
                        <div class="media-item">
                            ${isVideo ?
                    `<video src="${ev.target.result}"></video>` :
                    `<img src="${ev.target.result}">`
                }
                        </div>
                    `;
        };
        reader.readAsDataURL(file);
    });
});

// Mark as visited
document.getElementById('visitForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const placeId = document.getElementById('visitPlaceId').value;
    const rating = document.querySelector('input[name="visitRating"]:checked');

    try {
        // Mark as visited
        const res = await api(`/places/visit.php?id=${placeId}`, {
            method: 'POST',
            body: JSON.stringify({
                visit_date: document.getElementById('visitDate').value,
                rating: rating ? parseInt(rating.value) : null,
                notes: document.getElementById('visitNotes').value || null
            })
        });

        if (!res.ok) {
            const err = await res.json();
            showToast(err.error || 'Error al marcar como visitado');
            return;
        }

        // Upload media files
        const files = document.getElementById('mediaFiles').files;
        for (let file of files) {
            const formData = new FormData();
            formData.append('file', file);

            await api(`/uploads/index.php?place_id=${placeId}`, {
                method: 'POST',
                body: formData
            });
        }

        showToast('¡Lugar marcado como visitado!');
        closeModal('visitModal');
        loadPlaces();
    } catch (e) {
        showToast('Error de conexión');
    }
});

// Place details
async function showPlaceDetails(placeId) {
    const place = places.find(p => p.id == placeId);
    if (!place) return;

    const category = categories.find(c => c.id == place.category_id);

    let mediaHtml = '';
    if (place.media && place.media.length > 0) {
        mediaHtml = `
                    <div style="margin-top: 15px;">
                        <strong>Fotos/Videos:</strong>
                        <div class="media-gallery">
                            ${place.media.map(m => {
            const url = 'uploads/' + m.file_path;
            return m.file_type === 'video' ?
                `<div class="media-item" onclick="openLightbox('${url}', 'video')"><video src="${url}"></video></div>` :
                `<div class="media-item" onclick="openLightbox('${url}', 'image')"><img src="${url}"></div>`;
        }).join('')}
                        </div>
                    </div>
                `;
    }

    document.getElementById('detailsTitle').textContent = place.name;
    document.getElementById('detailsContent').innerHTML = `
                ${category ? `<span class="category-badge" style="background: ${category.color}20; color: ${category.color}; margin-bottom: 15px; display: inline-flex;">${category.icon} ${category.name}</span>` : ''}

                ${place.tiktok_link ? `<p style="margin-bottom: 10px;"><a href="${place.tiktok_link}" target="_blank" style="color: #fe2c55;">🎵 Ver en TikTok</a></p>` : ''}

                ${place.address ? `<p style="margin-bottom: 10px;">📍 ${place.address}</p>` : ''}

                <p style="margin-bottom: 10px;">
                    <span class="stars" style="font-size: 20px;">${'★'.repeat(place.rating || 0)}${'☆'.repeat(5 - (place.rating || 0))}</span>
                </p>

                <p style="margin-bottom: 10px; padding: 10px; background: ${place.visited ? '#e8f8f5' : '#fef9e7'}; border-radius: 8px;">
                    ${place.visited ? `✅ Visitado el ${new Date(place.visit_date).toLocaleDateString('es-MX', { dateStyle: 'long' })}` : '⏳ Pendiente de visitar'}
                </p>

                ${place.notes ? `<p style="margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-radius: 8px; font-style: italic;">"${place.notes}"</p>` : ''}

                <p style="font-size: 12px; color: #999;">Agregado por ${place.created_by_username || 'Usuario'} el ${new Date(place.created_at).toLocaleDateString('es-MX')}</p>

                ${mediaHtml}

                <div class="btn-group">
                    ${!place.visited ? `<button class="btn-primary btn-success" onclick="closeModal('detailsModal'); openVisitModal(${place.id})">✓ Marcar Visitado</button>` : ''}
                    <button class="btn-primary btn-secondary" onclick="editPlace(${place.id})">✏️ Editar</button>
                    <button class="btn-primary btn-danger" onclick="deletePlace(${place.id})">🗑️ Eliminar</button>
                </div>
            `;

    openModal('detailsModal');

    // Center map on this place
    map.setView([place.latitude, place.longitude], 16);
    if (markers[place.id]) {
        markers[place.id].openPopup();
    }
}

// Edit place
function editPlace(placeId) {
    const place = places.find(p => p.id == placeId);
    if (!place) return;

    closeModal('detailsModal');

    document.getElementById('modalTitle').textContent = 'Editar Lugar';
    document.getElementById('placeId').value = place.id;
    document.getElementById('placeName').value = place.name;
    document.getElementById('placeTiktok').value = place.tiktok_link || '';
    document.getElementById('placeCategory').value = place.category_id || '';
    document.getElementById('placeAddress').value = place.address || '';
    document.getElementById('placeNotes').value = place.notes || '';
    document.getElementById('submitPlaceBtn').textContent = 'Actualizar Lugar';

    if (place.rating) {
        document.getElementById('star' + place.rating).checked = true;
    }

    openModal('placeModal');

    setTimeout(() => {
        if (!miniMap) {
            miniMap = L.map('miniMap', { zoomControl: false }).setView([place.latitude, place.longitude], 15);
            L.control.zoom({ position: 'bottomright' }).addTo(miniMap);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(miniMap);

            miniMap.on('click', (e) => {
                setMiniMapMarker(e.latlng.lat, e.latlng.lng);
            });
        }
        miniMap.invalidateSize();
        setMiniMapMarker(place.latitude, place.longitude);
    }, 100);
}

// Delete place
async function deletePlace(placeId) {
    if (!confirm('¿Estás seguro de eliminar este lugar?')) return;

    try {
        const res = await api(`/places/delete.php?id=${placeId}`, {
            method: 'DELETE'
        });

        if (res.ok) {
            showToast('Lugar eliminado');
            closeModal('detailsModal');
            loadPlaces();
        } else {
            const err = await res.json();
            showToast(err.error || 'Error al eliminar');
        }
    } catch (e) {
        showToast('Error de conexión');
    }
}

// Categories modal
function openCategoriesModal() {
    openModal('categoriesModal');
}

// Add category
document.getElementById('categoryForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    try {
        const res = await api('/categories/index.php', {
            method: 'POST',
            body: JSON.stringify({
                name: document.getElementById('categoryName').value,
                icon: document.getElementById('categoryIcon').value,
                color: document.getElementById('categoryColor').value
            })
        });

        if (res.ok) {
            showToast('Categoría agregada');
            document.getElementById('categoryForm').reset();
            document.getElementById('categoryIcon').value = '📍';
            loadCategories();
        } else {
            const err = await res.json();
            showToast(err.error || 'Error al agregar categoría');
        }
    } catch (e) {
        showToast('Error de conexión');
    }
});

// Lightbox
function openLightbox(url, type) {
    const lightbox = document.getElementById('lightbox');
    const img = document.getElementById('lightboxImg');
    const video = document.getElementById('lightboxVideo');

    if (type === 'video') {
        img.style.display = 'none';
        video.style.display = 'block';
        video.src = url;
    } else {
        video.style.display = 'none';
        img.style.display = 'block';
        img.src = url;
    }

    lightbox.classList.add('active');
}

function closeLightbox() {
    const lightbox = document.getElementById('lightbox');
    const video = document.getElementById('lightboxVideo');
    video.pause();
    lightbox.classList.remove('active');
}

// Initialize
loadCategories();
loadPlaces();
initUserLocation();