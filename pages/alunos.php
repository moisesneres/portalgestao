<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/portalgestao:viewpanelgestor', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/portalgestao/pages/alunos.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('studentspage', 'local_portalgestao'));
$PAGE->set_heading(get_string('studentspage', 'local_portalgestao'));
$PAGE->navbar->add(get_string('pluginname', 'local_portalgestao'), new moodle_url('/local/portalgestao/index.php'));
$PAGE->navbar->add(get_string('studentspage', 'local_portalgestao'));

$PAGE->requires->css('/local/portalgestao/assets/css/global.css');
$PAGE->requires->js('/local/portalgestao/assets/js/alunos.js');

echo $OUTPUT->header();
?>
<div class="local-portalgestao">
  <h2><?php echo get_string('studentspage', 'local_portalgestao'); ?></h2>
  <p class="intro">Lista dos alunos vinculados à sua empresa (campo de perfil empresa).</p>
  <div class="table-wrapper">
    <table class="generaltable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome</th>
          <th>E-mail</th>
          <th>Username</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody id="alunos-table-body"></tbody>
    </table>
  </div>
  <div id="alunos-pagination" class="pagination d-none" aria-live="polite">
    <span class="count-label" id="alunos-count"></span>
    <div class="pagination-buttons">
      <button type="button" id="alunos-prev">Anterior</button>
      <button type="button" id="alunos-next">Próxima</button>
    </div>
  </div>
  <div id="alunos-empty" class="empty-state d-none" role="status">
    Nenhum aluno encontrado para sua empresa.
  </div>
</div>
<?php
echo $OUTPUT->footer();
