// ---------------------------------------------------------
// 1. Toast Notification System
// ---------------------------------------------------------
function showToast(message, type = 'info') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    let icon = '\u2139\ufe0f';
    if (type === 'success') icon = '\u2705';
    if (type === 'error') icon = '\u274c';
    if (type === 'warning') icon = '\u26a0\ufe0f';

    toast.innerHTML = `
        <div style="display:flex; align-items:center; gap:10px;">
            <span style="font-size:1.2rem;">${icon}</span>
            <span class="toast-message">${escapeHtml(message)}</span>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">\u00d7</button>
    `;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

function escapeHtml(text) {
    if (text === null || text === undefined) return "";
    return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
}

window.alert = function(message) { showToast(message, 'warning'); };

// ---------------------------------------------------------
// 2. Real-Time Stats
// ---------------------------------------------------------
function updateDashboardStats() {
    if (!document.getElementById('stat-downloads')) return;
    fetch('../php/get_stats.php', { credentials: 'same-origin' })
        .then(res => res.json())
        .then(data => {
            if(document.getElementById('stat-downloads')) document.getElementById('stat-downloads').innerText = data.downloads;
            if(document.getElementById('stat-resources')) document.getElementById('stat-resources').innerText = data.resources;
            if(document.getElementById('stat-rating')) document.getElementById('stat-rating').innerText = data.rating;
        })
        .catch(err => console.error(err));
}

// ---------------------------------------------------------
// 3. Dynamic Modals (Confirm Only)
// ---------------------------------------------------------
function createCustomModals() {
    if (!document.getElementById('custom-confirm-modal')) {
        const div = document.createElement('div');
        div.innerHTML = `
            <div class="modal-overlay" id="custom-confirm-modal" style="z-index:10000;">
                <div class="modal-content" style="max-width: 400px; text-align: center;">
                    <h3 style="margin-top:0;">Confirm Action</h3>
                    <p id="confirm-message" style="margin-bottom:20px; color:var(--text-muted);"></p>
                    <div class="modal-actions">
                        <button class="btn-card" id="btn-confirm-yes" style="background:#EF4444; color:white;">Yes</button>
                        <button class="btn-card" id="btn-confirm-no" style="background:#E2E8F0; color:black;">Cancel</button>
                    </div>
                </div>
            </div>`;
        // FIX: Use firstElementChild to get the div, not firstChild (which might be a text node/newline)
        document.body.appendChild(div.firstElementChild);
        document.getElementById('btn-confirm-no').onclick = () => document.getElementById('custom-confirm-modal').classList.remove('open');
    }
}

window.openCustomConfirm = function(message, callback) {
    const modal = document.getElementById('custom-confirm-modal');
    if(!modal) return; // This was failing because the modal wasn't in DOM
    document.getElementById('confirm-message').innerText = message;
    const yesBtn = document.getElementById('btn-confirm-yes');
    
    // Clone to remove old event listeners
    const newBtn = yesBtn.cloneNode(true);
    yesBtn.parentNode.replaceChild(newBtn, yesBtn);
    
    newBtn.addEventListener('click', () => { modal.classList.remove('open'); callback(); });
    modal.classList.add('open');
}

// ---------------------------------------------------------
// 4. Global Event Delegation
// ---------------------------------------------------------
document.addEventListener('click', function(e) {
    // Star Selection (Modal)
    const starSpan = e.target.closest('.star-select-row span');
    if(starSpan) {
        const val = starSpan.getAttribute('data-v');
        const parent = starSpan.parentElement;
        parent.querySelectorAll('span').forEach(s => {
            s.classList.toggle('active', s.getAttribute('data-v') <= val);
        });
        const m = document.getElementById('resource-modal');
        if(m) m.dataset.rating = val;
    }

    // Submit Review
    const submitBtn = e.target.closest('.btn-send');
    if(submitBtn) {
        const m = document.getElementById('resource-modal');
        const resId = m ? m.getAttribute('data-active-id') : null;
        const rating = m ? m.dataset.rating : 0;
        const inputField = m ? m.querySelector('.modern-input') : null;
        const comment = inputField ? inputField.value : '';

        if(!resId) return;
        if(typeof USER_IS_LOGGED_IN !== 'undefined' && !USER_IS_LOGGED_IN) { window.location.href='login.php'; return; }
        
        if(!rating || rating == 0) return showToast('Please tap a star to rate', 'warning');
        if(!comment.trim()) return showToast('Please write a comment', 'warning');

        fetch('../php/submit_review.php', { 
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ resource_id: parseInt(resId,10), rating: parseInt(rating,10), comment: comment }) 
        })
        .then(r => r.json())
        .then(d => {
            if(d.success) {
                showToast('Review posted!', 'success');
                window.openResourceModal(parseInt(resId)); 
            } else {
                showToast(d.message || 'Error', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Submission Failed', 'error');
        });
    }

    // Admin Actions (Approve/Reject)
    const adminBtn = e.target.closest('.btn-approve') || e.target.closest('.btn-reject');
    if (adminBtn) {
        const row = adminBtn.closest('tr');
        const id = adminBtn.getAttribute('data-id');
        const action = adminBtn.classList.contains('btn-approve') ? 'published' : 'rejected';
        
        window.openCustomConfirm(`Mark as ${action}?`, () => {
            fetch('../php/admin_action.php', { 
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, action: action })
            })
            .then(r => r.json())
            .then(d => {
                if(d.success) { 
                    row.remove(); 
                    showToast(`Resource ${action}`, 'success'); 
                    setTimeout(() => location.reload(), 1500); // Refresh to update stats
                }
                else showToast(d.message || 'Error', 'error');
            })
            .catch(err => { console.error(err); showToast('Network Error', 'error'); });
        });
    }

    // Modal Closing
    if (e.target.id === 'resource-modal' || e.target.id === 'upload-modal' || e.target.classList.contains('new-modal-overlay')) {
         if (e.target.classList.contains('new-modal-overlay')) {
             e.target.classList.remove('open');
         }
    }
});

// ---------------------------------------------------------
// 5. DOM Ready Listeners
// ---------------------------------------------------------
document.addEventListener('DOMContentLoaded', function() {
    // URL Messages
    const p = new URLSearchParams(window.location.search);
    if(p.has('success')) {
        let m = "Success";
        if(p.get('success') === 'uploaded') m = "File uploaded successfully!";
        if(p.get('success') === 'uploaded_pending') m = "File uploaded! Pending approval.";
        if(p.get('success') === 'logged_out') m = "Logged out successfully";
        showToast(m, 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    if(p.has('error')) {
        showToast(p.get('error'), 'error');
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Tab Logic
    const tabLogin = document.getElementById('tab-login');
    const tabRegister = document.getElementById('tab-register');
    const formLogin = document.getElementById('login-form');
    const formRegister = document.getElementById('register-form');

    if (tabLogin && tabRegister && formLogin && formRegister) {
        tabLogin.addEventListener('click', () => {
            tabLogin.classList.add('active');
            tabRegister.classList.remove('active');
            formLogin.classList.remove('hidden');
            formRegister.classList.add('hidden');
        });
        tabRegister.addEventListener('click', () => {
            tabRegister.classList.add('active');
            tabLogin.classList.remove('active');
            formRegister.classList.remove('hidden');
            formLogin.classList.add('hidden');
        });
    }

    createCustomModals(); // Initialize Modals
    updateDashboardStats();
    setInterval(updateDashboardStats, 5000);

    // Sidebar
    const toggle = document.getElementById('menu-toggle');
    const side = document.getElementById('sidebar');
    const close = document.querySelector('.sidebar-close-btn');
    if(toggle) toggle.onclick = () => side.classList.add('open');
    if(close) close.onclick = () => side.classList.remove('open');

    // --- UPLOAD LOGIC ---
    const dz = document.getElementById('drop-zone');
    const fi = document.getElementById('file-input');
    const btnReset = document.getElementById('btn-reset-upload');
    const catBox = document.getElementById('category-box');
    const placeholder = document.getElementById('upload-placeholder');
    
    if (dz && fi) {
        dz.addEventListener('click', (e) => {
            if (typeof USER_IS_LOGGED_IN !== 'undefined' && !USER_IS_LOGGED_IN) { window.location.href='login.php'; return; }
            fi.click();
        });

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(n => dz.addEventListener(n, (e) => { e.preventDefault(); e.stopPropagation(); }));
        ['dragenter', 'dragover'].forEach(n => dz.addEventListener(n, () => { dz.style.backgroundColor='#DBEAFE'; dz.style.borderColor='#3B82F6'; }));
        ['dragleave', 'drop'].forEach(n => dz.addEventListener(n, (e) => { if(n==='drop'||!dz.contains(e.relatedTarget)){dz.style.backgroundColor='#F8FAFC'; dz.style.borderColor='#CBD5E1';} }));

        dz.addEventListener('drop', (e) => {
            if (typeof USER_IS_LOGGED_IN !== 'undefined' && !USER_IS_LOGGED_IN) { window.location.href='login.php'; return; }
            if (e.dataTransfer.files.length) { fi.files = e.dataTransfer.files; fi.dispatchEvent(new Event('change')); }
        });

        // 6. Handle File Selection (UPDATED)
        fi.addEventListener('change', () => { 
            if(fi.files.length) { 
                const file = fi.files[0];
                if (file.size > 524288000) {
                    showToast('File is too large (Max 500MB)', 'error');
                    fi.value = ''; 
                    return;
                }

                const txtEl = dz.querySelector('.file-text');
                if (txtEl) {
                    txtEl.innerHTML = `Selected: <strong>${escapeHtml(file.name)}</strong>`;
                    txtEl.style.color = '#3B82F6';
                }
                
                // --- NEW: Auto-populate Title from Filename ---
                const titleInput = document.querySelector('input[name="title"]');
                if (titleInput && !titleInput.value) {
                    // Remove file extension (e.g., "Note.pdf" -> "Note")
                    const nameWithoutExt = file.name.replace(/\.[^/.]+$/, "");
                    titleInput.value = nameWithoutExt;
                }
                // ----------------------------------------------
                
                if (catBox) catBox.style.display = 'flex';
                if (placeholder) placeholder.style.display = 'none';
            } 
        });

        if (btnReset) btnReset.addEventListener('click', (e) => {
            e.stopPropagation(); fi.value='';
            const t = dz.querySelector('.file-text'); if(t) { t.innerHTML = 'Drag & Drop file here<br><span class="browse-link" style="color:var(--primary-blue); font-weight:700;">or Browse</span>'; t.style.color='#475569'; }
            if(catBox) catBox.style.display='none'; if(placeholder) placeholder.style.display='flex';
        });
    }
});

// ---------------------------------------------------------
// 6. Modal Functions
// ---------------------------------------------------------
window.openUploadModal = function() {
    if (typeof USER_IS_LOGGED_IN !== 'undefined' && !USER_IS_LOGGED_IN) { window.location.href='login.php'; return; }
    const m = document.getElementById('upload-modal');
    if(m) m.classList.add('open');
};
window.closeUploadModal = function() { document.getElementById('upload-modal').classList.remove('open'); };

window.openResourceModal = function(identifier) {
    const m = document.getElementById('resource-modal');
    if(!m) return;
    
    m.classList.add('open');
    const titleEl = m.querySelector('.resource-title');
    if(titleEl) titleEl.innerText = "Loading...";
    if(m.querySelector('.resource-type-badge')) m.querySelector('.resource-type-badge').innerText = "...";
    if(m.querySelector('.reviews-list')) m.querySelector('.reviews-list').innerHTML = '<p style="color:#94a3b8; text-align:center;">Loading reviews...</p>';

    let url = `../php/get_resource_details.php?title=${encodeURIComponent(identifier)}`;
    if (typeof identifier === 'number' || /^\d+$/.test(String(identifier))) {
        url = `../php/get_resource_details.php?id=${identifier}`;
    }

    fetch(url, { credentials: 'same-origin' })
    .then(r => r.json())
    .then(d => {
        if(d.success) {
            const rsrc = d.resource;
            m.setAttribute('data-active-id', rsrc.id);
            m.querySelector('.resource-title').innerText = rsrc.title;
            m.querySelector('.resource-type-badge').innerText = rsrc.type;
            m.querySelector('.course-info').innerText = rsrc.course_name || rsrc.subject;
            
            const ext = (rsrc.file_path || '').split('.').pop().toLowerCase();
            m.querySelector('.file-name-display').innerText = `${rsrc.title}.${ext}`;
            
            const dlBtn = m.querySelector('.btn-primary-download');
            if(dlBtn) {
                dlBtn.onclick = () => {
                    if(d.current_user_id == 0) window.location.href='login.php';
                    else { 
                        window.location.href=`../php/download.php?file=${encodeURIComponent(rsrc.file_path)}`; 
                        updateDashboardStats(); 
                    }
                };
            }
            
            const reviewsList = m.querySelector('.reviews-list');
            reviewsList.innerHTML = '';
            if(d.reviews.length === 0) {
                reviewsList.innerHTML = '<div style="text-align:center; padding:20px; color:#cbd5e1;">No reviews yet.</div>';
            } else {
                d.reviews.forEach(rv => {
                    const div = document.createElement('div');
                    div.className = 'modern-review-item';
                    div.innerHTML = `
                        <div class="modern-avatar">${escapeHtml((rv.full_name || 'U')[0])}</div>
                        <div class="modern-review-content">
                            <div class="m-review-header">
                                <span class="m-user-name">${escapeHtml(rv.full_name)}</span>
                                <span class="m-stars">${'\u2605'.repeat(rv.rating)}</span>
                            </div>
                            <div class="m-comment">${escapeHtml(rv.comment)}</div>
                        </div>
                    `;
                    reviewsList.appendChild(div);
                });
            }
        } else {
            showToast('Resource not found', 'error');
            m.classList.remove('open');
        }
    })
    .catch(err => { console.error(err); showToast('Connection error', 'error'); });
};

window.closeResourceModal = function() {
    const el = document.getElementById('resource-modal');
    if (el) el.classList.remove('open');
};

window.deleteResource = function(id, btnElement) {
    window.openCustomConfirm("Permanently delete this file?", () => {
        const meta = document.querySelector('meta[name="csrf-token"]');
        const token = meta ? meta.getAttribute('content') : '';
        
        fetch('../php/delete_resource.php', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ id: id, csrf_token: token }) 
        })
        .then(r => r.json())
        .then(d => {
            if (d && d.success) {
                if(btnElement) btnElement.closest('tr').remove();
                showToast('Resource deleted', 'success');
                updateDashboardStats();
            } else {
                showToast(d.message || 'Error', 'error');
            }
        })
        .catch(err => { console.error(err); showToast('Network Error', 'error'); });
    });
};

window.togglePassword = (id, ic) => { const i = document.getElementById(id); if (!i) return; i.type = i.type === 'password' ? 'text' : 'password'; if (ic && ic.style) ic.style.color = i.type === 'password' ? '#64748B' : '#3B82F6'; };