<?php
use ElectionPoll\DBAdaptors\PDOAdaptor;
use ElectionPoll\ElectionPoll;

define('ROOT', dirname(__FILE__) . '/');
define('LIB', ROOT . 'lib/');

// Include a PSR-0 autoloader
include(LIB . 'autoloader.php');

include("config.php");
include("header.php");

$db = new PDOAdaptor();
$poll = new ElectionPoll($db);

if (isset($_REQUEST["init"])) {
  $poll->init();
  echo "<h1>Initialisation complete</h1>";
  include("footer.php");
  return;
}
echo "<h1>Voting intentions poll</h1>";
$formatting = [];
echo $poll->html($formatting);
 ?>

<?php
include("footer.php");
 ?>
