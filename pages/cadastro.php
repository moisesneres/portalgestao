<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/portalgestao:manageusers', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/portalgestao/pages/cadastro.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('bulkcreatepage', 'local_portalgestao'));
$PAGE->set_heading(get_string('bulkcreatepage', 'local_portalgestao'));
$PAGE->navbar->add(get_string('pluginname', 'local_portalgestao'), new moodle_url('/local/portalgestao/index.php'));
$PAGE->navbar->add(get_string('bulkcreatepage', 'local_portalgestao'));

$PAGE->requires->css('/local/portalgestao/assets/css/global.css');
$PAGE->requires->js('/local/portalgestao/assets/js/cadastro.js');

echo $OUTPUT->header();
?>
<div class="local-portalgestao">
  <h2><?php echo get_string('bulkcreatepage', 'local_portalgestao'); ?></h2>
  <p class="intro">
    Utilize o formulário abaixo para cadastrar alunos vinculados à sua empresa. Informe os cursos usando o código numérico exibido na URL (por exemplo, <code>id=137</code>) ou o shortname de cada curso. Cada usuário receberá uma senha provisória por e-mail e deverá trocá-la no primeiro acesso.
  </p>
  <form id="cadastro-form">
    <table class="generaltable cadastro-table">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Sobrenome</th>
          <th>E-mail / Username</th>
          <th>Cursos (códigos ou shortnames separados por vírgula)</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody id="cadastro-rows"></tbody>
    </table>
    <div class="actions">
      <button type="button" id="add-row" class="btn-secondary btn">Adicionar aluno</button>
      <button type="submit" id="btn-enviar" class="btn">Criar usuários</button>
    </div>
  </form>
  <div id="resultado" class="result" aria-live="polite"></div>
</div>
<?php
echo $OUTPUT->footer();
