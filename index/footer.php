<?php
$scriptPath = $_SERVER['SCRIPT_NAME'];
$parts      = explode('/', trim($scriptPath,'/'));
$base       = '/' . $parts[0] . '/';
?>
<footer>
  <p>
    &copy; <?= date('Y') ?> Labs Project &mdash; University of the UK &nbsp;|&nbsp;
    <a href="<?= $base ?>index.php">Home</a> &nbsp;|&nbsp;
    <a href="<?= $base ?>index.php?level=1">Undergraduate</a> &nbsp;|&nbsp;
    <a href="<?= $base ?>index.php?level=2">Postgraduate</a>
  </p>
</footer>
<script src="<?= $base ?>assets/js/main.js"></script>
</body>
</html>