// AtomicStresser - Admin Panel JavaScript

let currentEditId = null;
let currentEditType = null;

// Tab switching
function switchAdminTab(tab) {
    document.querySelectorAll('.admin-tab-content').forEach(el => el.classList.add('hidden'));
    document.getElementById('admin-' + tab).classList.remove('hidden');
    
    ['users', 'plans', 'methods'].forEach(t => {
        const btn = document.getElementById('admin-tab-' + t);
        btn.className = t === tab
            ? 'flex items-center gap-2 px-4 py-2 rounded-lg font-medium bg-blue-600 text-white transition'
            : 'flex items-center gap-2 px-4 py-2 rounded-lg font-medium bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition';
    });
}

// Modal functions
function openModal(title) {
    document.getElementById('modal-title').textContent = title;
    document.getElementById('admin-modal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('admin-modal').classList.add('hidden');
    document.getElementById('modal-fields').innerHTML = '';
    currentEditId = null;
    currentEditType = null;
}

function createField(label, name, type = 'text', value = '', options = {}) {
    const div = document.createElement('div');
    
    if (type === 'checkbox') {
        div.innerHTML = `
            <label class="flex items-center gap-2 text-sm text-gray-300 cursor-pointer">
                <input type="checkbox" name="${name}" ${value ? 'checked' : ''} class="rounded">
                ${label}
            </label>
        `;
    } else if (type === 'select') {
        const optionsHtml = (options.choices || []).map(c => 
            `<option value="${c}" ${c === value ? 'selected' : ''}>${c}</option>`
        ).join('');
        div.innerHTML = `
            <label class="block text-sm text-gray-400 mb-1">${label}</label>
            <select name="${name}" class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                ${optionsHtml}
            </select>
        `;
    } else {
        div.innerHTML = `
            <label class="block text-sm text-gray-400 mb-1">${label}</label>
            <input type="${type}" name="${name}" value="${value}" 
                class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500"
                ${options.required ? 'required' : ''}>
        `;
    }
    return div;
}

// =================== USERS ===================
async function loadUsers() {
    try {
        const res = await fetch('api/users.php');
        const users = await res.json();
        const tbody = document.getElementById('users-table');
        
        tbody.innerHTML = users.map(u => `
            <tr class="border-t border-gray-700/50">
                <td class="px-4 py-3 text-white">${escapeHtml(u.username)}</td>
                <td class="px-4 py-3">${escapeHtml(u.email)}</td>
                <td class="px-4 py-3"><span class="bg-blue-600/20 text-blue-400 px-2 py-0.5 rounded text-xs">${escapeHtml(u.plan)}</span></td>
                <td class="px-4 py-3">${escapeHtml(u.rule)}</td>
                <td class="px-4 py-3">${new Date(u.join_date).toLocaleDateString()}</td>
                <td class="px-4 py-3">
                    <button onclick='editUser(${JSON.stringify(u)})' class="text-blue-400 hover:text-blue-300 mr-2"><i class="fas fa-edit"></i></button>
                    <button onclick="deleteUser('${u.id}')" class="text-red-400 hover:text-red-300"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } catch (err) {
        console.error('Failed to load users:', err);
    }
}

function showAddUserModal() {
    currentEditType = 'user-add';
    const fields = document.getElementById('modal-fields');
    fields.innerHTML = '';
    fields.appendChild(createField('Username', 'username', 'text', '', {required: true}));
    fields.appendChild(createField('Email', 'email', 'email', '', {required: true}));
    fields.appendChild(createField('Password', 'password', 'password', '', {required: true}));
    fields.appendChild(createField('Plan', 'plan', 'select', 'Starter', {choices: ['Starter', 'Standard', 'Advanced', 'Enterprise']}));
    fields.appendChild(createField('Role', 'rule', 'select', 'user', {choices: ['user', 'admin']}));
    fields.appendChild(createField('Max Concurrents', 'max_concurrents', 'number', '1'));
    fields.appendChild(createField('Max Seconds', 'max_seconds', 'number', '60'));
    openModal('Add User');
}

function editUser(user) {
    currentEditType = 'user-edit';
    currentEditId = user.id;
    const fields = document.getElementById('modal-fields');
    fields.innerHTML = '';
    fields.appendChild(createField('Username', 'username', 'text', user.username));
    fields.appendChild(createField('Email', 'email', 'email', user.email));
    fields.appendChild(createField('Password (leave blank to keep)', 'password', 'password', ''));
    fields.appendChild(createField('Plan', 'plan', 'select', user.plan, {choices: ['Starter', 'Standard', 'Advanced', 'Enterprise']}));
    fields.appendChild(createField('Role', 'rule', 'select', user.rule, {choices: ['user', 'admin']}));
    fields.appendChild(createField('Max Concurrents', 'max_concurrents', 'number', user.max_concurrents));
    fields.appendChild(createField('Max Seconds', 'max_seconds', 'number', user.max_seconds));
    openModal('Edit User');
}

async function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user?')) return;
    try {
        const res = await fetch('api/users.php', {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        });
        if (res.ok) {
            showToast('User deleted', 'success');
            loadUsers();
        } else {
            const data = await res.json();
            showToast(data.detail || 'Failed to delete user', 'error');
        }
    } catch (err) {
        showToast('Connection error', 'error');
    }
}

// =================== PLANS ===================
async function loadPlans() {
    try {
        const res = await fetch('api/plans.php');
        const plans = await res.json();
        const tbody = document.getElementById('plans-table');
        
        tbody.innerHTML = plans.map(p => `
            <tr class="border-t border-gray-700/50">
                <td class="px-4 py-3 text-white">${escapeHtml(p.name)}</td>
                <td class="px-4 py-3">${p.max_concurrents}</td>
                <td class="px-4 py-3">${p.max_seconds}s</td>
                <td class="px-4 py-3">${p.premium ? '<span class="text-green-400">Yes</span>' : '<span class="text-gray-500">No</span>'}</td>
                <td class="px-4 py-3">${p.api_access ? '<span class="text-green-400">Yes</span>' : '<span class="text-gray-500">No</span>'}</td>
                <td class="px-4 py-3">
                    <button onclick='editPlan(${JSON.stringify(p)})' class="text-blue-400 hover:text-blue-300 mr-2"><i class="fas fa-edit"></i></button>
                    <button onclick="deletePlan('${p.id}')" class="text-red-400 hover:text-red-300"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } catch (err) {
        console.error('Failed to load plans:', err);
    }
}

function showAddPlanModal() {
    currentEditType = 'plan-add';
    const fields = document.getElementById('modal-fields');
    fields.innerHTML = '';
    fields.appendChild(createField('Name', 'name', 'text', '', {required: true}));
    fields.appendChild(createField('Max Concurrents', 'max_concurrents', 'number', '1'));
    fields.appendChild(createField('Max Seconds', 'max_seconds', 'number', '60'));
    fields.appendChild(createField('Min Seconds', 'min_seconds', 'number', '10'));
    fields.appendChild(createField('Premium', 'premium', 'checkbox', false));
    fields.appendChild(createField('API Access', 'api_access', 'checkbox', false));
    openModal('Add Plan');
}

function editPlan(plan) {
    currentEditType = 'plan-edit';
    currentEditId = plan.id;
    const fields = document.getElementById('modal-fields');
    fields.innerHTML = '';
    fields.appendChild(createField('Name', 'name', 'text', plan.name));
    fields.appendChild(createField('Max Concurrents', 'max_concurrents', 'number', plan.max_concurrents));
    fields.appendChild(createField('Max Seconds', 'max_seconds', 'number', plan.max_seconds));
    fields.appendChild(createField('Min Seconds', 'min_seconds', 'number', plan.min_seconds));
    fields.appendChild(createField('Premium', 'premium', 'checkbox', plan.premium));
    fields.appendChild(createField('API Access', 'api_access', 'checkbox', plan.api_access));
    openModal('Edit Plan');
}

async function deletePlan(id) {
    if (!confirm('Are you sure you want to delete this plan?')) return;
    try {
        const res = await fetch('api/plans.php', {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        });
        if (res.ok) {
            showToast('Plan deleted', 'success');
            loadPlans();
        } else {
            const data = await res.json();
            showToast(data.detail || 'Failed to delete', 'error');
        }
    } catch (err) {
        showToast('Connection error', 'error');
    }
}

// =================== METHODS ===================
async function loadMethods() {
    try {
        const res = await fetch('api/methods.php');
        const methods = await res.json();
        const tbody = document.getElementById('methods-table');
        
        tbody.innerHTML = methods.map(m => `
            <tr class="border-t border-gray-700/50">
                <td class="px-4 py-3 text-white">${escapeHtml(m.name)}</td>
                <td class="px-4 py-3">${escapeHtml(m.description)}</td>
                <td class="px-4 py-3">${m.layer4 ? '<span class="text-green-400">Yes</span>' : '<span class="text-gray-500">No</span>'}</td>
                <td class="px-4 py-3">${m.layer7 ? '<span class="text-green-400">Yes</span>' : '<span class="text-gray-500">No</span>'}</td>
                <td class="px-4 py-3">${m.premium ? '<span class="text-yellow-400">Yes</span>' : '<span class="text-gray-500">No</span>'}</td>
                <td class="px-4 py-3">
                    <button onclick='editMethod(${JSON.stringify(m)})' class="text-blue-400 hover:text-blue-300 mr-2"><i class="fas fa-edit"></i></button>
                    <button onclick="deleteMethod('${m.id}')" class="text-red-400 hover:text-red-300"><i class="fas fa-trash"></i></button>
                </td>
            </tr>
        `).join('');
    } catch (err) {
        console.error('Failed to load methods:', err);
    }
}

function showAddMethodModal() {
    currentEditType = 'method-add';
    const fields = document.getElementById('modal-fields');
    fields.innerHTML = '';
    fields.appendChild(createField('Name', 'name', 'text', '', {required: true}));
    fields.appendChild(createField('Description', 'description', 'text', ''));
    fields.appendChild(createField('Layer 4', 'layer4', 'checkbox', false));
    fields.appendChild(createField('Layer 7', 'layer7', 'checkbox', false));
    fields.appendChild(createField('Amplification', 'amplification', 'checkbox', false));
    fields.appendChild(createField('Premium', 'premium', 'checkbox', false));
    fields.appendChild(createField('Proxy', 'proxy', 'checkbox', false));
    openModal('Add Method');
}

function editMethod(method) {
    currentEditType = 'method-edit';
    currentEditId = method.id;
    const fields = document.getElementById('modal-fields');
    fields.innerHTML = '';
    fields.appendChild(createField('Name', 'name', 'text', method.name));
    fields.appendChild(createField('Description', 'description', 'text', method.description));
    fields.appendChild(createField('Layer 4', 'layer4', 'checkbox', method.layer4));
    fields.appendChild(createField('Layer 7', 'layer7', 'checkbox', method.layer7));
    fields.appendChild(createField('Amplification', 'amplification', 'checkbox', method.amplification));
    fields.appendChild(createField('Premium', 'premium', 'checkbox', method.premium));
    fields.appendChild(createField('Proxy', 'proxy', 'checkbox', method.proxy));
    openModal('Edit Method');
}

async function deleteMethod(id) {
    if (!confirm('Are you sure you want to delete this method?')) return;
    try {
        const res = await fetch('api/methods.php', {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        });
        if (res.ok) {
            showToast('Method deleted', 'success');
            loadMethods();
        } else {
            const data = await res.json();
            showToast(data.detail || 'Failed to delete', 'error');
        }
    } catch (err) {
        showToast('Connection error', 'error');
    }
}

// =================== FORM SUBMIT ===================
document.getElementById('modal-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {};
    
    // Get all field values
    document.querySelectorAll('#modal-fields input, #modal-fields select').forEach(el => {
        if (el.type === 'checkbox') {
            data[el.name] = el.checked;
        } else {
            data[el.name] = el.value;
        }
    });
    
    const [type, action] = currentEditType.split('-');
    let url, method, body;
    
    if (action === 'add') {
        url = `api/${type}s.php`;
        method = 'POST';
        const fd = new FormData();
        Object.entries(data).forEach(([k, v]) => fd.append(k, v));
        body = fd;
    } else {
        url = `api/${type}s.php`;
        method = 'PUT';
        data.id = currentEditId;
        body = JSON.stringify(data);
    }
    
    try {
        const options = { method };
        if (action === 'add') {
            options.body = body;
        } else {
            options.headers = {'Content-Type': 'application/json'};
            options.body = body;
        }
        
        const res = await fetch(url, options);
        
        if (res.ok) {
            showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} saved!`, 'success');
            closeModal();
            if (type === 'user') loadUsers();
            else if (type === 'plan') loadPlans();
            else if (type === 'method') loadMethods();
        } else {
            const resData = await res.json();
            showToast(resData.detail || 'Failed to save', 'error');
        }
    } catch (err) {
        showToast('Connection error', 'error');
    }
});

// Helper
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load initial data
loadUsers();
loadPlans();
loadMethods();
