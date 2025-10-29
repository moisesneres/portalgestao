<?php
namespace local_portalgestao\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Cliente simples para consumo dos web services REST do Moodle.
 */
class ws_client {
    /**
     * Recupera o endpoint configurado ou usa o padrão do próprio Moodle.
     *
     * @return string
     */
    protected static function get_endpoint(): string {
        global $CFG;

        $endpoint = trim((string) get_config('local_portalgestao', 'wsendpoint'));
        if ($endpoint === '') {
            $endpoint = rtrim($CFG->wwwroot, '/') . '/webservice/rest/server.php';
        }
        return $endpoint;
    }

    /**
     * Recupera o token configurado para o plugin.
     *
     * @throws \moodle_exception Quando não configurado.
     * @return string
     */
    protected static function get_token(): string {
        $token = trim((string) get_config('local_portalgestao', 'wstoken'));
        if ($token === '') {
            throw new \moodle_exception('missingwstoken', 'local_portalgestao');
        }
        return $token;
    }

    /**
     * Executa uma chamada REST ao Moodle usando o token configurado.
     *
     * @param string $function Nome da função (wsfunction).
     * @param array $params Parâmetros adicionais.
     * @throws \moodle_exception Em caso de falha na comunicação ou resposta inválida.
     * @return mixed
     */
    public static function call(string $function, array $params = []) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $endpoint = self::get_endpoint();
        $token = self::get_token();

        $request = array_merge($params, [
            'wstoken' => $token,
            'wsfunction' => $function,
            'moodlewsrestformat' => 'json',
        ]);

        $curl = new \curl();
        $response = $curl->post($endpoint, $request);

        if ($curl->get_errno()) {
            throw new \moodle_exception('wsconnection', 'local_portalgestao', '', $curl->error);
        }

        $trimmed = trim((string) $response);
        if ($trimmed === '') {
            throw new \moodle_exception('wsinvalidresponse', 'local_portalgestao', '', null, 'Empty response');
        }

        $payload = json_decode($trimmed, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('wsinvalidresponse', 'local_portalgestao', '', null, json_last_error_msg());
        }

        if (is_array($payload) && array_key_exists('exception', $payload)) {
            $message = isset($payload['message']) ? (string) $payload['message'] : '';
            $debuginfo = $payload['debuginfo'] ?? '';
            throw new \moodle_exception('wsremoteerror', 'local_portalgestao', '', $message, $debuginfo);
        }

        return $payload;
    }
}
