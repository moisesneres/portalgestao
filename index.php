<?php
require_once(__DIR__ . '/../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/portalgestao:viewpanelgestor', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/portalgestao/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_portalgestao'));
$PAGE->set_heading(get_string('pluginname', 'local_portalgestao'));
$PAGE->navbar->add(get_string('pluginname', 'local_portalgestao'));

$PAGE->requires->css('/local/portalgestao/assets/css/global.css');

echo $OUTPUT->header();
?>
<div class="local-portalgestao">
  <h2><?php echo get_string('pluginname', 'local_portalgestao'); ?></h2>
  <p><?php echo get_string('indexintro', 'local_portalgestao'); ?></p>
  <ul>
    <li><a href="<?php echo (new moodle_url('/local/portalgestao/pages/alunos.php'))->out(); ?>"><?php echo get_string('studentspage', 'local_portalgestao'); ?></a></li>
    <li><a href="<?php echo (new moodle_url('/local/portalgestao/pages/cadastro.php'))->out(); ?>"><?php echo get_string('bulkcreatepage', 'local_portalgestao'); ?></a></li>
  </ul>
</div>
<?php
echo $OUTPUT->footer();
