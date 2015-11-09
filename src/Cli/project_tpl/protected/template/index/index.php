<?php include $this->getTemplate('inc/header.inc.php');?>
<div class="dashboard">
<?php
$cal = new Lite\Component\Calendar();
$cal->setConfig(array('show_week_index'=>true));
echo $cal;
?>
</div>
<?php include $this->getTemplate('inc/footer.inc.php');?>