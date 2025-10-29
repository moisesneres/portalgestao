<?php
namespace local_portalgestao\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Serviço com operações relacionadas aos alunos vinculados ao gestor.
 */
class alunos_service {
    /**
     * Busca o valor do campo de perfil "empresa" do usuário (gestor/cliente).
     *
     * @param \stdClass $user Usuário alvo.
     * @param string $fieldshortname Shortname do campo de perfil personalizado.
     * @return string|null Valor encontrado ou null caso não exista.
     */
    public static function get_company_value_for_user(\stdClass $user, string $fieldshortname = 'company'): ?string {
        global $DB;
        $field = self::resolve_company_field($fieldshortname);
        if (!$field) {
            return null;
        }

        $data = $DB->get_record('user_info_data', ['userid' => $user->id, 'fieldid' => $field->id]);
        return $data ? (string) $data->data : null;
    }

    /**
     * Resolve the custom profile field that stores the company value.
     *
     * @param string $fieldshortname Preferred shortname to look up.
     * @return \stdClass|null Field record when found.
     */
    protected static function resolve_company_field(string $fieldshortname = 'company'): ?\stdClass {
        global $DB;

        $field = $DB->get_record('user_info_field', ['shortname' => $fieldshortname], '*', IGNORE_MISSING);
        if ($field) {
            return $field;
        }

        if ($fieldshortname !== 'company') {
            $field = $DB->get_record('user_info_field', ['shortname' => 'company'], '*', IGNORE_MISSING);
            if ($field) {
                return $field;
            }
        }

        return $DB->get_record('user_info_field', ['id' => 3], '*', IGNORE_MISSING) ?: null;
    }

    /**
     * Lista usuários da mesma empresa do gestor (via campo de perfil).
     *
     * @param \stdClass $manager Usuário gestor.
     * @param string $fieldshortname Shortname do campo que identifica a empresa.
     * @return array Lista de usuários encontrados.
     */
    public static function list_users_by_company(\stdClass $manager, string $fieldshortname = 'company', int $page = 0, int $perpage = 20): array {
        global $DB;
        $field = self::resolve_company_field($fieldshortname);
        if (!$field) {
            return ['users' => [], 'total' => 0, 'page' => 0, 'perpage' => max(1, $perpage)];
        }

        $company = self::get_company_value_for_user($manager, $fieldshortname);
        if ($company === null || $company === '') {
            return ['users' => [], 'total' => 0, 'page' => 0, 'perpage' => max(1, $perpage)];
        }

        $perpage = max(1, $perpage);
        $page = max(0, $page);
        $offset = $page * $perpage;

        $params = [
            'fieldid' => $field->id,
            'company' => $company,
            'selfid' => $manager->id,
        ];

        $fromsql = "
                FROM {user} u
                JOIN {user_info_data} d ON d.userid = u.id AND d.fieldid = :fieldid
               WHERE d.data = :company
                 AND u.deleted = 0
                 AND u.id <> :selfid
        ";

        $total = (int) $DB->count_records_sql('SELECT COUNT(1) ' . $fromsql, $params);

        if ($total === 0) {
            return ['users' => [], 'total' => 0, 'page' => 0, 'perpage' => $perpage];
        }

        $records = $DB->get_records_sql(
            'SELECT u.id, u.firstname, u.lastname, u.email, u.username, u.suspended ' . $fromsql . ' ORDER BY u.lastname, u.firstname, u.id',
            $params,
            $offset,
            $perpage
        );

        return [
            'users' => array_values($records),
            'total' => $total,
            'page' => $page,
            'perpage' => $perpage,
        ];
    }

    /**
     * Cria um usuário simples (auth manual) e retorna o ID.
     *
     * @param array $u Dados básicos do usuário.
     * @return int ID do usuário criado.
     */
    public static function create_user(array $u, ?string $companyvalue = null): int {
        global $CFG, $DB;
        require_once($CFG->libdir . '/moodlelib.php');
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/user/editlib.php');

        $user = new \stdClass();
        $user->auth = 'manual';
        $user->username = $u['username'];
        $user->firstname = $u['firstname'];
        $user->lastname  = $u['lastname'];
        $user->email     = $u['email'];
        $user->password  = generate_password(12);
        $user->suspended = 0;
        $user->confirmed = 1;

        $userid = user_create_user($user, false, false);

        if ($companyvalue !== null && $companyvalue !== '') {
            self::set_company_value_for_user($userid, $companyvalue);
        }

        $created = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        setnew_password_and_mail($created);

        if (function_exists('useredit_force_password_change')) {
            \useredit_force_password_change($userid);
        } else {
            set_user_preference('auth_forcepasswordchange', 1, $userid);
            \core_user::reset_login_hash($userid);
        }

        return $userid;
    }

    /**
     * Atualiza ou cria o valor do campo de perfil de empresa para um usuário.
     *
     * @param int $userid ID do usuário.
     * @param string $company Valor da empresa.
     * @param string $fieldshortname Shortname do campo de perfil.
     * @return void
     */
    protected static function set_company_value_for_user(int $userid, string $company, string $fieldshortname = 'company'): void {
        global $DB;
        $field = self::resolve_company_field($fieldshortname);
        if (!$field) {
            return;
        }

        $record = $DB->get_record('user_info_data', ['userid' => $userid, 'fieldid' => $field->id]);
        if ($record) {
            $record->data = $company;
            $record->dataformat = FORMAT_PLAIN;
            $DB->update_record('user_info_data', $record);
        } else {
            $record = new \stdClass();
            $record->userid = $userid;
            $record->fieldid = $field->id;
            $record->data = $company;
            $record->dataformat = FORMAT_PLAIN;
            $DB->insert_record('user_info_data', $record);
        }
    }

    /**
     * Matricula em curso pelo código (ID ou shortname) usando enrol_manual (role estudante padrão).
     *
     * @param int $userid ID do usuário a ser matriculado.
     * @param string $code Código do curso (ID numérico ou shortname).
     * @param string|null $groupname Nome do grupo para vincular o aluno.
     * @return bool Verdadeiro em caso de sucesso.
     */
    public static function enrol_in_course_code(int $userid, string $code, ?string $groupname = null): bool {
        global $DB, $CFG, $USER;

        $code = trim($code);
        if ($code === '') {
            return false;
        }

        $course = null;
        if (ctype_digit($code)) {
            $course = $DB->get_record('course', ['id' => (int) $code], '*', IGNORE_MISSING);
        }
        if (!$course) {
            $course = $DB->get_record('course', ['shortname' => $code], '*', IGNORE_MISSING);
        }
        if (!$course) {
            return false;
        }

        require_once($CFG->dirroot . '/enrol/manual/lib.php');
        require_once($CFG->libdir . '/grouplib.php');
        require_once($CFG->dirroot . '/group/lib.php');
        require_once($CFG->libdir . '/moodlelib.php');

        $plugin = enrol_get_plugin('manual');
        if (!$plugin) {
            return false;
        }

        $enrol = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', IGNORE_MISSING);
        if (!$enrol) {
            $currentuser = $USER;
            $admin = get_admin();
            \core\session\manager::set_user($admin);
            try {
                $enrolid = $plugin->add_default_instance($course);
            } finally {
                \core\session\manager::set_user($currentuser);
            }
            if (!$enrolid) {
                return false;
            }
            $enrol = $DB->get_record('enrol', ['id' => $enrolid], '*', MUST_EXIST);
        }

        $roleid = self::resolve_student_roleid($enrol);
        if (!$roleid) {
            return false;
        }

        $enrolled = $DB->record_exists('user_enrolments', ['userid' => $userid, 'enrolid' => $enrol->id]);
        if (!$enrolled) {
            try {
                $currentuser = $USER;
                $admin = get_admin();
                \core\session\manager::set_user($admin);
                try {
                    $plugin->enrol_user($enrol, $userid, $roleid);
                } finally {
                    \core\session\manager::set_user($currentuser);
                }
            } catch (\Throwable $e) {
                debugging('Falha ao matricular usuário: ' . $e->getMessage(), DEBUG_DEVELOPER);
                return false;
            }
            $enrolled = $DB->record_exists('user_enrolments', ['userid' => $userid, 'enrolid' => $enrol->id]);
        }

        if ($enrolled && $groupname !== null && $groupname !== '') {
            $group = $DB->get_record('groups', ['courseid' => $course->id, 'name' => $groupname], '*', IGNORE_MISSING);
            if (!$group) {
                $group = new \stdClass();
                $group->courseid = $course->id;
                $group->name = $groupname;
                $group->id = groups_create_group($group);
            }
            if ($group && !$DB->record_exists('groups_members', ['groupid' => $group->id, 'userid' => $userid])) {
                groups_add_member($group->id, $userid);
            }
        }

        return $enrolled;
    }

    /**
     * Resolve o papel (role) utilizado na matrícula manual.
     *
     * @param \stdClass|null $enrol Instância de matrícula manual.
     * @return int|null ID do papel encontrado ou null se não disponível.
     */
    protected static function resolve_student_roleid(?\stdClass $enrol): ?int {
        global $DB;

        if ($enrol && !empty($enrol->roleid)) {
            return (int) $enrol->roleid;
        }

        $configrole = get_config('enrol_manual', 'roleid');
        if (!empty($configrole)) {
            return (int) $configrole;
        }

        $role = $DB->get_record('role', ['shortname' => 'student'], 'id', IGNORE_MISSING);
        if ($role) {
            return (int) $role->id;
        }

        $role = $DB->get_record('role', ['archetype' => 'student'], 'id', IGNORE_MISSING);
        if ($role) {
            return (int) $role->id;
        }

        return null;
    }

    /**
     * Suspende/reativa usuário.
     *
     * @param int $userid ID do usuário.
     * @param int $suspended Flag de suspensão (0/1).
     * @return bool Verdadeiro em caso de sucesso.
     */
    public static function set_suspended(int $userid, int $suspended): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        $user->suspended = $suspended ? 1 : 0;
        user_update_user($user, false, false);

        return true;
    }

    /**
     * Exclui usuário.
     *
     * @param int $userid ID do usuário.
     * @return bool Verdadeiro em caso de sucesso.
     */
    public static function delete_user(int $userid): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        return delete_user($user);
    }
}
