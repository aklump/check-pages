<?php

$message = NULL;
if (isset($_POST['message'])) {
  $message = $_POST['message'];
}

//
// Handle the drupal form with pass-through values.
//
if (isset($_POST['op']) && $_POST['form_id'] === 'drupal_form') {
  $is_valid = TRUE;
  $is_valid = $is_valid && $_POST['form_build_id'] === 'form-GCz0YacA8dPFAKxjqF41M0rUJgoM8VbyN8RNTG48GxQ';
  $is_valid = $is_valid && $_POST['form_token'] === 'HC-doLJjcGKhIIkbn1wUreeJgoxgLZw2VJrNyHcfe30';
  if (!$is_valid) {
    header("Status: 403 Forbidden");
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
  <input type="submit" name="message" value="Form A">
</form>

<form class="form-b" method="post">
  <input type="hidden" name="message" value="Form B"/>
  <input type="submit" class="form-save" name="message" value="Save">
  <input type="submit" class="form-delete" name="message" value="Delete">
</form>

<form class="drupal-form" method="post">
  <input type="hidden" name="form_id" value="drupal_form"/>
  <input type="hidden" name="form_build_id" value="form-GCz0YacA8dPFAKxjqF41M0rUJgoM8VbyN8RNTG48GxQ"/ >
  <input type="hidden" name="form_token" value="HC-doLJjcGKhIIkbn1wUreeJgoxgLZw2VJrNyHcfe30"/>
  <input type="hidden" name="field_amount[0][value]" value="123"/>
  <input id="edit-submit" type="submit" name="op" value="Save"/>
</form>

</body>
</html>
