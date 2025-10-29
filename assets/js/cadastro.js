document.addEventListener('DOMContentLoaded', function() {
    if (typeof require !== 'undefined') {
        require(['core/ajax', 'core/notification'], function(Ajax, Notification) {
            const form = document.getElementById('cadastro-form');
            const addRowBtn = document.getElementById('add-row');
            const tableBody = document.getElementById('cadastro-rows');
            const resultBox = document.getElementById('resultado');

            function createInput(type, placeholder, required) {
                const input = document.createElement('input');
                input.type = type;
                input.placeholder = placeholder;
                input.dataset.placeholder = placeholder;
                if (required) {
                    input.required = true;
                }
                input.className = 'form-control';
                return input;
            }

            function addRow(defaults = {}) {
                const tr = document.createElement('tr');

                const firstnameTd = document.createElement('td');
                firstnameTd.dataset.title = 'Nome';
                const firstname = createInput('text', 'Nome', true);
                firstname.value = defaults.firstname || '';
                firstname.dataset.field = 'firstname';
                firstnameTd.appendChild(firstname);

                const lastnameTd = document.createElement('td');
                lastnameTd.dataset.title = 'Sobrenome';
                const lastname = createInput('text', 'Sobrenome', true);
                lastname.value = defaults.lastname || '';
                lastname.dataset.field = 'lastname';
                lastnameTd.appendChild(lastname);

                const emailTd = document.createElement('td');
                emailTd.dataset.title = 'E-mail / Username';
                const email = createInput('email', 'email@exemplo.com', true);
                email.value = defaults.email || defaults.username || '';
                email.dataset.field = 'email';
                emailTd.appendChild(email);

                const coursesTd = document.createElement('td');
                coursesTd.dataset.title = 'Cursos';
                const courses = createInput('text', '137,curso-curto', false);
                courses.value = defaults.courses || '';
                courses.dataset.field = 'courses';
                coursesTd.appendChild(courses);

                const actionsTd = document.createElement('td');
                actionsTd.dataset.title = 'Ações';
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn-danger';
                removeBtn.textContent = 'Remover';
                removeBtn.addEventListener('click', function() {
                    tr.remove();
                    if (!tableBody.querySelector('tr')) {
                        addRow();
                    }
                });
                actionsTd.appendChild(removeBtn);

                tr.appendChild(firstnameTd);
                tr.appendChild(lastnameTd);
                tr.appendChild(emailTd);
                tr.appendChild(coursesTd);
                tr.appendChild(actionsTd);

                tableBody.appendChild(tr);
            }

            function gatherRows() {
                const rows = [];
                tableBody.querySelectorAll('tr').forEach(function(tr) {
                    const record = {};
                    tr.querySelectorAll('input[data-field]').forEach(function(input) {
                        const field = input.dataset.field;
                        const value = input.value.trim();
                        if (value !== '') {
                            record[field] = value;
                        }
                    });
                    rows.push(record);
                });
                return rows;
            }

            addRowBtn.addEventListener('click', function() {
                addRow();
            });

            form.addEventListener('submit', function(event) {
                event.preventDefault();
                resultBox.innerHTML = '';

                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                const rows = gatherRows().filter(function(row) {
                    return Object.keys(row).length > 0;
                });

                if (!rows.length) {
                    Notification.alert('Aviso', 'Preencha ao menos um aluno para continuar.', 'Ok');
                    return;
                }

                const invalid = rows.filter(function(row) {
                    return !row.firstname || !row.lastname || !row.email;
                });

                if (invalid.length) {
                    Notification.alert('Aviso', 'Nome, sobrenome e e-mail são obrigatórios em todas as linhas.', 'Ok');
                    return;
                }

                const payload = rows.map(function(row) {
                    const entry = {
                        username: row.email,
                        firstname: row.firstname,
                        lastname: row.lastname,
                        email: row.email
                    };

                    if (row.courses) {
                        entry.courses = row.courses.split(',').map(function(course) {
                            return course.trim();
                        }).filter(function(course) {
                            return course !== '';
                        });
                    }

                    return entry;
                });

                Ajax.call([
                    {
                        methodname: 'local_portalgestao_create_batch',
                        args: { payload: JSON.stringify(payload) }
                    }
                ])[0].then(function(res) {
                    resultBox.innerHTML = '';

                    const created = Array.isArray(res.created) ? res.created : [];
                    const skipped = Array.isArray(res.skipped) ? res.skipped : [];

                    const successBox = document.createElement('div');
                    successBox.className = 'alert alert-success';
                    successBox.textContent = 'Usuários criados: ' + (created.length ? created.join(', ') : 'Nenhum');
                    resultBox.appendChild(successBox);

                    if (skipped.length) {
                        const warningBox = document.createElement('div');
                        warningBox.className = 'alert alert-warning';
                        const list = document.createElement('ul');
                        list.className = 'mb-0';
                        skipped.forEach(function(item) {
                            const li = document.createElement('li');
                            li.textContent = item.reason + ': ' + item.row;
                            list.appendChild(li);
                        });
                        warningBox.appendChild(document.createTextNode('Linhas ignoradas:'));
                        warningBox.appendChild(list);
                        resultBox.appendChild(warningBox);
                    }

                    tableBody.innerHTML = '';
                    addRow();
                }).catch(Notification.exception);
            });

            addRow();
        });
    }
});
