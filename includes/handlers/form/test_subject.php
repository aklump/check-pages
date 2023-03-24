<?php

$message = NULL;
if (isset($_POST['message'])) {
  $message = $_POST['message'];
}

//
// Handle the drupal form with pass-through values.
//
if (isset($_POST['op'])) {
  $is_valid = ($_POST['form_id'] ?? NULL) === 'drupal_form';
  $is_valid = $is_valid && ($_POST['form_build_id'] ?? NULL) === 'form-GCz0YacA8dPFAKxjqF41M0rUJgoM8VbyN8RNTG48GxQ';
  $is_valid = $is_valid && ($_POST['form_token'] ?? NULL) === 'HC-doLJjcGKhIIkbn1wUreeJgoxgLZw2VJrNyHcfe30';
  if (!$is_valid) {
    header("HTTP/1.1 403 Forbidden");
    exit();
  }
  $message = $_POST['field_amount'][0]['value'];
}
?>
<html>
<head></head>
<body>
<?php if ($message): ?>
  <div class="messages"><?= $message ?></div>
<?php endif ?>

<form class="form-a" method="post">
  <input type="submit" name="message" value="Form A Was Submitted">
</form>

<form class="form-b" method="post">
  <input type="hidden" name="message" value="Form B Was Submitted"/>
  <input type="submit" class="form-save" name="message" value="Save Was Used to Submit">
  <input type="submit" class="form-delete" name="message" value="Delete Was Used to Submit">
</form>

<form class="form-c" action="/thank_you.php" method="post">
  <input type="text" name="first_name" value=""/>
  <input type="date" name="date" value=""/>
  <select name="shirt_size">
    <option value="sm">small</option>
    <option value="lg">large</option>
  </select>
  <button type="submit">Submit</button>
</form>

<form class="drupal-form" method="post">
  <input type="hidden" name="form_id" value="drupal_form"/>
  <input type="hidden" name="form_build_id" value="form-GCz0YacA8dPFAKxjqF41M0rUJgoM8VbyN8RNTG48GxQ"/ >
  <input type="hidden" name="form_token" value="HC-doLJjcGKhIIkbn1wUreeJgoxgLZw2VJrNyHcfe30"/>
  <input type="text" name="field_amount[0][value]" value="$5.99"/>
  <input id="edit-submit" type="submit" name="op" value="Save"/>
</form>

</body>
</html>
