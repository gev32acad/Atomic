// AtomicStresser - Admin Panel JavaScript

let currentEditId = null;
let currentEditType = null;

// Get CSRF token from page
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// Tab switching
function switchAdminTab(tab) {
    document.querySelectorAll('.admin-tab-content').forEach(el => el.classList.add('hidden'));
    document.getElementById('admin-' + tab).classList.remove('hidden');
    
    ['users', 'plans', 'orders', 'methods'].forEach(t => {
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
                <input type="checkbox" name="${escapeHtml(name)}" ${value ? 'checked' : ''} class="rounded">
                ${escapeHtml(label)}
            </label>
        `;
    } else if (type === 'select') {
        const optionsHtml = (options.choices || []).map(c => 
            `<option value="${escapeHtml(c)}" ${c === value ? 'selected' : ''}>${escapeHtml(c)}</option>`
        ).join('');
        div.innerHTML = `
            <label class="block text-sm text-gray-400 mb-1">${escapeHtml(label)}</label>
            <select name="${escapeHtml(name)}" class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500">
                ${optionsHtml}
            </select>
        `;
    } else {
        div.innerHTML = `
            <label class="block text-sm text-gray-400 mb-1">${escapeHtml(label)}</label>
            <input type="${escapeHtml(type)}" name="${escapeHtml(name)}" value="${escapeHtml(String(value))}" 
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
        
        if (!users.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-400">No users found</td></tr>';
            return;
        }
        
        // XSS-safe rendering (#4): use textContent instead of innerHTML for user data
        tbody.innerHTML = '';
        users.forEach(u => {
            const tr = document.createElement('tr');
            tr.className = 'border-t border-gray-700/50';
            
            const tdUsername = document.createElement('td');
            tdUsername.className = 'px-4 py-3 text-white';
            tdUsername.textContent = u.username;
            
            const tdEmail = document.createElement('td');
            tdEmail.className = 'px-4 py-3';
            tdEmail.textContent = u.email;
            
            const tdPlan = document.createElement('td');
            tdPlan.className = 'px-4 py-3';
            tdPlan.innerHTML = `<span class="bg-blue-600/20 text-blue-400 px-2 py-0.5 rounded text-xs">${escapeHtml(u.plan)}</span>`;
            
            const tdRole = document.createElement('td');
            tdRole.className = 'px-4 py-3';
            tdRole.textContent = u.rule;
            
            const tdJoined = document.createElement('td');
            tdJoined.className = 'px-4 py-3';
            tdJoined.textContent = new Date(u.join_date).toLocaleDateString();
            
            const tdActions = document.createElement('td');
            tdActions.className = 'px-4 py-3';
            
            const editBtn = document.createElement('button');
            editBtn.className = 'text-blue-400 hover:text-blue-300 mr-2';
            editBtn.innerHTML = '<i class="fas fa-edit"></i>';
            editBtn.addEventListener('click', () => editUser(u));
            
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'text-red-400 hover:text-red-300';
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
            deleteBtn.addEventListener('click', () => deleteUser(u.id));
            
            tdActions.appendChild(editBtn);
            tdActions.appendChild(deleteBtn);
            
            tr.appendChild(tdUsername);
            tr.appendChild(tdEmail);
            tr.appendChild(tdPlan);
            tr.appendChild(tdRole);
            tr.appendChild(tdJoined);
            tr.appendChild(tdActions);
            tbody.appendChild(tr);
        });
    } catch (err) {
        console.error('Failed to load users:', err);
        document.getElementById('users-table').innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-400">Failed to load users</td></tr>';
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
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken()},
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
        
        if (!plans.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-400">No plans found</td></tr>';
            return;
        }
        
        tbody.innerHTML = '';
        plans.forEach(p => {
            const tr = document.createElement('tr');
            tr.className = 'border-t border-gray-700/50';
            tr.innerHTML = `
                <td class="px-4 py-3 text-white">${escapeHtml(p.name)}</td>
                <td class="px-4 py-3">${p.price == 0 ? '<span class="text-green-400">Free</span>' : '$' + parseFloat(p.price).toFixed(2)}</td>
                <td class="px-4 py-3">${p.price == 0 ? '&infin;' : (parseInt(p.duration_days) || 30) + 'd'}</td>
                <td class="px-4 py-3">${parseInt(p.max_concurrents)}</td>
                <td class="px-4 py-3">${parseInt(p.max_seconds)}s</td>
                <td class="px-4 py-3">${p.premium ? '<span class="text-green-400">Yes</span>' : '<span class="text-gray-500">No</span>'}</td>
                <td class="px-4 py-3">${p.api_access ? '<span class="text-green-400">Yes</span>' : '<span class="text-gray-500">No</span>'}</td>
                <td class="px-4 py-3"></td>
            `;
            const actionsCell = tr.querySelector('td:last-child');
            const editBtn = document.createElement('button');
            editBtn.className = 'text-blue-400 hover:text-blue-300 mr-2';
            editBtn.innerHTML = '<i class="fas fa-edit"></i>';
            editBtn.addEventListener('click', () => editPlan(p));
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'text-red-400 hover:text-red-300';
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
            deleteBtn.addEventListener('click', () => deletePlan(p.id));
            actionsCell.appendChild(editBtn);
            actionsCell.appendChild(deleteBtn);
            tbody.appendChild(tr);
        });
    } catch (err) {
        console.error('Failed to load plans:', err);
        document.getElementById('plans-table').innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-400">Failed to load plans</td></tr>';
    }
}

function showAddPlanModal() {
    currentEditType = 'plan-add';
    const fields = document.getElementById('modal-fields');
    fields.innerHTML = '';
    fields.appendChild(createField('Name', 'name', 'text', '', {required: true}));
    fields.appendChild(createField('Description', 'description', 'text', ''));
    fields.appendChild(createField('Price (USD)', 'price', 'number', '0'));
    fields.appendChild(createField('Duration (days)', 'duration_days', 'number', '30'));
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
    fields.appendChild(createField('Description', 'description', 'text', plan.description || ''));
    fields.appendChild(createField('Price (USD)', 'price', 'number', plan.price || 0));
    fields.appendChild(createField('Duration (days)', 'duration_days', 'number', plan.duration_days || 30));
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
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken()},
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
        
        if (!methods.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-400">No methods found</td></tr>';
            return;
        }
        
        tbody.innerHTML = '';
        methods.forEach(m => {
            const tr = document.createElement('tr');
            tr.className = 'border-t border-gray-700/50';
            tr.innerHTML = `
                <td class="px-4 py-3 text-white">${escapeHtml(m.name)}</td>
                <td class="px-4 py-3">${escapeHtml(m.description)}</td>
                <td class="px-4 py-3">${m.layer4 ? '<span class="text-green-400">Yes</span>' : '<span class="text-gray-500">No</span>'}</td>
                <td class="px-4 py-3">${m.layer7 ? '<span class="text-green-400">Yes</span>' : '<span class="text-gray-500">No</span>'}</td>
                <td class="px-4 py-3">${m.premium ? '<span class="text-yellow-400">Yes</span>' : '<span class="text-gray-500">No</span>'}</td>
                <td class="px-4 py-3"></td>
            `;
            const actionsCell = tr.querySelector('td:last-child');
            const editBtn = document.createElement('button');
            editBtn.className = 'text-blue-400 hover:text-blue-300 mr-2';
            editBtn.innerHTML = '<i class="fas fa-edit"></i>';
            editBtn.addEventListener('click', () => editMethod(m));
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'text-red-400 hover:text-red-300';
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
            deleteBtn.addEventListener('click', () => deleteMethod(m.id));
            actionsCell.appendChild(editBtn);
            actionsCell.appendChild(deleteBtn);
            tbody.appendChild(tr);
        });
    } catch (err) {
        console.error('Failed to load methods:', err);
        document.getElementById('methods-table').innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-400">Failed to load methods</td></tr>';
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
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken()},
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
        fd.append('csrf_token', getCsrfToken());
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
            options.headers = {'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken()};
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

// Helper - XSS-safe escaping (#4)
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
}

// =================== ORDERS ===================
let allOrders = [];
let currentOrderFilter = 'all';

async function loadOrders() {
    try {
        const res = await fetch('api/orders.php');
        const orders = await res.json();
        allOrders = orders;

        // Update badge
        const pending = orders.filter(o => o.status === 'pending').length;
        const badge = document.getElementById('orders-badge');
        if (pending > 0) {
            badge.textContent = pending;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }

        renderOrders();
    } catch (err) {
        console.error('Failed to load orders:', err);
        document.getElementById('orders-table').innerHTML = '<tr><td colspan="7" class="text-center py-8 text-red-400">Failed to load orders</td></tr>';
    }
}

function filterOrders(status) {
    currentOrderFilter = status;
    ['all', 'pending', 'approved', 'rejected'].forEach(s => {
        const btn = document.getElementById('order-filter-' + s);
        btn.className = s === status
            ? 'text-xs px-3 py-1.5 rounded-lg bg-blue-600 text-white transition'
            : 'text-xs px-3 py-1.5 rounded-lg bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition';
    });
    renderOrders();
}

function renderOrders() {
    const tbody = document.getElementById('orders-table');
    const filtered = currentOrderFilter === 'all' ? allOrders : allOrders.filter(o => o.status === currentOrderFilter);

    if (!filtered.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-gray-400">No ${currentOrderFilter === 'all' ? '' : currentOrderFilter + ' '}orders found</td></tr>`;
        return;
    }

    const statusColors = { pending: 'text-yellow-400', approved: 'text-green-400', rejected: 'text-red-400' };
    const statusIcons = { pending: 'fa-clock', approved: 'fa-check-circle', rejected: 'fa-times-circle' };

    tbody.innerHTML = '';
    filtered.forEach(o => {
        const tr = document.createElement('tr');
        tr.className = 'border-t border-gray-700/50';
        tr.innerHTML = `
            <td class="px-4 py-3 font-mono text-xs text-gray-300">${escapeHtml(o.id)}</td>
            <td class="px-4 py-3 text-white">${escapeHtml(o.username)}</td>
            <td class="px-4 py-3"><span class="bg-blue-600/20 text-blue-400 px-2 py-0.5 rounded text-xs">${escapeHtml(o.plan_name)}</span></td>
            <td class="px-4 py-3 text-xs">${escapeHtml(o.amount)} ${escapeHtml(o.crypto)}<br><span class="text-gray-500">$${parseFloat(o.price_usd).toFixed(2)}</span></td>
            <td class="px-4 py-3">
                <span class="flex items-center gap-1 text-sm ${statusColors[o.status] || 'text-gray-400'}">
                    <i class="fas ${statusIcons[o.status] || 'fa-question-circle'}"></i>
                    ${escapeHtml(o.status.charAt(0).toUpperCase() + o.status.slice(1))}
                </span>
            </td>
            <td class="px-4 py-3 text-xs text-gray-400">${new Date(o.created_at).toLocaleString()}</td>
            <td class="px-4 py-3"></td>
        `;

        const actionsCell = tr.querySelector('td:last-child');

        if (o.status === 'pending') {
            const approveBtn = document.createElement('button');
            approveBtn.className = 'text-green-400 hover:text-green-300 mr-2';
            approveBtn.title = 'Approve';
            approveBtn.innerHTML = '<i class="fas fa-check"></i>';
            approveBtn.addEventListener('click', () => handleOrder(o.id, 'approve'));

            const rejectBtn = document.createElement('button');
            rejectBtn.className = 'text-red-400 hover:text-red-300 mr-2';
            rejectBtn.title = 'Reject';
            rejectBtn.innerHTML = '<i class="fas fa-times"></i>';
            rejectBtn.addEventListener('click', () => handleOrder(o.id, 'reject'));

            actionsCell.appendChild(approveBtn);
            actionsCell.appendChild(rejectBtn);
        }

        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'text-gray-500 hover:text-red-400';
        deleteBtn.title = 'Delete';
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
        deleteBtn.addEventListener('click', () => deleteOrder(o.id));
        actionsCell.appendChild(deleteBtn);

        tbody.appendChild(tr);
    });
}

async function handleOrder(id, action) {
    const label = action === 'approve' ? 'approve' : 'reject';
    if (!confirm(`Are you sure you want to ${label} this order?`)) return;
    try {
        const res = await fetch('api/orders.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
            body: JSON.stringify({ id, action })
        });
        const data = await res.json();
        if (res.ok) {
            showToast('Order ' + (action === 'approve' ? 'approved' : 'rejected'), 'success');
            loadOrders();
        } else {
            showToast(data.detail || 'Failed', 'error');
        }
    } catch (err) {
        showToast('Connection error', 'error');
    }
}

async function deleteOrder(id) {
    if (!confirm('Delete this order?')) return;
    try {
        const res = await fetch('api/orders.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken() },
            body: JSON.stringify({ id })
        });
        if (res.ok) {
            showToast('Order deleted', 'success');
            loadOrders();
        } else {
            const data = await res.json();
            showToast(data.detail || 'Failed to delete', 'error');
        }
    } catch (err) {
        showToast('Connection error', 'error');
    }
}

// Load initial data
loadUsers();
loadPlans();
loadOrders();
loadMethods();
