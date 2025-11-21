// Dashboard JavaScript Functions - completed modals and full modal behavior
// NOTE: all server requests use '../api/admin.php' and rely on the server-side 'action' POST parameter.
// This file implements fully functional modals for:
// - Account tab behavior (already present)
// - User modal (create/edit)
// - Company modal (edit company basic info + simple CSV lists for addresses/contacts/emails)
// - Product modal (create/edit with image upload preview)
// - Social media modal (add/edit for admin/company)
// - Profile picture upload modal
// Includes helpers for creating/destroying modals, form submission, previews, and graceful error handling.

// ----------------------------- Utilities ---------------------------------
function createModal(innerHtml, opts = {}) {
    // opts: { dismissOnClickOutside: true, className: '' }
    const modal = document.createElement('div');
    modal.className = 'modal generated-modal ' + (opts.className || '');
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close" title="Close">&times;</span>
            ${innerHtml}
        </div>
    `;
    document.body.appendChild(modal);

    // Close button
    modal.querySelector('.close').addEventListener('click', () => {
        modal.remove();
    });

    // Click outside to close
    if (opts.dismissOnClickOutside !== false) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) modal.remove();
        });
    }

    // ESC key to close
    const escHandler = function (e) {
        if (e.key === 'Escape') {
            modal.remove();
            window.removeEventListener('keydown', escHandler);
        }
    };
    window.addEventListener('keydown', escHandler);

    return modal;
}

function showAlert(msg, isError = false) {
    // Simple inline alert inside a modal form; create ephemeral toast-like alert if not inside modal.
    const existing = document.querySelector('.modal .form-alert, .generated-alert');
    if (existing) existing.remove();

    const alertEl = document.createElement('div');
    alertEl.className = 'generated-alert ' + (isError ? 'alert-error' : 'alert-success');
    alertEl.textContent = msg;
    document.body.appendChild(alertEl);
    setTimeout(() => alertEl.remove(), 4000);
}

// Helper to post JSON (or FormData) to the API
function postToApi(data, isFormData = false) {
    const opts = {
        method: 'POST',
        headers: {}
    };
    if (isFormData) {
        opts.body = data;
    } else {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(data);
    }
    return fetch('../api/admin.php', opts).then(r => r.json());
}

// ----------------------------- Account Tab -------------------------------
function toggleAccountTab() {
    const modal = document.getElementById('accountModal');
    if (!modal) return;
    modal.style.display = (modal.style.display === 'block') ? 'none' : 'block';
}

function closeAccountModal() {
    const modal = document.getElementById('accountModal');
    if (!modal) return;
    modal.style.display = 'none';
}

function showAccountTab(tabName) {
    document.querySelectorAll('#accountModal .tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('#accountModal .tab-btn').forEach(btn => btn.classList.remove('active'));
    const target = document.getElementById(tabName + '-tab');
    if (target) target.classList.add('active');
    // set active class on button
    const btns = Array.from(document.querySelectorAll('#accountModal .tab-btn'));
    if (tabName === 'profile') btns[0] && btns[0].classList.add('active');
    if (tabName === 'password') btns[1] && btns[1].classList.add('active');
}

// ----------------------------- Admin Login / Profile / Password -------------------------------
function adminLogin(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'admin_login');

    postToApi(formData, true)
    .then(data => {
        if (data.success) {
            showAlert('Login successful. Reloading...');
            setTimeout(() => location.reload(), 800);
        } else {
            showAlert(data.message || 'Login failed', true);
        }
    })
    .catch(err => {
        console.error(err);
        showAlert('An error occurred while logging in', true);
    });
}

function updateProfile(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('action', 'update_profile');

    postToApi(formData, true)
    .then(data => {
        showAlert(data.message || 'Profile update complete', !data.success);
        if (data.success) setTimeout(() => location.reload(), 800);
    })
    .catch(err => {
        console.error(err);
        showAlert('An error occurred while updating profile', true);
    });
}

function changePassword(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    if (formData.get('new_password') !== formData.get('confirm_password')) {
        showAlert('New passwords do not match', true);
        return;
    }
    formData.append('action', 'change_password');

    postToApi(formData, true)
    .then(data => {
        showAlert(data.message || 'Password changed', !data.success);
        if (data.success) e.target.reset();
    })
    .catch(err => {
        console.error(err);
        showAlert('An error occurred while changing password', true);
    });
}

// ----------------------------- User Modal (Create / Edit) -------------------------------
function openUserModal(userId = null) {
    const isEdit = !!userId;
    const title = isEdit ? 'Edit Card Holder' : 'Add New Card Holder';
    const inner = `
        <h2>${title}</h2>
        <form id="userForm" class="modal-form">
            <div class="form-alert" style="display:none"></div>
            <input type="hidden" name="user_id" value="${userId || ''}">
            <div class="form-group"><label>First Name</label><input name="first_name" required></div>
            <div class="form-group"><label>Middle Name</label><input name="middle_name"></div>
            <div class="form-group"><label>Last Name</label><input name="last_name" required></div>
            <div class="form-group"><label>Email</label><input name="email" type="email" required></div>
            <div class="form-group"><label>Contact Number</label><input name="contact_number"></div>
            <div class="form-group"><label>NFC UID (readonly)</label><input name="nfc_uid" readonly></div>
            <div class="form-group"><label>Active</label><select name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div class="form-group"><label>Profile Picture</label><input name="profile_picture" type="file" accept="image/*"></div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">${isEdit ? 'Save Changes' : 'Create Card Holder'}</button>
                <button type="button" class="btn-secondary" id="userCancel">Cancel</button>
                ${isEdit ? '<button type="button" class="btn-danger" id="userDelete">Delete</button>' : ''}
            </div>
        </form>
    `;
    const modal = createModal(inner, { className: 'user-modal' });

    // Cancel handler
    modal.querySelector('#userCancel').addEventListener('click', () => modal.remove());

    // If editing, fetch user data
    if (isEdit) {
        const fd = new FormData();
        fd.append('action', 'get_user');
        fd.append('user_id', userId);
        postToApi(fd, true)
        .then(res => {
            if (res.success && res.user) {
                const form = modal.querySelector('#userForm');
                for (const k in res.user) {
                    const el = form.querySelector(`[name="${k}"]`);
                    if (el) el.value = res.user[k] == null ? '' : res.user[k];
                }
            } else {
                showAlert(res.message || 'Failed to load user', true);
            }
        })
        .catch(err => {
            console.error(err);
            showAlert('Failed to retrieve user', true);
        });
    }

    // Delete handler (if editing)
    const delBtn = modal.querySelector('#userDelete');
    if (delBtn) {
        delBtn.addEventListener('click', function () {
            if (!confirm('Are you sure you want to delete this user?')) return;
            const payload = { action: 'delete_user', user_id: userId };
            postToApi(payload)
            .then(res => {
                showAlert(res.message || 'User deleted', !res.success);
                if (res.success) setTimeout(() => location.reload(), 600);
            })
            .catch(err => {
                console.error(err);
                showAlert('Failed to delete user', true);
            });
        });
    }

    // Submit handler for create/update
    modal.querySelector('#userForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', isEdit ? 'update_user' : 'create_user');

        showFormLoading(form, true);
        postToApi(formData, true)
        .then(res => {
            showFormLoading(form, false);
            if (res.success) {
                showAlert(res.message || 'Saved successfully');
                setTimeout(() => location.reload(), 700);
            } else {
                showInlineFormAlert(form, res.message || 'Failed to save', true);
            }
        })
        .catch(err => {
            showFormLoading(form, false);
            console.error(err);
            showInlineFormAlert(form, 'An error occurred while saving', true);
        });
    });

    // Image preview for profile_picture
    const fileInput = modal.querySelector('input[name="profile_picture"]');
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const img = document.createElement('img');
            img.style.maxWidth = '120px';
            img.style.display = 'block';
            const reader = new FileReader();
            reader.onload = function (ev) {
                img.src = ev.target.result;
            };
            reader.readAsDataURL(file);
            // Insert preview
            const existing = modal.querySelector('.profile-preview');
            if (existing) existing.remove();
            const preview = document.createElement('div');
            preview.className = 'profile-preview';
            preview.appendChild(img);
            this.parentNode.appendChild(preview);
        });
    }
}

// Inline form alert helpers
function showInlineFormAlert(form, message, isError = false) {
    const alertEl = form.querySelector('.form-alert');
    if (!alertEl) return showAlert(message, isError);
    alertEl.style.display = 'block';
    alertEl.textContent = message;
    alertEl.className = 'form-alert ' + (isError ? 'alert-error' : 'alert-success');
}

function showFormLoading(form, loading) {
    const btn = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = !!loading;
    if (loading) {
        btn.dataset.prev = btn.textContent;
        btn.textContent = 'Saving...';
    } else if (btn && btn.dataset.prev) {
        btn.textContent = btn.dataset.prev;
        delete btn.dataset.prev;
    }
}

// ----------------------------- Company Modal -------------------------------
function openCompanyModal() {
    const inner = `
        <h2>Edit Company</h2>
        <form id="companyForm" class="modal-form">
            <div class="form-alert" style="display:none"></div>
            <div class="form-group"><label>Company Name</label><input name="company_name" required></div>
            <div class="form-group"><label>Tagline / Description</label><input name="company_tagline"></div>
            <div class="form-group"><label>Logo</label><input name="logo" type="file" accept="image/*"></div>
            <div class="form-group"><label>Addresses (comma separated)</label><input name="addresses"></div>
            <div class="form-group"><label>Contacts (comma separated)</label><input name="contacts"></div>
            <div class="form-group"><label>Emails (comma separated)</label><input name="emails"></div>
            <div class="form-group"><label>Map Latitude</label><input name="map_latitude" type="text"></div>
            <div class="form-group"><label>Map Longitude</label><input name="map_longitude" type="text"></div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Save Company</button>
                <button type="button" class="btn-secondary" id="companyCancel">Cancel</button>
            </div>
        </form>
    `;
    const modal = createModal(inner, { className: 'company-modal' });
    modal.querySelector('#companyCancel').addEventListener('click', () => modal.remove());

    // Fetch company data
    const fd = new FormData();
    fd.append('action', 'get_company');
    postToApi(fd, true)
    .then(res => {
        if (res.success && res.company) {
            const form = modal.querySelector('#companyForm');
            const company = res.company;
            form.querySelector('[name="company_name"]').value = company.company_name || '';
            form.querySelector('[name="company_tagline"]').value = company.tagline || '';
            form.querySelector('[name="map_latitude"]').value = company.map_latitude || '';
            form.querySelector('[name="map_longitude"]').value = company.map_longitude || '';
            // Convert arrays to comma-separated strings if provided
            if (Array.isArray(company.addresses)) form.querySelector('[name="addresses"]').value = company.addresses.map(a => a.full_address || '').join(', ');
            if (Array.isArray(company.contacts)) form.querySelector('[name="contacts"]').value = company.contacts.map(c => c.contact_number || '').join(', ');
            if (Array.isArray(company.emails)) form.querySelector('[name="emails"]').value = company.emails.map(e => e.email_address || '').join(', ');
        } else {
            showAlert(res.message || 'Failed to load company', true);
        }
    })
    .catch(err => {
        console.error(err);
        showAlert('Failed to retrieve company data', true);
    });

    modal.querySelector('#companyForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'update_company');

        showFormLoading(form, true);
        postToApi(formData, true)
        .then(res => {
            showFormLoading(form, false);
            showInlineFormAlert(form, res.message || 'Saved', !res.success);
            if (res.success) setTimeout(() => location.reload(), 700);
        })
        .catch(err => {
            showFormLoading(form, false);
            console.error(err);
            showInlineFormAlert(form, 'An error occurred while saving', true);
        });
    });

    // Logo preview
    const logoInput = modal.querySelector('input[name="logo"]');
    if (logoInput) {
        logoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (ev) {
                const img = document.createElement('img');
                img.src = ev.target.result;
                img.style.maxWidth = '140px';
                // remove previous preview
                const prev = modal.querySelector('.logo-preview');
                if (prev) prev.remove();
                const container = document.createElement('div');
                container.className = 'logo-preview';
                container.appendChild(img);
                logoInput.parentNode.appendChild(container);
            };
            reader.readAsDataURL(file);
        });
    }
}

// ----------------------------- Product Modal -------------------------------
function openProductModal(productId = null) {
    const isEdit = !!productId;
    const inner = `
        <h2>${isEdit ? 'Edit Product' : 'Add Product'}</h2>
        <form id="productForm" class="modal-form">
            <div class="form-alert" style="display:none"></div>
            <input type="hidden" name="product_id" value="${productId || ''}">
            <div class="form-group"><label>Product Name</label><input name="product_name" required></div>
            <div class="form-group"><label>Description</label><textarea name="description"></textarea></div>
            <div class="form-group"><label>Price</label><input name="price" type="text"></div>
            <div class="form-group"><label>Image</label><input name="image" type="file" accept="image/*"></div>
            <div class="form-group"><label>Alt Text</label><input name="image_alt_text"></div>
            <div class="form-group"><label>Active</label><select name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">${isEdit ? 'Save Product' : 'Create Product'}</button>
                <button type="button" class="btn-secondary" id="productCancel">Cancel</button>
                ${isEdit ? '<button type="button" class="btn-danger" id="productDelete">Delete</button>' : ''}
            </div>
        </form>
    `;
    const modal = createModal(inner, { className: 'product-modal' });
    modal.querySelector('#productCancel').addEventListener('click', () => modal.remove());

    if (isEdit) {
        const fd = new FormData();
        fd.append('action', 'get_product');
        fd.append('product_id', productId);
        postToApi(fd, true)
        .then(res => {
            if (res.success && res.product) {
                const p = res.product;
                const form = modal.querySelector('#productForm');
                form.querySelector('[name="product_name"]').value = p.product_name || '';
                form.querySelector('[name="description"]').value = p.description || '';
                form.querySelector('[name="price"]').value = p.price || '';
                form.querySelector('[name="image_alt_text"]').value = p.image_alt_text || '';
                form.querySelector('[name="is_active"]').value = p.is_active ? '1' : '0';
                if (p.image_path) {
                    const img = document.createElement('img');
                    img.src = p.image_path;
                    img.style.maxWidth = '140px';
                    const container = document.createElement('div');
                    container.className = 'product-image-preview';
                    container.appendChild(img);
                    modal.querySelector('input[name="image"]').parentNode.appendChild(container);
                }
            } else {
                showAlert(res.message || 'Failed to load product', true);
            }
        })
        .catch(err => {
            console.error(err);
            showAlert('Failed to retrieve product', true);
        });
    }

    const deleteBtn = modal.querySelector('#productDelete');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            if (!confirm('Are you sure you want to delete this product?')) return;
            const payload = { action: 'delete_product', product_id: productId };
            postToApi(payload)
            .then(res => {
                showAlert(res.message || 'Product deleted', !res.success);
                if (res.success) setTimeout(() => location.reload(), 700);
            })
            .catch(err => {
                console.error(err);
                showAlert('Failed to delete product', true);
            });
        });
    }

    modal.querySelector('#productForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', isEdit ? 'update_product' : 'create_product');

        showFormLoading(form, true);
        postToApi(formData, true)
        .then(res => {
            showFormLoading(form, false);
            showInlineFormAlert(form, res.message || 'Saved', !res.success);
            if (res.success) setTimeout(() => location.reload(), 700);
        })
        .catch(err => {
            showFormLoading(form, false);
            console.error(err);
            showInlineFormAlert(form, 'An error occurred while saving product', true);
        });
    });

    // Image preview
    const imageInput = modal.querySelector('input[name="image"]');
    if (imageInput) {
        imageInput.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (ev) {
                const img = document.createElement('img');
                img.src = ev.target.result;
                img.style.maxWidth = '140px';
                const prev = modal.querySelector('.product-image-preview');
                if (prev) prev.remove();
                const container = document.createElement('div');
                container.className = 'product-image-preview';
                container.appendChild(img);
                imageInput.parentNode.appendChild(container);
            };
            reader.readAsDataURL(file);
        });
    }
}

// ----------------------------- Social Media Modal -------------------------------
function addSocialMedia(type = 'admin', socialId = null) {
    // type: 'admin' or 'company'
    const isEdit = !!socialId;
    const title = (isEdit ? 'Edit' : 'Add') + ' Social Media (' + type + ')';
    const inner = `
        <h2>${title}</h2>
        <form id="socialForm" class="modal-form">
            <div class="form-alert" style="display:none"></div>
            <input type="hidden" name="social_id" value="${socialId || ''}">
            <div class="form-group"><label>Platform Name</label><input name="platform_name" required></div>
            <div class="form-group"><label>URL</label><input name="url" type="url" required></div>
            <div class="form-group"><label>Icon (file)</label><input name="platform_icon" type="file" accept="image/*"></div>
            <div class="form-group"><label>Display Order</label><input name="display_order" type="number" value="0"></div>
            <div class="form-group"><label>Active</label><select name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">${isEdit ? 'Save' : 'Add'}</button>
                <button type="button" class="btn-secondary" id="socialCancel">Cancel</button>
            </div>
        </form>
    `;
    const modal = createModal(inner, { className: 'social-modal' });
    modal.querySelector('#socialCancel').addEventListener('click', () => modal.remove());

    // If editing, fetch social data
    if (isEdit) {
        const fd = new FormData();
        fd.append('action', 'get_social');
        fd.append('social_id', socialId);
        fd.append('type', type);
        postToApi(fd, true)
        .then(res => {
            if (res.success && res.social) {
                const form = modal.querySelector('#socialForm');
                const s = res.social;
                form.querySelector('[name="platform_name"]').value = s.platform_name || '';
                form.querySelector('[name="url"]').value = s.url || '';
                form.querySelector('[name="display_order"]').value = s.display_order || 0;
                form.querySelector('[name="is_active"]').value = s.is_active ? '1' : '0';
            } else {
                showAlert(res.message || 'Failed to load social media', true);
            }
        })
        .catch(err => {
            console.error(err);
            showAlert('Failed to retrieve social media', true);
        });
    }

    modal.querySelector('#socialForm').addEventListener('submit', function (e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', isEdit ? 'update_social' : 'create_social');
        formData.append('type', type);

        showFormLoading(form, true);
        postToApi(formData, true)
        .then(res => {
            showFormLoading(form, false);
            showInlineFormAlert(form, res.message || 'Saved', !res.success);
            if (res.success) setTimeout(() => location.reload(), 700);
        })
        .catch(err => {
            showFormLoading(form, false);
            console.error(err);
            showInlineFormAlert(form, 'An error occurred while saving social media', true);
        });
    });
}

// ----------------------------- Profile Picture -------------------------------
function editProfilePicture() {
    const inner = `
        <h2>Update Profile Picture</h2>
        <form id="profilePicForm" class="modal-form">
            <div class="form-alert" style="display:none"></div>
            <div class="form-group"><label>Select Image</label><input name="profile_picture" type="file" accept="image/*" required></div>
            <div class="form-actions">
                <button type="submit" class="btn-primary">Upload</button>
                <button type="button" class="btn-secondary" id="profilePicCancel">Cancel</button>
            </div>
        </form>
    `;
    const modal = createModal(inner, { className: 'profile-pic-modal' });
    modal.querySelector('#profilePicCancel').addEventListener('click', () => modal.remove());

    const form = modal.querySelector('#profilePicForm');
    // preview
    const fileInput = form.querySelector('[name="profile_picture"]');
    fileInput.addEventListener('change', function () {
        const f = this.files[0];
        const prev = modal.querySelector('.profile-preview');
        if (prev) prev.remove();
        if (!f) return;
        const reader = new FileReader();
        reader.onload = function (ev) {
            const img = document.createElement('img');
            img.src = ev.target.result;
            img.style.maxWidth = '160px';
            const container = document.createElement('div');
            container.className = 'profile-preview';
            container.appendChild(img);
            fileInput.parentNode.appendChild(container);
        };
        reader.readAsDataURL(f);
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        const fd = new FormData(form);
        fd.append('action', 'upload_profile_picture');

        showFormLoading(form, true);
        postToApi(fd, true)
        .then(res => {
            showFormLoading(form, false);
            showInlineFormAlert(form, res.message || 'Uploaded', !res.success);
            if (res.success) setTimeout(() => location.reload(), 700);
        })
        .catch(err => {
            showFormLoading(form, false);
            console.error(err);
            showInlineFormAlert(form, 'Upload failed', true);
        });
    });
}

// ----------------------------- NFC Registration (WebView2) -------------------------------
// registerNFC is left as-is if defined elsewhere; provide fallback that triggers the user modal for "manual" entry.
if (typeof registerNFC === 'undefined') {
    window.registerNFC = function (userId) {
        if (window.chrome && window.chrome.webview && window.chrome.webview.postMessage) {
            if (confirm('Please tap the NFC card on the reader. Click OK when ready.')) {
                window.chrome.webview.postMessage(JSON.stringify({ action: 'registerNFC', userId: userId }));
            }
        } else {
            // Fallback: show a modal to allow admin to paste NFC UID manually
            const inner = `
                <h2>Register NFC</h2>
                <form id="nfcForm" class="modal-form">
                    <div class="form-alert" style="display:none"></div>
                    <input type="hidden" name="user_id" value="${userId}">
                    <div class="form-group"><label>NFC UID</label><input name="nfc_uid" required></div>
                    <div class="form-actions"><button type="submit" class="btn-primary">Save</button><button type="button" class="btn-secondary" id="nfcCancel">Cancel</button></div>
                </form>
            `;
            const modal = createModal(inner, { className: 'nfc-modal' });
            modal.querySelector('#nfcCancel').addEventListener('click', () => modal.remove());
            modal.querySelector('#nfcForm').addEventListener('submit', function (e) {
                e.preventDefault();
                const fd = new FormData(e.target);
                fd.append('action', 'register_nfc_manual');
                postToApi(fd, true)
                .then(res => {
                    showInlineFormAlert(e.target, res.message || 'Saved', !res.success);
                    if (res.success) setTimeout(() => location.reload(), 700);
                })
                .catch(err => {
                    console.error(err);
                    showInlineFormAlert(e.target, 'Failed to register NFC', true);
                });
            });
        }
    };
}

// ----------------------------- Small helpers / Existing functions kept -------------------------------
function editUser(userId) { openUserModal(userId); }
function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        postToApi({ action: 'delete_user', user_id: userId })
        .then(data => {
            showAlert(data.message || 'Deleted', !data.success);
            if (data.success) setTimeout(() => location.reload(), 700);
        })
        .catch(err => { console.error(err); showAlert('Failed to delete user', true); });
    }
}

function editProduct(productId) { openProductModal(productId); }
function deleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product?')) {
        postToApi({ action: 'delete_product', product_id: productId })
        .then(data => {
            showAlert(data.message || 'Deleted', !data.success);
            if (data.success) setTimeout(() => location.reload(), 700);
        })
        .catch(err => { console.error(err); showAlert('Failed to delete product', true); });
    }
}

function viewProduct(imagePath) {
    const modal = createModal(`<span class="close" title="Close">&times;</span><img src="${imagePath}" alt="Product" style="max-width:100%;">`, { className: 'product-view' });
    // close is already wired in createModal
}

// Close generated modal(s) when clicking outside is already handled by createModal.
// Keep legacy window.onclick behavior for compatibility with existing page markup:
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
            // remove generated modals
            if (modal.classList.contains('generated-modal')) modal.remove();
        }
    });
};