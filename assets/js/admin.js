// NetStress - Admin Panel JavaScript

let currentEditId = null;
let currentEditType = null;
let loadedPlanNames = []; // populated by loadPlans() for user modals
const UNCATEGORIZED_LABEL = 'Uncategorized (legacy)';

// Get CSRF token from page
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// Tab switching
function switchAdminTab(tab) {
    document.querySelectorAll('.admin-tab-content').forEach(el => el.classList.add('hidden'));
    document.getElementById('admin-' + tab).classList.remove('hidden');
    
    ['users', 'plans', 'orders', 'attacks', 'methods', 'categories', 'servers', 'blacklist'].forEach(t => {
        const btn = document.getElementById('admin-tab-' + t);
        if (!btn) return;
        btn.className = t === tab
            ? 'flex items-center gap-2 px-4 py-2 rounded-lg font-medium bg-blue-600 text-white transition'
            : 'flex items-center gap-2 px-4 py-2 rounded-lg font-medium bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition';
    });

    // Lazy-load new tabs on first open
    if (tab === 'attacks' && !window._adminAttacksLoaded) {
        window._adminAttacksLoaded = true;
        loadAdminAttacks();
    }
    if (tab === 'blacklist' && !window._blacklistLoaded) {
        window._blacklistLoaded = true;
        loadBlacklist();
    }
    if (tab === 'categories' && !window._categoriesLoaded) {
        window._categoriesLoaded = true;
        loadCategories();
    }
}

// Modal functions
function openModal(title) {
    document.getElementById('modal-title').textContent = title;
    const modal = document.getElementById('admin-modal');
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
}

function closeModal() {
    const modal = document.getElementById('admin-modal');
    modal.classList.add('hidden');
    modal.style.display = '';
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
let allUsersData = [];

async function loadUsers() {
    try {
        const res = await fetch('api/users.php');
        allUsersData = await res.json();
        renderUsers(allUsersData);
    } catch (err) {
        console.error('Failed to load users:', err);
        document.getElementById('users-table').innerHTML = '<tr><td colspan="5" class="text-center py-8 text-red-400">Failed to load users</td></tr>';
    }
}

function filterUsers(query) {
    const q = query.toLowerCase();
    renderUsers(allUsersData.filter(u =>
        u.username.toLowerCase().includes(q) ||
        (u.plan || '').toLowerCase().includes(q) ||
        (u.role || '').toLowerCase().includes(q)
    ));
}

function renderUsers(users) {
    const tbody = document.getElementById('users-table');
    if (!users.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-400">No users found</td></tr>';
        return;
    }
    
    // XSS-safe rendering: use textContent instead of innerHTML for user data
    tbody.innerHTML = '';
        users.forEach(u => {
            const tr = document.createElement('tr');
            tr.className = 'border-t border-gray-700/50';
            
            const tdUsername = document.createElement('td');
            tdUsername.className = 'px-4 py-3 text-white';
            tdUsername.textContent = u.username;
            
            const tdPlan = document.createElement('td');
            tdPlan.className = 'px-4 py-3';
            tdPlan.innerHTML = `<span class="bg-blue-600/20 text-blue-400 px-2 py-0.5 rounded text-xs">${escapeHtml(u.plan)}</span>`;
            
            const tdRole = document.createElement('td');
            tdRole.className = 'px-4 py-3 hidden sm:table-cell';
            tdRole.textContent = u.role;
            
            const tdJoined = document.createElement('td');
            tdJoined.className = 'px-4 py-3 hidden sm:table-cell';
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
            tr.appendChild(tdPlan);
            tr.appendChild(tdRole);
            tr.appendChild(tdJoined);
            tr.appendChild(tdActions);
            tbody.appendChild(tr);
        });
}

function showAddUserModal() {
    currentEditType = 'user-add';
    const fields = document.getElementById('modal-fields');
    fields.innerHTML = '';
    const planChoices = loadedPlanNames.length ? loadedPlanNames : ['Starter'];
    fields.appendChild(createField('Username', 'username', 'text', '', {required: true}));
    fields.appendChild(createField('Password', 'password', 'password', '', {required: true}));
    fields.appendChild(createField('Plan', 'plan', 'select', planChoices[0], {choices: planChoices}));
    fields.appendChild(createField('Role', 'role', 'select', 'user', {choices: ['user', 'admin']}));
    fields.appendChild(createField('Max Concurrents', 'max_concurrents', 'number', '1'));
    fields.appendChild(createField('Max Seconds', 'max_seconds', 'number', '60'));
    openModal('Add User');
}

function editUser(user) {
    currentEditType = 'user-edit';
    currentEditId = user.id;
    const fields = document.getElementById('modal-fields');
    fields.innerHTML = '';
    const planChoices = loadedPlanNames.length ? loadedPlanNames : ['Starter'];
    fields.appendChild(createField('Username', 'username', 'text', user.username));
    fields.appendChild(createField('Password (leave blank to keep)', 'password', 'password', ''));
    fields.appendChild(createField('Plan', 'plan', 'select', user.plan, {choices: planChoices}));
    fields.appendChild(createField('Role', 'role', 'select', user.role || user.rule, {choices: ['user', 'admin']}));
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

        // Keep plan names available for user modals
        loadedPlanNames = plans.map(p => p.name);

        const tbody = document.getElementById('plans-table');
        
        if (!plans.length) {
            tbody.innerHTML = '<tr><td colspan="9" class="text-center py-8 text-gray-400">No plans found</td></tr>';
            return;
        }
        
        tbody.innerHTML = '';
        plans.forEach(p => {
            const tr = document.createElement('tr');
            tr.className = 'border-t border-gray-700/50';
            tr.innerHTML = `
                <td class="px-4 py-3 text-white">${escapeHtml(p.name)}</td>
                <td class="px-4 py-3 hidden md:table-cell">${p.price == 0 ? '<span class="text-green-400">Free</span>' : '$' + parseFloat(p.price).toFixed(2)}</td>
                <td class="px-4 py-3 hidden lg:table-cell">${p.price == 0 ? '&infin;' : (parseInt(p.duration_days) || 30) + 'd'}</td>
                <td class="px-4 py-3 hidden sm:table-cell">${parseInt(p.max_concurrents)}</td>
                <td class="px-4 py-3 hidden sm:table-cell">${parseInt(p.max_seconds)}s</td>
                <td class="px-4 py-3">${p.premium ? '<span class="text-green-400">Yes</span>' : '<span class="text-gray-500">No</span>'}</td>
                <td class="px-4 py-3 hidden lg:table-cell">${p.api_access ? '<span class="text-green-400">Yes</span>' : '<span class="text-gray-500">No</span>'}</td>
                <td class="px-4 py-3 hidden lg:table-cell">${p.allow_schedule ? '<span class="text-yellow-400"><i class="fas fa-calendar-alt mr-1"></i>Yes</span>' : '<span class="text-gray-500">No</span>'}</td>
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
        document.getElementById('plans-table').innerHTML = '<tr><td colspan="9" class="text-center py-8 text-red-400">Failed to load plans</td></tr>';
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
    fields.appendChild(createField('Allow Scheduling', 'allow_schedule', 'checkbox', false));
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
    fields.appendChild(createField('Allow Scheduling', 'allow_schedule', 'checkbox', !!plan.allow_schedule));
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

// =================== CATEGORIES ===================
let _allCategoriesData = [];
let _cachedAdminCategories = null;
let _categoriesLayerFilter = 'all';

async function loadCategories() {
    _cachedAdminCategories = null;
    try {
        const res = await fetch('api/categories.php');
        _allCategoriesData = await res.json();
        renderCategories(_allCategoriesData);
    } catch (err) {
        console.error('Failed to load categories:', err);
        const tbody = document.getElementById('categories-table');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center py-8 text-red-400">Failed to load categories</td></tr>';
        }
    }
}

async function getAdminCategories() {
    if (_cachedAdminCategories) return _cachedAdminCategories;
    try {
        const res = await fetch('api/categories.php');
        _cachedAdminCategories = await res.json();
    } catch (e) {
        _cachedAdminCategories = [];
    }
    return _cachedAdminCategories;
}

function filterCategoriesLayer(f) {
    _categoriesLayerFilter = f;
    ['all', 'l4', 'l7'].forEach(tab => {
        const btn = document.getElementById('categories-layer-' + tab);
        if (!btn) return;
        btn.className = tab === f
            ? 'text-xs px-3 py-1.5 rounded-lg bg-blue-600 text-white transition shrink-0'
            : 'text-xs px-3 py-1.5 rounded-lg bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition shrink-0';
    });
    renderCategories(_allCategoriesData);
}

function renderCategories(categories) {
    const tbody = document.getElementById('categories-table');
    if (!tbody) return;

    const filtered = _categoriesLayerFilter === 'l4' ? categories.filter(c => c.layer === 'Layer4')
        : _categoriesLayerFilter === 'l7' ? categories.filter(c => c.layer === 'Layer7')
        : categories;

    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center py-8 text-gray-400">No categories found</td></tr>';
        return;
    }

    tbody.innerHTML = '';
    filtered.forEach(c => {
        const layerBadge = c.layer === 'Layer4'
            ? '<span class="text-blue-400 text-xs font-bold">L4</span>'
            : '<span class="text-purple-400 text-xs font-bold">L7</span>';
        const tr = document.createElement('tr');
        tr.className = 'border-t border-gray-700/50';
        tr.innerHTML = `
            <td class="px-4 py-3 text-white">${escapeHtml(c.name)}</td>
            <td class="px-4 py-3">${layerBadge}</td>
            <td class="px-4 py-3"></td>
        `;
        const actionsCell = tr.querySelector('td:last-child');
        const editBtn = document.createElement('button');
        editBtn.className = 'text-blue-400 hover:text-blue-300 mr-2';
        editBtn.innerHTML = '<i class="fas fa-edit"></i>';
        editBtn.addEventListener('click', () => editCategory(c));
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'text-red-400 hover:text-red-300';
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
        deleteBtn.addEventListener('click', () => deleteCategory(c.id));
        actionsCell.appendChild(editBtn);
        actionsCell.appendChild(deleteBtn);
        tbody.appendChild(tr);
    });
}

function showAddCategoryModal() {
    currentEditType = 'category-add';
    const fields = document.getElementById('modal-fields');
    fields.innerHTML = '';
    fields.appendChild(createField('Name', 'name', 'text', '', {required: true}));
    fields.appendChild(createField('Layer', 'layer', 'select', 'Layer4', {choices: ['Layer4', 'Layer7']}));
    openModal('Add Category');
}

function editCategory(category) {
    currentEditType = 'category-edit';
    currentEditId = category.id;
    const fields = document.getElementById('modal-fields');
    fields.innerHTML = '';
    fields.appendChild(createField('Name', 'name', 'text', category.name, {required: true}));
    fields.appendChild(createField('Layer', 'layer', 'select', category.layer || 'Layer4', {choices: ['Layer4', 'Layer7']}));
    openModal('Edit Category');
}

async function deleteCategory(id) {
    if (!confirm('Delete this category? It can only be removed if no method is using it.')) return;
    try {
        const res = await fetch('api/categories.php', {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken()},
            body: JSON.stringify({id})
        });
        if (res.ok) {
            showToast('Category deleted', 'success');
            loadCategories();
            loadMethods();
        } else {
            const data = await res.json();
            showToast(data.detail || 'Failed to delete category', 'error');
        }
    } catch (err) {
        showToast('Connection error', 'error');
    }
}

// =================== METHODS ===================
// Cached methods list for server modal dropdowns and method modals
let _cachedAdminMethods = null;
// Full methods list for the admin table + layer filtering
let _allMethodsData = [];
let _methodsLayerFilter = 'all';

async function loadMethods() {
    // Invalidate cache so server modal picks up any newly added methods
    _cachedAdminMethods = null;
    try {
        const res = await fetch('api/methods.php');
        _allMethodsData = await res.json();
        renderMethods(_allMethodsData);
    } catch (err) {
        console.error('Failed to load methods:', err);
        document.getElementById('methods-table').innerHTML = '<tr><td colspan="6" class="text-center py-8 text-red-400">Failed to load methods</td></tr>';
    }
}

function filterMethodsLayer(f) {
    _methodsLayerFilter = f;
    ['all', 'l4', 'l7'].forEach(tab => {
        const btn = document.getElementById('methods-layer-' + tab);
        if (!btn) return;
        btn.className = tab === f
            ? 'text-xs px-3 py-1.5 rounded-lg bg-blue-600 text-white transition shrink-0'
            : 'text-xs px-3 py-1.5 rounded-lg bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition shrink-0';
    });
    renderMethods(_allMethodsData);
}

function renderMethods(methods) {
    const filtered = _methodsLayerFilter === 'l4' ? methods.filter(m => m.layer4)
        : _methodsLayerFilter === 'l7' ? methods.filter(m => m.layer7)
        : methods;

    const tbody = document.getElementById('methods-table');

    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-400">No methods found</td></tr>';
        return;
    }

    tbody.innerHTML = '';
    filtered.forEach(m => {
        const layerLabel = m.layer4 && m.layer7 ? '<span class="badge badge-l4 mr-1">L4</span><span class="badge badge-l7">L7</span>'
            : m.layer4 ? '<span class="badge badge-l4">L4</span>'
            : m.layer7 ? '<span class="badge badge-l7">L7</span>'
            : '<span class="text-gray-500 text-xs">—</span>';
        const categoryBadge = m.category
            ? `<span class="badge badge-method">${escapeHtml(m.category)}</span>`
            : '<span class="text-gray-500 text-xs">—</span>';
        const premiumBadge = m.premium
            ? '<span class="badge badge-premium">⭐ Premium</span>'
            : '<span class="text-gray-500 text-xs">No</span>';
        const tr = document.createElement('tr');
        tr.className = 'border-t border-gray-700/50';
        tr.innerHTML = `
            <td class="px-4 py-3 text-white font-medium">${escapeHtml(m.name)}</td>
            <td class="px-4 py-3 hidden md:table-cell text-gray-400 text-xs">${escapeHtml(m.description)}</td>
            <td class="px-4 py-3">${layerLabel}</td>
            <td class="px-4 py-3 hidden sm:table-cell">${categoryBadge}</td>
            <td class="px-4 py-3">${premiumBadge}</td>
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
}

function getLayerCategories(categories, layer) {
    return categories
        .filter(c => c.layer === layer)
        .map(c => c.name);
}

// Build a category select filtered by layer.
function buildCategorySelect(categories, layer, currentCategory) {
    const div = document.createElement('div');
    const cats = getLayerCategories(categories, layer);
    const chosen = (cats.length > 0 && currentCategory && cats.includes(currentCategory))
        ? currentCategory
        : (cats[0] || '');
    const options = cats.length
        ? cats.map(c => `<option value="${escapeHtml(c)}" ${c === chosen ? 'selected' : ''}>${escapeHtml(c)}</option>`).join('')
        : '<option value="">No categories available</option>';
    div.innerHTML = `
        <label class="block text-sm text-gray-400 mb-1">Category</label>
        <select name="category" class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500" ${cats.length ? '' : 'disabled'}>
            ${options}
        </select>
        ${cats.length ? '' : '<p class="text-xs text-yellow-400 mt-1">Create a category for this layer first.</p>'}
    `;
    return div;
}

// Replace category select contents when the layer dropdown changes.
// Each modal open creates a fresh layerSelect element, so this listener is attached once per element.
function attachCategoryLayerListener(layerSelect, categories) {
    if (!layerSelect) return;
    layerSelect.addEventListener('change', function() {
        const wrapper = document.getElementById('method-category-wrapper');
        if (!wrapper) return;
        const curCat = wrapper.querySelector('select[name="category"]')?.value || '';
        wrapper.innerHTML = '';
        const node = buildCategorySelect(categories, this.value, curCat);
        while (node.firstChild) wrapper.appendChild(node.firstChild);
    });
}

async function showAddMethodModal() {
    currentEditType = 'method-add';
    const fields = document.getElementById('modal-fields');
    // Clearing fields removes previously created elements, including their event listeners
    fields.innerHTML = '';
    fields.appendChild(createField('Name', 'name', 'text', '', {required: true}));
    fields.appendChild(createField('Description', 'description', 'text', ''));

    const allCategories = await getAdminCategories();
    // Fresh element created for each modal open — only one listener is ever attached to it
    const layerField = createField('Layer', 'layer', 'select', 'Layer4', {choices: ['Layer4', 'Layer7']});
    fields.appendChild(layerField);

    const catWrapper = document.createElement('div');
    catWrapper.id = 'method-category-wrapper';
    const catNode = buildCategorySelect(allCategories, 'Layer4', '');
    while (catNode.firstChild) catWrapper.appendChild(catNode.firstChild);
    fields.appendChild(catWrapper);

    fields.appendChild(createField('Premium', 'premium', 'checkbox', false));
    attachCategoryLayerListener(layerField.querySelector('select[name="layer"]'), allCategories);
    openModal('Add Method');
}

async function editMethod(method) {
    currentEditType = 'method-edit';
    currentEditId = method.id;
    const fields = document.getElementById('modal-fields');
    // Clearing fields removes previously created elements, including their event listeners
    fields.innerHTML = '';
    fields.appendChild(createField('Name', 'name', 'text', method.name));
    fields.appendChild(createField('Description', 'description', 'text', method.description));

    const allCategories = await getAdminCategories();
    const currentLayer = method.layer7 && !method.layer4 ? 'Layer7' : 'Layer4';
    // Fresh element created for each modal open — only one listener is ever attached to it
    const layerField = createField('Layer', 'layer', 'select', currentLayer, {choices: ['Layer4', 'Layer7']});
    fields.appendChild(layerField);

    const catWrapper = document.createElement('div');
    catWrapper.id = 'method-category-wrapper';
    const catNode = buildCategorySelect(allCategories, currentLayer, method.category || '');
    while (catNode.firstChild) catWrapper.appendChild(catNode.firstChild);
    fields.appendChild(catWrapper);

    fields.appendChild(createField('Premium', 'premium', 'checkbox', method.premium));
    attachCategoryLayerListener(layerField.querySelector('select[name="layer"]'), allCategories);
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

// =================== METHOD LAYER HELPERS ===================

// Translate 'layer' select value in a JSON data object to layer4/layer7 booleans.
function applyMethodLayerToData(data) {
    const layerVal = data.layer || 'Layer4';
    if (!data.category || !String(data.category).trim()) {
        throw new Error('Please create/select a category for the selected layer first.');
    }
    data.layer4 = layerVal === 'Layer4';
    data.layer7 = layerVal === 'Layer7';
    data.amplification = (data.category || '').toLowerCase() === 'amplification';
    data.proxy = layerVal === 'Layer7';
    delete data.layer;
}

// Translate 'layer' select value in a FormData object to layer4/layer7 booleans.
function applyMethodLayerToFormData(fd) {
    const layerVal = fd.get('layer') || 'Layer4';
    if (!fd.get('category') || !String(fd.get('category')).trim()) {
        throw new Error('Please create/select a category for the selected layer first.');
    }
    fd.delete('layer');
    fd.set('layer4', layerVal === 'Layer4');
    fd.set('layer7', layerVal === 'Layer7');
    fd.set('amplification', (fd.get('category') || '').toLowerCase() === 'amplification');
    fd.set('proxy', layerVal === 'Layer7');
}

// =================== FORM SUBMIT ===================
document.getElementById('modal-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const data = {};
    
    // Get all field values
    document.querySelectorAll('#modal-fields input, #modal-fields select').forEach(el => {
        if (el.type === 'checkbox') {
            // Multi-value checkboxes (name ending in []) are handled separately below
            if (!el.name.endsWith('[]')) {
                data[el.name] = el.checked;
            }
        } else if (el.tagName === 'SELECT' && el.multiple) {
            // Multi-select: handled below with the multiKeys loop
        } else {
            data[el.name] = el.value;
        }
    });

    // Collect multi-value checkbox arrays (e.g. methods[])
    const multiKeys = new Set();
    document.querySelectorAll('#modal-fields input[type="checkbox"][name$="[]"]').forEach(cb => {
        multiKeys.add(cb.name);
    });
    // Collect multi-select (select[multiple]) arrays
    document.querySelectorAll('#modal-fields select[multiple]').forEach(sel => {
        multiKeys.add(sel.name);
    });
    multiKeys.forEach(name => {
        const key = name.replace(/\[\]$/, '');
        data[key] = [];
        document.querySelectorAll(`#modal-fields input[type="checkbox"][name="${CSS.escape(name)}"]:checked`).forEach(cb => {
            data[key].push(cb.value);
        });
        document.querySelectorAll(`#modal-fields select[multiple][name="${CSS.escape(name)}"] option:checked`).forEach(opt => {
            data[key].push(opt.value);
        });
    });
    
    const [type, action] = currentEditType.split('-');

    // Special handling for method: translate 'layer' select to layer4/layer7 booleans,
    // and derive amplification/proxy automatically.
    if (type === 'method') {
        try {
            applyMethodLayerToData(data);
        } catch (err) {
            showToast(err.message || 'Invalid method category', 'error');
            return;
        }
    }

    // Special handling for blacklist-add (maps to api/blacklist.php)
    if (type === 'blacklist' && action === 'add') {
        const fd = new FormData();
        document.querySelectorAll('#modal-fields input, #modal-fields select').forEach(el => {
            fd.append(el.name, el.value);
        });
        fd.append('csrf_token', getCsrfToken());
        try {
            const res = await fetch('api/blacklist.php', { method: 'POST', body: fd });
            if (res.ok) {
                showToast('Blacklist entry added', 'success');
                closeModal();
                loadBlacklist();
            } else {
                const d = await res.json();
                showToast(d.detail || 'Failed', 'error');
            }
        } catch (err) {
            showToast('Connection error', 'error');
        }
        return;
    }

    let url, method, body;
    
    if (action === 'add') {
        url = type === 'category' ? 'api/categories.php' : `api/${type}s.php`;
        method = 'POST';
        const fd = new FormData();
        // Collect regular input/select values
        document.querySelectorAll('#modal-fields input, #modal-fields select').forEach(el => {
            if (el.type === 'checkbox') {
                if (!el.name.endsWith('[]')) {
                    fd.append(el.name, el.checked);
                }
            } else if (el.tagName === 'SELECT' && el.multiple) {
                // handled below
            } else {
                fd.append(el.name, el.value);
            }
        });
        // Collect multi-select selected options
        document.querySelectorAll('#modal-fields select[multiple]').forEach(sel => {
            const key = sel.name.replace(/\[\]$/, '');
            Array.from(sel.selectedOptions).forEach(opt => fd.append(key + '[]', opt.value));
        });
        // Collect textarea values
        document.querySelectorAll('#modal-fields textarea').forEach(el => {
            fd.append(el.name, el.value);
        });
        fd.append('csrf_token', getCsrfToken());
        // For method-add: translate 'layer' select to layer4/layer7 booleans
        if (type === 'method') {
            try {
                applyMethodLayerToFormData(fd);
            } catch (err) {
                showToast(err.message || 'Invalid method category', 'error');
                return;
            }
        }
        body = fd;
    } else {
        url = type === 'category' ? 'api/categories.php' : `api/${type}s.php`;
        method = 'PUT';
        data.id = currentEditId;
        // Also collect textareas for edit (PUT sends JSON)
        document.querySelectorAll('#modal-fields textarea').forEach(el => {
            data[el.name] = el.value;
        });
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
            else if (type === 'category') {
                loadCategories();
                loadMethods();
            }
            else if (type === 'server') loadServers();
            else if (type === 'blacklist') loadBlacklist();
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
        document.getElementById('orders-table').innerHTML = '<tr><td colspan="8" class="text-center py-8 text-red-400">Failed to load orders</td></tr>';
    }
}

let orderSearchQuery = '';

function filterOrdersSearch(query) {
    orderSearchQuery = query.toLowerCase();
    renderOrders();
}

function filterOrders(status) {
    currentOrderFilter = status;
    ['all', 'pending', 'approved', 'rejected'].forEach(s => {
        const btn = document.getElementById('order-filter-' + s);
        if (!btn) return;
        btn.className = s === status
            ? 'text-xs px-3 py-1.5 rounded-lg bg-blue-600 text-white transition'
            : 'text-xs px-3 py-1.5 rounded-lg bg-gray-700/50 text-gray-300 hover:bg-gray-700 transition';
    });
    renderOrders();
}

function renderOrders() {
    const tbody = document.getElementById('orders-table');
    let filtered = currentOrderFilter === 'all' ? allOrders : allOrders.filter(o => o.status === currentOrderFilter);
    if (orderSearchQuery) {
        filtered = filtered.filter(o =>
            (o.username || '').toLowerCase().includes(orderSearchQuery) ||
            (o.plan_name || '').toLowerCase().includes(orderSearchQuery) ||
            (o.id || '').toLowerCase().includes(orderSearchQuery)
        );
    }

    if (!filtered.length) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-gray-400">No ${currentOrderFilter === 'all' ? '' : currentOrderFilter + ' '}orders found</td></tr>`;
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
            <td class="px-4 py-3 text-xs hidden md:table-cell">${escapeHtml(o.amount)} ${escapeHtml(o.crypto)}<br><span class="text-gray-500">$${parseFloat(o.price_usd).toFixed(2)}</span></td>
            <td class="px-4 py-3 font-mono text-xs text-gray-500 hidden lg:table-cell">${o.tx_hash ? escapeHtml(String(o.tx_hash).substring(0, 20)) + '…' : '—'}</td>
            <td class="px-4 py-3">
                <span class="flex items-center gap-1 text-sm ${statusColors[o.status] || 'text-gray-400'}">
                    <i class="fas ${statusIcons[o.status] || 'fa-question-circle'}"></i>
                    ${escapeHtml(o.status.charAt(0).toUpperCase() + o.status.slice(1))}
                </span>
            </td>
            <td class="px-4 py-3 text-xs text-gray-400 hidden md:table-cell">${new Date(o.created_at).toLocaleString()}</td>
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
loadServers();

// =================== SERVERS ===================


async function loadServers() {
    try {
        const res = await fetch('api/servers.php');
        const servers = await res.json();
        const tbody = document.getElementById('servers-table');

        if (!servers.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-gray-400">No servers configured yet. Click "Add Server" to add a backend attack server.</td></tr>';
            return;
        }


        tbody.innerHTML = '';
        servers.forEach(s => {
            const tr = document.createElement('tr');
            tr.className = 'border-t border-gray-700/50';

            const methodsDisplay = (s.methods && s.methods.length)
                ? s.methods.slice(0, 3).map(m => `<span class="inline-block bg-gray-800 text-gray-300 text-xs rounded px-1 py-0.5 mr-1">${escapeHtml(m)}</span>`).join('') +
                  (s.methods.length > 3 ? `<span class="text-gray-500 text-xs">+${s.methods.length - 3} more</span>` : '')
                : '<span class="text-gray-500 text-xs">All</span>';

            const urlDisplay = s.api_url
                ? (() => {
                    // Mask the api_key value in the URL display to avoid showing credentials
                    const masked = s.api_url.replace(/([?&](?:key|apikey|api_key)=)[^&]+/gi, '$1***');
                    const short = masked.substring(0, 50) + (masked.length > 50 ? '…' : '');
                    return `<span class="text-xs text-gray-400 font-mono" title="(key hidden)">${escapeHtml(short)}</span>`;
                  })()
                : '<span class="text-gray-600">—</span>';

            const layerBadge = s.layer === 'Layer4'
                ? '<span class="text-blue-400 text-xs font-bold">L4</span>'
                : s.layer === 'Layer7'
                    ? '<span class="text-purple-400 text-xs font-bold">L7</span>'
                    : '<span class="text-green-400 text-xs font-bold">L4+L7</span>';

            tr.innerHTML = `
                <td class="px-4 py-3 text-white font-medium">${escapeHtml(s.name)}</td>
                <td class="px-4 py-3">${layerBadge}</td>
                <td class="px-4 py-3 hidden sm:table-cell">${methodsDisplay}</td>
                <td class="px-4 py-3 hidden lg:table-cell text-gray-300 text-xs">${s.max_slots !== undefined && s.max_slots !== null ? escapeHtml(String(s.max_slots)) : '—'}</td>
                <td class="px-4 py-3 hidden lg:table-cell">${urlDisplay}</td>
                <td class="px-4 py-3 hidden md:table-cell">${s.enabled ? '<span class="text-green-400">Yes</span>' : '<span class="text-gray-500">No</span>'}</td>
                <td class="px-4 py-3 hidden md:table-cell server-status-cell" id="server-status-${escapeHtml(s.id)}">
                    <span class="text-gray-500 text-xs"><i class="fas fa-circle-notch fa-spin mr-1"></i>Checking…</span>
                </td>
                <td class="px-4 py-3"></td>
            `;

            const actionsCell = tr.querySelector('td:last-child');

            const editBtn = document.createElement('button');
            editBtn.className = 'text-blue-400 hover:text-blue-300 mr-2';
            editBtn.innerHTML = '<i class="fas fa-edit"></i>';
            editBtn.title = 'Edit';
            editBtn.addEventListener('click', () => editServer(s));

            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'text-red-400 hover:text-red-300';
            deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
            deleteBtn.title = 'Delete';
            deleteBtn.addEventListener('click', () => deleteServer(s.id));

            actionsCell.appendChild(editBtn);
            actionsCell.appendChild(deleteBtn);
            tbody.appendChild(tr);

            // Async status check
            checkServerStatus(s.id);
        });
    } catch (err) {
        console.error('Failed to load servers:', err);
        const tbody = document.getElementById('servers-table');
        if (tbody) tbody.innerHTML = '<tr><td colspan="8" class="text-center py-8 text-red-400">Failed to load servers</td></tr>';
    }
}

async function checkServerStatus(id) {
    const cell = document.getElementById('server-status-' + id);
    if (!cell) return;
    try {
        const res = await fetch(`api/servers.php?action=check&id=${encodeURIComponent(id)}`);
        const data = await res.json();
        if (data.online) {
            cell.innerHTML = '<span class="flex items-center gap-1 text-green-400 text-xs"><span class="status-dot status-live"></span>Online</span>';
        } else {
            cell.innerHTML = '<span class="flex items-center gap-1 text-red-400 text-xs"><span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>Offline</span>';
        }
    } catch {
        cell.innerHTML = '<span class="text-gray-600 text-xs">Unknown</span>';
    }
}


async function getAdminMethods() {
    if (_cachedAdminMethods) return _cachedAdminMethods;
    try {
        const res = await fetch('api/methods.php');
        _cachedAdminMethods = await res.json();
    } catch (e) {
        _cachedAdminMethods = [];
    }
    return _cachedAdminMethods;
}

function createMethodsMultiSelect(allMethods, selectedMethods, layer) {
    const div = document.createElement('div');
    const filtered = allMethods.filter(m => layer === 'Layer4' ? !!m.layer4 : !!m.layer7);
    const grouped = {};
    filtered.forEach(m => {
        const cat = m.category || UNCATEGORIZED_LABEL;
        if (!grouped[cat]) grouped[cat] = [];
        grouped[cat].push(m);
    });
    const optionsHtml = Object.entries(grouped)
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([cat, methods]) => {
        const opts = methods.map(m =>
            `<option value="${escapeHtml(m.name)}" ${selectedMethods.includes(m.name) ? 'selected' : ''}>${escapeHtml(m.name)}${m.premium ? ' ⭐' : ''}</option>`
        ).join('');
        return `<optgroup label="${escapeHtml(cat)}">${opts}</optgroup>`;
    }).join('');
    div.innerHTML = `
        <label class="block text-sm text-gray-400 mb-1">Methods (hold Ctrl/Cmd to select multiple; none = all)</label>
        <select name="methods[]" multiple
            class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-blue-500 text-sm" style="min-height:100px">
            ${optionsHtml}
        </select>
    `;
    return div;
}

function createEnabledToggle(checked) {
    const div = document.createElement('div');
    div.innerHTML = `
        <label class="flex items-center justify-between cursor-pointer">
            <span class="text-sm text-gray-400">Enabled</span>
            <div class="relative">
                <input type="checkbox" name="enabled" ${checked ? 'checked' : ''} class="sr-only peer" id="server-enabled-toggle">
                <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:bg-blue-600 transition-colors"></div>
                <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
            </div>
        </label>
    `;
    return div;
}

async function showAddServerModal() {
    currentEditType = 'server-add';
    const fields = document.getElementById('modal-fields');
    fields.innerHTML = '';
    fields.appendChild(createField('Name', 'name', 'text', '', {required: true}));

    const urlDiv = document.createElement('div');
    urlDiv.innerHTML = `
        <label class="block text-sm text-gray-400 mb-1">API URL (use placeholders)</label>
        <textarea name="api_url" rows="3" placeholder="http://example.com/api?host={host}&port={port}&time={time}&method={method}"
            class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 font-mono text-xs"></textarea>
    `;
    fields.appendChild(urlDiv);

    const layerField = createField('Layer', 'layer', 'select', 'Layer4', {choices: ['Layer4', 'Layer7']});
    fields.appendChild(layerField);
    fields.appendChild(createField('Max Slots', 'max_slots', 'number', '10'));

    const allMethods = await getAdminMethods();
    const methodsWrapper = document.createElement('div');
    methodsWrapper.id = 'server-methods-wrapper';
    methodsWrapper.appendChild(createMethodsMultiSelect(allMethods, [], 'Layer4'));
    fields.appendChild(methodsWrapper);
    fields.appendChild(createEnabledToggle(true));

    // Update methods list when layer changes
    const layerSelect = layerField.querySelector('select[name="layer"]');
    if (layerSelect) {
        layerSelect.addEventListener('change', function() {
            const wrapper = document.getElementById('server-methods-wrapper');
            if (!wrapper) return;
            const selected = Array.from(wrapper.querySelectorAll('select[name="methods[]"] option:checked')).map(o => o.value);
            wrapper.innerHTML = '';
            wrapper.appendChild(createMethodsMultiSelect(allMethods, selected, this.value));
        });
    }

    openModal('Add Server');
}

async function editServer(server) {
    currentEditType = 'server-edit';
    currentEditId = server.id;
    const fields = document.getElementById('modal-fields');
    fields.innerHTML = '';
    fields.appendChild(createField('Name', 'name', 'text', server.name));

    const urlDiv = document.createElement('div');
    urlDiv.innerHTML = `
        <label class="block text-sm text-gray-400 mb-1">API URL (use placeholders)</label>
        <textarea name="api_url" rows="3"
            class="w-full bg-background border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-blue-500 font-mono text-xs">${escapeHtml(server.api_url || '')}</textarea>
    `;
    fields.appendChild(urlDiv);

    const layerField = createField('Layer', 'layer', 'select', server.layer || 'Layer4', {choices: ['Layer4', 'Layer7']});
    fields.appendChild(layerField);
    fields.appendChild(createField('Max Slots', 'max_slots', 'number', server.max_slots ?? 10));

    const allMethods = await getAdminMethods();
    const methodsWrapper = document.createElement('div');
    methodsWrapper.id = 'server-methods-wrapper';
    methodsWrapper.appendChild(createMethodsMultiSelect(allMethods, server.methods || [], server.layer || 'Layer4'));
    fields.appendChild(methodsWrapper);
    fields.appendChild(createEnabledToggle(!!server.enabled));

    // Update methods list when layer changes
    const layerSelect = layerField.querySelector('select[name="layer"]');
    if (layerSelect) {
        layerSelect.addEventListener('change', function() {
            const wrapper = document.getElementById('server-methods-wrapper');
            if (!wrapper) return;
            const selected = Array.from(wrapper.querySelectorAll('select[name="methods[]"] option:checked')).map(o => o.value);
            wrapper.innerHTML = '';
            wrapper.appendChild(createMethodsMultiSelect(allMethods, selected, this.value));
        });
    }

    openModal('Edit Server');
}

async function deleteServer(id) {
    if (!confirm('Delete this server?')) return;
    try {
        const res = await fetch('api/servers.php', {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken()},
            body: JSON.stringify({id})
        });
        if (res.ok) {
            showToast('Server deleted', 'success');
            loadServers();
        } else {
            const data = await res.json();
            showToast(data.detail || 'Failed to delete', 'error');
        }
    } catch (err) {
        showToast('Connection error', 'error');
    }
}

// =================== ADMIN ATTACKS TAB ===================

let allAdminAttacks = [];

async function loadAdminAttacks() {
    try {
        const res = await fetch('api/history.php?all=1&per_page=100');
        const resp = await res.json();
        allAdminAttacks = resp.attacks ?? resp;
        // Enrich with username from user_id by joining users list
        try {
            const ur = await fetch('api/users.php');
            const users = await ur.json();
            const usersMap = {};
            users.forEach(u => { usersMap[u.id] = u.username; });
            allAdminAttacks = allAdminAttacks.map(a => ({...a, username: usersMap[a.user_id] || a.user_id}));
        } catch(_) {}
        renderAdminAttacks(allAdminAttacks);
    } catch (err) {
        document.getElementById('admin-attacks-table').innerHTML =
            '<tr><td colspan="6" class="text-center py-8 text-red-400">Failed to load attacks</td></tr>';
    }
}

function filterAdminAttacks(query) {
    const q = query.toLowerCase();
    renderAdminAttacks(allAdminAttacks.filter(a =>
        (a.username || '').toLowerCase().includes(q) ||
        (a.target || '').toLowerCase().includes(q) ||
        (a.method || '').toLowerCase().includes(q)
    ));
}

function renderAdminAttacks(attacks) {
    const tbody = document.getElementById('admin-attacks-table');
    if (!attacks.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-8 text-gray-400">No attacks found</td></tr>';
        return;
    }
    tbody.innerHTML = '';
    attacks.forEach(a => {
        const tr = document.createElement('tr');
        tr.className = 'border-t border-gray-700/50';
        tr.innerHTML = `
            <td class="px-4 py-3 text-white">${escapeHtml(a.username || a.user_id || '—')}</td>
            <td class="px-4 py-3 font-mono text-xs text-gray-300">${escapeHtml(a.target || '—')}</td>
            <td class="px-4 py-3"><span class="badge badge-method">${escapeHtml(a.method || '—')}</span></td>
            <td class="px-4 py-3 hidden sm:table-cell"><span class="${a.layer === 'Layer7' ? 'badge badge-l7' : 'badge badge-l4'}">${escapeHtml(a.layer || '—')}</span></td>
            <td class="px-4 py-3 hidden sm:table-cell text-gray-300">${escapeHtml(String(a.time || 0))}s</td>
            <td class="px-4 py-3 hidden md:table-cell text-xs text-gray-400">${a.start_time ? new Date(a.start_time).toLocaleString() : '—'}</td>
        `;
        tbody.appendChild(tr);
    });
}

// =================== BLACKLIST TAB ===================

let allBlacklist = [];

async function loadBlacklist() {
    try {
        const res = await fetch('api/blacklist.php');
        allBlacklist = await res.json();
        renderBlacklist(allBlacklist);
    } catch (err) {
        document.getElementById('blacklist-table').innerHTML =
            '<tr><td colspan="5" class="text-center py-8 text-red-400">Failed to load blacklist</td></tr>';
    }
}

function renderBlacklist(entries) {
    const tbody = document.getElementById('blacklist-table');
    if (!entries.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-8 text-gray-400">No blacklist entries. Protected targets can be added here.</td></tr>';
        return;
    }
    tbody.innerHTML = '';
    entries.forEach(e => {
        const typeColors = { ip: 'text-blue-400', cidr: 'text-yellow-400', url: 'text-purple-400' };
        const tr = document.createElement('tr');
        tr.className = 'border-t border-gray-700/50';
        tr.innerHTML = `
            <td class="px-4 py-3 font-bold text-xs ${typeColors[e.type] || 'text-gray-400'}">${escapeHtml((e.type || '').toUpperCase())}</td>
            <td class="px-4 py-3 font-mono text-sm text-white">${escapeHtml(e.value || '')}</td>
            <td class="px-4 py-3 text-xs text-gray-400 hidden sm:table-cell">${escapeHtml(e.note || '—')}</td>
            <td class="px-4 py-3 text-xs text-gray-500 hidden md:table-cell">${e.created_at ? new Date(e.created_at).toLocaleString() : '—'}</td>
            <td class="px-4 py-3"></td>
        `;
        const actionsCell = tr.querySelector('td:last-child');
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'text-red-400 hover:text-red-300';
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
        deleteBtn.title = 'Remove';
        deleteBtn.addEventListener('click', () => deleteBlacklistEntry(e.id));
        actionsCell.appendChild(deleteBtn);
        tbody.appendChild(tr);
    });
}

function showAddBlacklistModal() {
    currentEditType = 'blacklist-add';
    const fields = document.getElementById('modal-fields');
    fields.innerHTML = '';
    fields.appendChild(createField('Type', 'type', 'select', 'ip', {choices: ['ip', 'cidr', 'url']}));
    fields.appendChild(createField('Value', 'value', 'text', '', {required: true}));
    fields.appendChild(createField('Note (optional)', 'note', 'text', ''));
    openModal('Add Blacklist Entry');
}

async function deleteBlacklistEntry(id) {
    if (!confirm('Remove this entry from the blacklist?')) return;
    try {
        const res = await fetch('api/blacklist.php', {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': getCsrfToken()},
            body: JSON.stringify({id})
        });
        if (res.ok) {
            showToast('Entry removed', 'success');
            loadBlacklist();
        } else {
            const data = await res.json();
            showToast(data.detail || 'Failed to delete', 'error');
        }
    } catch (err) {
        showToast('Connection error', 'error');
    }
}
