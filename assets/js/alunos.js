document.addEventListener('DOMContentLoaded', function() {
    if (typeof require !== 'undefined') {
        require(['core/ajax', 'core/notification'], function(Ajax, Notification) {
            const table = document.getElementById('alunos-table-body');
            const emptyState = document.getElementById('alunos-empty');
            const pagination = document.getElementById('alunos-pagination');
            const prevBtn = document.getElementById('alunos-prev');
            const nextBtn = document.getElementById('alunos-next');
            const countLabel = document.getElementById('alunos-count');

            if (!table) {
                return;
            }

            let currentPage = 0;
            const perPage = 10;

            function toggleEmpty(show) {
                if (!emptyState) {
                    return;
                }
                if (show) {
                    emptyState.classList.remove('d-none');
                } else {
                    emptyState.classList.add('d-none');
                }
            }

            function updatePagination(usersCount, perpage, total) {
                if (!pagination) {
                    return;
                }

                if (!total) {
                    pagination.classList.add('d-none');
                    if (countLabel) {
                        countLabel.textContent = '';
                    }
                    return;
                }

                const totalPages = Math.ceil(total / perpage);
                const start = (currentPage * perpage) + 1;
                const end = (currentPage * perpage) + usersCount;

                pagination.classList.remove('d-none');
                if (countLabel) {
                    countLabel.textContent = 'Mostrando ' + start + ' - ' + end + ' de ' + total + ' aluno(s)';
                }
                if (prevBtn) {
                    prevBtn.disabled = currentPage <= 0;
                }
                if (nextBtn) {
                    nextBtn.disabled = currentPage >= (totalPages - 1);
                }
            }

            function buildCell(title, content) {
                const td = document.createElement('td');
                td.dataset.title = title;
                if (typeof content === 'string') {
                    td.innerHTML = content;
                } else {
                    td.appendChild(content);
                }
                return td;
            }

            function createActionButton(label, classes) {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = classes;
                button.textContent = label;
                return button;
            }

            function load(page) {
                if (typeof page === 'number') {
                    currentPage = Math.max(0, page);
                }

                Ajax.call([
                    {
                        methodname: 'local_portalgestao_list_company_users',
                        args: {
                            page: currentPage,
                            perpage: perPage
                        },
                    }
                ])[0].then(function(response) {
                    const users = response.users || [];
                    const total = response.total || 0;
                    const perpage = response.perpage || perPage;
                    const totalPages = total ? Math.ceil(total / perpage) : 0;

                    if (totalPages && currentPage >= totalPages) {
                        currentPage = totalPages - 1;
                        load(currentPage);
                        return;
                    }

                    table.innerHTML = '';

                    if (!users.length) {
                        toggleEmpty(true);
                        updatePagination(0, perpage, total);
                        return;
                    }

                    toggleEmpty(false);

                    users.forEach(function(u) {
                        const tr = document.createElement('tr');

                        tr.appendChild(buildCell('ID', String(u.id)));
                        tr.appendChild(buildCell('Nome', u.firstname + ' ' + u.lastname));
                        tr.appendChild(buildCell('E-mail', '<a href="mailto:' + u.email + '">' + u.email + '</a>'));
                        tr.appendChild(buildCell('Username', u.username));

                        const statusBadge = document.createElement('span');
                        statusBadge.className = 'badge-status ' + (u.suspended ? 'inactive' : 'active');
                        statusBadge.textContent = u.suspended ? 'Inativo' : 'Ativo';
                        tr.appendChild(buildCell('Status', statusBadge));

                        const actionsTd = document.createElement('td');
                        actionsTd.dataset.title = 'Ações';

                        const toggleBtn = createActionButton(
                            u.suspended ? 'Ativar' : 'Inativar',
                            'btn btn-secondary'
                        );
                        toggleBtn.dataset.action = 'toggle';
                        toggleBtn.dataset.id = u.id;
                        toggleBtn.dataset.s = u.suspended ? 0 : 1;

                        const deleteBtn = createActionButton('Excluir', 'btn-danger');
                        deleteBtn.dataset.action = 'del';
                        deleteBtn.dataset.id = u.id;

                        actionsTd.appendChild(toggleBtn);
                        actionsTd.appendChild(deleteBtn);
                        tr.appendChild(actionsTd);

                        table.appendChild(tr);
                    });

                    updatePagination(users.length, perpage, total);
                }).catch(Notification.exception);
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    if (!prevBtn.disabled) {
                        load(currentPage - 1);
                    }
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    if (!nextBtn.disabled) {
                        load(currentPage + 1);
                    }
                });
            }

            document.body.addEventListener('click', function(ev) {
                const btn = ev.target.closest('button[data-action]');
                if (!btn) {
                    return;
                }
                const id = parseInt(btn.getAttribute('data-id'), 10);
                const action = btn.getAttribute('data-action');

                if (action === 'toggle') {
                    const s = parseInt(btn.getAttribute('data-s'), 10);
                    Ajax.call([
                        {
                            methodname: 'local_portalgestao_toggle_suspend',
                            args: {userid: id, suspended: s}
                        }
                    ])[0].then(function() {
                        load(currentPage);
                    }).catch(Notification.exception);
                } else if (action === 'del') {
                    Notification.confirm(
                        'Excluir usuário',
                        'Tem certeza de que deseja remover este aluno da sua empresa?',
                        'Sim, excluir',
                        'Cancelar',
                        function() {
                            Ajax.call([
                                {
                                    methodname: 'local_portalgestao_delete_user',
                                    args: {userid: id},
                                }
                            ])[0].then(function() {
                                load(currentPage);
                            }).catch(Notification.exception);
                        }
                    );
                }
            });

            load();
        });
    }
});
