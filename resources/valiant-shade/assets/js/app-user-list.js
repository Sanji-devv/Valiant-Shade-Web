/**
 * Page User List - Valiant Shade
 */

'use strict';

document.addEventListener('DOMContentLoaded', function (e) {
  let borderColor, bodyBg, headingColor;

  if (typeof config !== 'undefined' && config.colors) {
    borderColor = config.colors.borderColor;
    bodyBg = config.colors.bodyBg;
    headingColor = config.colors.headingColor;
  }

  const dt_user_table = document.querySelector('.datatables-users');
  const baseAppUrl = typeof baseUrl !== 'undefined' ? baseUrl : '/valiant-shade/';
  const userView = baseAppUrl + 'pages/profile-user';
  const dataUrl = baseAppUrl + 'app/user/list-data';
  const createUrl = baseAppUrl + 'app/user/create';
  const rolesUrl = baseAppUrl + 'app/roles';
  let availableRoles = [];
  const escapeHtml = value => String(value).replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char]);

  const statusObj = {
    1: { title: 'Pending', label: 'Pending', class: 'bg-label-warning' },
    2: { title: 'Active', label: 'Active', class: 'bg-label-success' },
    3: { title: 'Inactive', label: 'Inactive', class: 'bg-label-secondary' }
  };

  // Check if current authenticated user is an Admin
  let currentUserIsAdmin = typeof window.currentUserIsAdmin !== 'undefined' ? window.currentUserIsAdmin : false;

  // Users datatable
  let dt_user = null;
  if (dt_user_table) {
    dt_user = new DataTable(dt_user_table, {
      ajax: {
        url: dataUrl,
        dataSrc: function (json) {
          if (typeof json.is_admin !== 'undefined') {
            currentUserIsAdmin = json.is_admin;
            window.currentUserIsAdmin = json.is_admin;
          }
          availableRoles = json.roles || [];
          return json.data || [];
        }
      },
      columns: [
        { data: 'id' },
        { data: 'id', orderable: false, render: DataTable.render.select() },
        { data: 'full_name' },
        { data: 'role' },
        { data: 'current_character' },
        { data: 'status' },
        { data: 'id' }
      ],
      columnDefs: [
        {
          // Responsive control
          className: 'control',
          searchable: false,
          orderable: false,
          responsivePriority: 2,
          targets: 0,
          render: function () {
            return '';
          }
        },
        {
          // Checkboxes
          targets: 1,
          orderable: false,
          searchable: false,
          responsivePriority: 4,
          checkboxes: true,
          render: function () {
            return '<input type="checkbox" class="dt-checkboxes form-check-input">';
          },
          checkboxes: {
            selectAllRender: '<input type="checkbox" class="form-check-input">'
          }
        },
        {
          // User avatar + name + email
          targets: 2,
          responsivePriority: 3,
          render: function (data, type, full, meta) {
            const name = full['full_name'] || 'User';
            const email = full['email'] || '';
            const states = ['primary', 'info', 'success', 'warning', 'danger'];
            const stateNum = Math.abs((name.charCodeAt(0) || 0) % states.length);
            const state = states[stateNum];
            let initials = (name.match(/\b\w/g) || []).map(char => char.toUpperCase());
            initials = ((initials.shift() || '') + (initials.pop() || '')).toUpperCase();
            if (!initials) initials = 'VS';
            const output = '<span class="avatar-initial rounded-circle bg-label-' + state + '">' + initials + '</span>';

            return `
              <div class="d-flex justify-content-start align-items-center user-name">
                <div class="avatar-wrapper">
                  <div class="avatar avatar-sm me-4">
                    ${output}
                  </div>
                </div>
                <div class="d-flex flex-column">
                  <a href="${userView}" class="text-heading text-truncate"><span class="fw-medium">${name}</span></a>
                  <small class="text-body-secondary">${email}</small>
                </div>
              </div>
            `;
          }
        },
        {
          // Dynamic roles
          targets: 3,
          render: function (data, type, full, meta) {
            const role = full['role'] || 'Player';
            if (currentUserIsAdmin) {
              const options = availableRoles.map(item => `<option value="${escapeHtml(item.slug)}" ${item.slug === full['role_slug'] ? 'selected' : ''}>${escapeHtml(item.name)}</option>`).join('');
              return `<select class="form-select form-select-sm user-role-select" data-id="${full.id}" aria-label="Role for ${escapeHtml(role)}">${options}</select>`;
            }
            return `<span class="text-truncate d-flex align-items-center text-heading fw-semibold"><i class="icon-base ti tabler-user icon-md text-secondary me-2"></i>${escapeHtml(role)}</span>`;
          }
        },
        {
          // Aktif Karakter (Current Character)
          targets: 4,
          render: function (data, type, full, meta) {
            const character = full['current_character'];
            const userId = full['id'];
            if (character && character.trim().length > 0) {
              const clearBtn = currentUserIsAdmin ? `
                <button type="button" class="btn btn-xs btn-text-danger btn-icon ms-1 p-0 delete-character" data-id="${userId}" data-name="${character.replace(/"/g, '&quot;')}" title="Remove Active Character">
                  <i class="icon-base ti tabler-trash text-danger" style="font-size: 15px;"></i>
                </button>
              ` : '';
              return `
                <span class="d-inline-flex align-items-center text-heading fw-semibold">
                  <span class="avatar avatar-xs rounded-circle bg-label-primary d-inline-flex align-items-center justify-content-center me-2" style="width:24px;height:24px;font-size:12px;">⚔️</span>
                  <span class="text-truncate">${character}</span>
                  ${clearBtn}
                </span>
              `;
            }
            return '<span class="text-body-secondary fst-italic small">No Character</span>';
          }
        },
        {
          // Status
          targets: 5,
          render: function (data, type, full, meta) {
            const status = full['status'] || 2;
            const currentStatus = statusObj[status] || statusObj[2];

            return `
              <span class="badge ${currentStatus.class}" text-capitalized>
                ${currentStatus.label || currentStatus.title}
              </span>
            `;
          }
        },
        {
          // Actions - Only Admins can delete
          targets: -1,
          title: 'Actions',
          searchable: false,
          orderable: false,
          render: function (data, type, full, meta) {
            const userId = full['id'];
            let deleteBtnHtml = '';

            // Delete button only shown if Admin
            if (currentUserIsAdmin) {
              deleteBtnHtml = `
                <a href="javascript:;" class="btn btn-text-danger rounded-pill waves-effect btn-icon delete-record" data-id="${userId}" title="Delete">
                  <i class="icon-base ti tabler-trash icon-22px"></i>
                </a>
              `;
            }

            return `
              <div class="d-flex align-items-center">
                <a href="${userView}" class="btn btn-text-secondary rounded-pill waves-effect btn-icon me-1" title="View Profile">
                  <i class="icon-base ti tabler-eye icon-22px"></i>
                </a>
                ${deleteBtnHtml}
              </div>
            `;
          }
        }
      ],
      select: {
        style: 'multi',
        selector: 'td:nth-child(2)'
      },
      order: [[2, 'asc']],
      layout: {
        topStart: {
          rowClass: 'row m-3 my-0 justify-content-between',
          features: [
            {
              pageLength: {
                menu: [10, 25, 50, 100],
                text: '_MENU_'
              }
            }
          ]
        },
        topEnd: {
          features: [
            {
              search: {
                placeholder: 'Search User...',
                text: '_INPUT_'
              }
            },
            {
              buttons: [
                {
                  extend: 'collection',
                  className: 'btn btn-label-secondary dropdown-toggle waves-effect',
                  text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ti tabler-upload icon-xs"></i> <span class="d-none d-sm-inline-block">Export</span></span>',
                  buttons: [
                    {
                      extend: 'print',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ti tabler-printer me-1"></i>Print</span>',
                      className: 'dropdown-item',
                      exportOptions: { columns: [2, 3, 4, 5] }
                    },
                    {
                      extend: 'csv',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ti tabler-file-text me-1"></i>CSV</span>',
                      className: 'dropdown-item',
                      exportOptions: { columns: [2, 3, 4, 5] }
                    },
                    {
                      extend: 'excel',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ti tabler-file-spreadsheet me-1"></i>Excel</span>',
                      className: 'dropdown-item',
                      exportOptions: { columns: [2, 3, 4, 5] }
                    },
                    {
                      extend: 'pdf',
                      text: '<span class="d-flex align-items-center"><i class="icon-base ti tabler-file-description me-1"></i>PDF</span>',
                      className: 'dropdown-item',
                      exportOptions: { columns: [2, 3, 4, 5] }
                    },
                    {
                      extend: 'copy',
                      text: '<i class="icon-base ti tabler-copy me-1"></i>Copy',
                      className: 'dropdown-item',
                      exportOptions: { columns: [2, 3, 4, 5] }
                    }
                  ]
                },
                {
                  text: '<span class="d-flex align-items-center gap-2"><i class="icon-base ti tabler-plus icon-xs"></i> <span class="d-none d-sm-inline-block">Add New User</span></span>',
                  className: 'add-new btn btn-primary waves-effect waves-light',
                  attr: {
                    'data-bs-toggle': 'offcanvas',
                    'data-bs-target': '#offcanvasAddUser'
                  }
                }
              ]
            }
          ]
        },
        bottomStart: {
          rowClass: 'row mx-3 justify-content-between',
          features: ['info']
        },
        bottomEnd: 'paging'
      },
      language: {
        sLengthMenu: '_MENU_',
        search: '',
        searchPlaceholder: 'Search User...',
        info: 'Showing _START_ to _END_ of _TOTAL_ users',
        infoEmpty: 'No users found',
        infoFiltered: '(filtered from _MAX_ total users)',
        zeroRecords: 'No matching users found',
        paginate: {
          next: '<i class="icon-base ti tabler-chevron-right scaleX-n1-rtl icon-18px"></i>',
          previous: '<i class="icon-base ti tabler-chevron-left scaleX-n1-rtl icon-18px"></i>'
        }
      },
      responsive: {
        details: {
          display: DataTable.Responsive.display.modal({
            header: function (row) {
              const data = row.data();
              return 'User Details: ' + data['full_name'];
            }
          }),
          type: 'column',
          renderer: function (api, rowIdx, columns) {
            const data = columns
              .map(function (col) {
                return col.title !== ''
                  ? `<tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                      <td class="fw-semibold">${col.title}:</td>
                      <td>${col.data}</td>
                    </tr>`
                  : '';
              })
              .join('');

            if (data) {
              const div = document.createElement('div');
              div.classList.add('table-responsive');
              const table = document.createElement('table');
              div.appendChild(table);
              table.classList.add('table');
              const tbody = document.createElement('tbody');
              tbody.innerHTML = data;
              table.appendChild(tbody);
              return div;
            }
            return false;
          }
        }
      },
      initComplete: function () {
        const api = this.api();

        // 1. Role Filter
        const roleContainer = document.querySelector('.user_role');
        if (roleContainer) {
          const roleSelect = document.createElement('select');
          roleSelect.id = 'UserRoleFilter';
          roleSelect.className = 'form-select text-capitalize';
          roleSelect.innerHTML = '<option value="">All Roles</option>' + availableRoles.map(role => `<option value="${escapeHtml(role.name)}">${escapeHtml(role.name)}</option>`).join('');
          roleContainer.appendChild(roleSelect);
          roleSelect.addEventListener('change', () => {
            const val = roleSelect.value ? `^${roleSelect.value}$` : '';
            api.column(3).search(val, true, false).draw();
          });
        }

        // 2. Status Filter
        const statusContainer = document.querySelector('.user_status');
        if (statusContainer) {
          const statusSelect = document.createElement('select');
          statusSelect.id = 'UserStatusFilter';
          statusSelect.className = 'form-select text-capitalize';
          statusSelect.innerHTML = `
            <option value="">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Pending">Pending</option>
            <option value="Inactive">Inactive</option>
          `;
          statusContainer.appendChild(statusSelect);
          statusSelect.addEventListener('change', () => {
            const val = statusSelect.value ? statusSelect.value : '';
            api.column(5).search(val, true, false).draw();
          });
        }
      }
    });

    // Delete record event - Only Admin can delete
    document.addEventListener('click', function (e) {
      const deleteBtn = e.target.closest('.delete-record');
      if (deleteBtn) {
        e.preventDefault();

        if (!currentUserIsAdmin) {
          alert('Unauthorized action: Only Administrators can delete users.');
          return;
        }

        const userId = deleteBtn.getAttribute('data-id');
        if (!confirm('Are you sure you want to delete this user?')) {
          return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        fetch(baseAppUrl + 'app/user/' + userId, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          }
        })
          .then(res => res.json())
          .then(res => {
            if (res.success) {
              dt_user.ajax.reload();
            } else {
              alert(res.message || 'Failed to delete user.');
            }
          })
          .catch(err => {
            console.error(err);
            alert('An error occurred.');
          });
      }
    });

    // Delete character event
    document.addEventListener('click', function (e) {
      const charBtn = e.target.closest('.delete-character');
      if (charBtn) {
        e.preventDefault();
        e.stopPropagation();

        const userId = charBtn.getAttribute('data-id');
        const charName = charBtn.getAttribute('data-name') || 'this character';
        if (!confirm(`Are you sure you want to remove active character "${charName}" from this user?`)) {
          return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        fetch(baseAppUrl + 'app/user/' + userId + '/character', {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          }
        })
          .then(res => res.json())
          .then(res => {
            if (res.success) {
              dt_user.ajax.reload(null, false);
            } else {
              alert(res.message || 'Failed to remove character.');
            }
          })
          .catch(err => {
            console.error(err);
            alert('An error occurred while removing the character.');
          });
      }
    });

    document.addEventListener('change', function (event) {
      const roleSelect = event.target.closest('.user-role-select');
      if (!roleSelect) return;

      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      fetch(baseAppUrl + 'app/user/' + roleSelect.dataset.id + '/role', {
        method: 'PUT',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ role: roleSelect.value })
      }).then(response => {
        if (!response.ok) throw new Error('Could not update user role.');
        return response.json();
      }).then(() => dt_user.ajax.reload(null, false)).catch(error => {
        alert(error.message);
        dt_user.ajax.reload(null, false);
      });
    });
  }

  const roleModal = document.getElementById('roleManagementModal');
  const roleForm = document.getElementById('role-form');
  if (roleModal && roleForm) {
    const roleList = document.getElementById('role-list');
    const permissionList = document.getElementById('role-permissions');
    const roleName = document.getElementById('role-name');
    const roleId = document.getElementById('role-id');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const resetRoleForm = () => {
      roleForm.reset();
      roleId.value = '';
      roleForm.querySelectorAll('[name="permissions[]"]').forEach(input => { input.checked = false; });
    };

    const loadRoles = () => fetch(rolesUrl, { headers: { 'Accept': 'application/json' } })
      .then(response => {
        if (!response.ok) throw new Error('Unable to load roles.');
        return response.json();
      })
      .then(data => {
        permissionList.innerHTML = Object.entries(data.permissions || {}).map(([key, label]) => `
          <label class="form-check">
            <input class="form-check-input" type="checkbox" name="permissions[]" value="${escapeHtml(key)}">
            <span class="form-check-label">${escapeHtml(label)}</span>
          </label>`).join('');
        roleList.innerHTML = (data.roles || []).map(role => `
          <div class="list-group-item d-flex align-items-center justify-content-between gap-2">
            <button type="button" class="btn btn-link text-start p-0 role-edit" data-id="${role.id}">${escapeHtml(role.name)}</button>
            ${role.is_system ? '' : `<button type="button" class="btn btn-text-danger btn-icon role-delete" data-id="${role.id}" title="Delete role"><i class="icon-base ti tabler-trash"></i></button>`}
          </div>`).join('');
        roleList.dataset.roles = JSON.stringify(data.roles || []);
      });

    roleModal.addEventListener('show.bs.modal', () => loadRoles().catch(error => alert(error.message)));
    document.getElementById('new-role').addEventListener('click', resetRoleForm);

    roleList.addEventListener('click', event => {
      const editButton = event.target.closest('.role-edit');
      const deleteButton = event.target.closest('.role-delete');
      const roles = JSON.parse(roleList.dataset.roles || '[]');

      if (editButton) {
        const role = roles.find(item => String(item.id) === editButton.dataset.id);
        if (!role) return;
        resetRoleForm();
        roleId.value = role.id;
        roleName.value = role.name;
        roleForm.querySelectorAll('[name="permissions[]"]').forEach(input => { input.checked = (role.permissions || []).includes(input.value); });
      }

      if (deleteButton && confirm('Delete this role?')) {
        fetch(rolesUrl + '/' + deleteButton.dataset.id, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
          .then(response => response.json().then(data => ({ response, data })))
          .then(({ response, data }) => {
            if (!response.ok) throw new Error(data.message || 'Unable to delete role.');
            loadRoles();
            if (dt_user) dt_user.ajax.reload(null, false);
          }).catch(error => alert(error.message));
      }
    });

    roleForm.addEventListener('submit', event => {
      event.preventDefault();
      const permissions = Array.from(roleForm.querySelectorAll('[name="permissions[]"]:checked')).map(input => input.value);
      const editing = Boolean(roleId.value);
      fetch(editing ? rolesUrl + '/' + roleId.value : rolesUrl, {
        method: editing ? 'PUT' : 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: roleName.value, permissions })
      }).then(response => response.json().then(data => ({ response, data })))
        .then(({ response, data }) => {
          if (!response.ok) throw new Error(data.message || 'Unable to save role.');
          resetRoleForm();
          loadRoles();
          if (dt_user) dt_user.ajax.reload(null, false);
        }).catch(error => alert(error.message));
    });
  }

  // Layout formatting adjustments
  setTimeout(() => {
    const elementsToModify = [
      { selector: '.dt-buttons .btn', classToRemove: 'btn-secondary' },
      { selector: '.dt-search .form-control', classToRemove: 'form-control-sm' },
      { selector: '.dt-length .form-select', classToRemove: 'form-select-sm', classToAdd: 'ms-0' },
      { selector: '.dt-length', classToAdd: 'mb-md-6 mb-0' },
      {
        selector: '.dt-layout-end',
        classToRemove: 'justify-content-between',
        classToAdd: 'd-flex gap-md-4 justify-content-md-between justify-content-center gap-2 flex-wrap'
      },
      { selector: '.dt-buttons', classToAdd: 'd-flex gap-4 mb-md-0 mb-4' },
      { selector: '.dt-layout-table', classToRemove: 'row mt-2' },
      { selector: '.dt-layout-full', classToRemove: 'col-md col-12', classToAdd: 'table-responsive' }
    ];

    elementsToModify.forEach(({ selector, classToRemove, classToAdd }) => {
      document.querySelectorAll(selector).forEach(element => {
        if (classToRemove) {
          classToRemove.split(' ').forEach(className => element.classList.remove(className));
        }
        if (classToAdd) {
          classToAdd.split(' ').forEach(className => element.classList.add(className));
        }
      });
    });
  }, 100);

  // Add New User Form Validation & Submission
  const addNewUserForm = document.getElementById('addNewUserForm');
  if (addNewUserForm) {
    let fv = null;
    if (typeof FormValidation !== 'undefined') {
      fv = FormValidation.formValidation(addNewUserForm, {
        fields: {
          userFullname: {
            validators: {
              notEmpty: {
                message: 'Please enter full name'
              }
            }
          },
          userEmail: {
            validators: {
              notEmpty: {
                message: 'Please enter email address'
              },
              emailAddress: {
                message: 'Please enter a valid email address'
              }
            }
          }
        },
        plugins: {
          trigger: new FormValidation.plugins.Trigger(),
          bootstrap5: new FormValidation.plugins.Bootstrap5({
            eleValidClass: '',
            rowSelector: function (field, ele) {
              return '.form-control-validation';
            }
          }),
          submitButton: new FormValidation.plugins.SubmitButton(),
          autoFocus: new FormValidation.plugins.AutoFocus()
        }
      });
    }

    addNewUserForm.addEventListener('submit', function (e) {
      e.preventDefault();

      const proceedSubmit = () => {
        const formData = new FormData(addNewUserForm);
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        fetch(createUrl, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
          },
          body: formData
        })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              const offcanvasEl = document.getElementById('offcanvasAddUser');
              const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl) || new bootstrap.Offcanvas(offcanvasEl);
              offcanvas.hide();
              addNewUserForm.reset();
              if (dt_user) {
                dt_user.ajax.reload();
              }
            } else {
              alert(data.message || 'Failed to create user.');
            }
          })
          .catch(err => {
            console.error(err);
            alert('An error occurred while creating the user.');
          });
      };

      if (fv) {
        fv.validate().then(status => {
          if (status === 'Valid') {
            proceedSubmit();
          }
        });
      } else {
        proceedSubmit();
      }
    });
  }
});
