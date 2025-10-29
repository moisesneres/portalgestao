<?php
namespace local_portalgestao\external;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

class alunos_api extends external_api {
    private static function require_caps() {
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/portalgestao:manageusers', $context);
    }

    // LIST
    public static function list_company_users_parameters() {
        return new external_function_parameters([
            'page' => new external_value(PARAM_INT, 'Página (base zero)', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Quantidade por página', VALUE_DEFAULT, 20),
        ]);
    }

    public static function list_company_users($page = 0, $perpage = 20) {
        global $USER;
        self::require_caps();
        $params = self::validate_parameters(self::list_company_users_parameters(), ['page' => $page, 'perpage' => $perpage]);

        $result = \local_portalgestao\local\alunos_service::list_users_by_company(
            $USER,
            'company',
            $params['page'],
            $params['perpage']
        );
        $out = [];
        foreach ($result['users'] as $u) {
            $out[] = [
                'id' => (int) $u->id,
                'firstname' => $u->firstname,
                'lastname' => $u->lastname,
                'email' => $u->email,
                'username' => $u->username,
                'suspended' => (int) $u->suspended,
            ];
        }
        return [
            'users' => $out,
            'total' => (int) $result['total'],
            'page' => (int) $result['page'],
            'perpage' => (int) $result['perpage'],
        ];
    }

    public static function list_company_users_returns() {
        return new external_single_structure([
            'users' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'User ID'),
                    'firstname' => new external_value(PARAM_TEXT, 'First name'),
                    'lastname' => new external_value(PARAM_TEXT, 'Last name'),
                    'email' => new external_value(PARAM_TEXT, 'Email'),
                    'username' => new external_value(PARAM_TEXT, 'Username'),
                    'suspended' => new external_value(PARAM_INT, 'Suspended flag'),
                ])
            ),
            'total' => new external_value(PARAM_INT, 'Total users for the company'),
            'page' => new external_value(PARAM_INT, 'Current page (zero based)'),
            'perpage' => new external_value(PARAM_INT, 'Users per page'),
        ]);
    }

    // CREATE BATCH
    public static function create_batch_parameters() {
        return new external_function_parameters([
            'payload' => new external_value(PARAM_RAW, 'JSON com usuários e cursos'),
        ]);
    }

    public static function create_batch($payload) {
        global $DB, $USER;
        self::require_caps();
        $data = json_decode($payload, true);
        if (!is_array($data)) {
            throw new \invalid_parameter_exception('JSON inválido.');
        }
        $company = \local_portalgestao\local\alunos_service::get_company_value_for_user($USER, 'company');
        if ($company === null || $company === '') {
            throw new \moodle_exception('missingcompany', 'local_portalgestao');
        }
        $created = [];
        $skipped = [];
        foreach ($data as $row) {
            if (!empty($row['email']) && empty($row['username'])) {
                $row['username'] = $row['email'];
            }
            if (empty($row['email']) || empty($row['username'])) {
                $skipped[] = ['row' => json_encode($row), 'reason' => 'Faltando email/username'];
                continue;
            }
            $exists = $DB->record_exists('user', ['email' => $row['email']])
                || $DB->record_exists('user', ['username' => $row['username']]);
            if ($exists) {
                $skipped[] = ['row' => json_encode($row), 'reason' => 'Usuário já existe'];
                continue;
            }
            try {
                $uid = \local_portalgestao\local\alunos_service::create_user($row, $company);
            } catch (\Throwable $e) {
                $skipped[] = ['row' => json_encode($row), 'reason' => $e->getMessage()];
                continue;
            }
            $failcourses = [];
            if (!empty($row['courses'])) {
                $courses = is_array($row['courses']) ? $row['courses'] : explode(',', (string) $row['courses']);
                foreach ($courses as $code) {
                    if (!\local_portalgestao\local\alunos_service::enrol_in_course_code($uid, $code, $company)) {
                        $failcourses[] = trim((string) $code);
                    }
                }
            }
            $created[] = $uid;
            if ($failcourses) {
                $skipped[] = [
                    'row' => json_encode(['username' => $row['username'], 'courses' => $failcourses]),
                    'reason' => 'Falha ao matricular nos cursos informados',
                ];
            }
        }
        return ['created' => $created, 'skipped' => $skipped];
    }

    public static function create_batch_returns() {
        return new external_single_structure([
            'created' => new external_multiple_structure(new external_value(PARAM_INT, 'User ID criado')),
            'skipped' => new external_multiple_structure(
                new external_single_structure([
                    'row' => new external_value(PARAM_RAW, 'Linha original'),
                    'reason' => new external_value(PARAM_TEXT, 'Motivo do pulo'),
                ])
            ),
        ]);
    }

    // SUSPENDER/ATIVAR
    public static function toggle_suspend_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
            'suspended' => new external_value(PARAM_INT, '0 ou 1'),
        ]);
    }

    public static function toggle_suspend($userid, $suspended) {
        self::require_caps();
        \local_portalgestao\local\alunos_service::set_suspended((int) $userid, (int) $suspended);
        return ['ok' => 1];
    }

    public static function toggle_suspend_returns() {
        return new external_single_structure(['ok' => new external_value(PARAM_INT, '1 se ok')]);
    }

    // EXCLUIR
    public static function delete_user_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    public static function delete_user($userid) {
        self::require_caps();
        \local_portalgestao\local\alunos_service::delete_user((int) $userid);
        return ['ok' => 1];
    }

    public static function delete_user_returns() {
        return new external_single_structure(['ok' => new external_value(PARAM_INT, '1 se ok')]);
    }
}
