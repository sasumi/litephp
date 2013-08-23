<?php include 'header.inc.php'?>
<form action="<?php echo url('component/upload')?>" method="POST" enctype="multipart/form-data">
	<input type="file" name="file1"><br/>
	<input type="file" name="file2" id=""><br/>
	<input type="file" name="files[]" id=""><br/>
	<input type="file" name="files[]" id=""><br/>
	<input type="submit" value="submit">
</form>
<?php include 'footer.inc.php'?>