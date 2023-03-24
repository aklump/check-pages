<html>
<body>
<h1>Thank you <?= ($_POST['first_name'] ?? '') ?>, for your submission</h1>
<h2>Your membership will begin on <?= ($_POST['date'] ?? '') ?>.</h2>
<p>Your shirt size is <?= ($_POST['shirt_size'] ?? '') ?>.</p>
</body>
</html>
