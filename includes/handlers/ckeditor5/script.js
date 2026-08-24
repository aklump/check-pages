((Drupal) => {
  if (!Drupal.CKEditor5Instances) {
    return '';
  }

  const intervalMS = 250;
  const maxAttempts = Math.round(JIG__TIMEOUT_SECONDS * 1000 / intervalMS);

  function waitFor(condition) {
    return new Promise((resolve) => {
      let attempts = 0;

      const tick = () => {
        attempts += 1;

        const result = condition();

        if (result) {
          resolve(result);
          return;
        }

        if (attempts >= maxAttempts) {
          resolve(null);
          return;
        }

        setTimeout(tick, intervalMS);
      };

      tick();
    });
  }

  function waitForAnimationFrame() {
    return new Promise((resolve) => {
      requestAnimationFrame(() => {
        resolve();
      });
    });
  }

  function onceCkeditorDataChanges(editor) {
    return new Promise((resolve) => {
      editor.model.document.once('change:data', () => {
        resolve();
      });
    });
  }

  function getCkeditorInstance(selector) {
    const element = document.querySelector(selector);

    if (!element) {
      return null;
    }

    const id = element.getAttribute('data-ckeditor5-id');

    return id ? Drupal.CKEditor5Instances.get(id) : null;
  }

  return waitFor(() => getCkeditorInstance('JIG__TEXTAREA'))
    .then((editor) => {
      if (!editor) {
        return null;
      }

      const changed = onceCkeditorDataChanges(editor);

      // @see https://ckeditor.com/docs/ckeditor5/latest/getting-started/setup/getting-and-setting-data.html
      editor.setData('JIG__VALUE');

      return changed
        .then(() => waitForAnimationFrame())
        .then(() => waitFor(() => {
          if (!editor.getData().includes('JIG__VALUE')) {
            return null;
          }

          const element = document.querySelector('JIG__DOM');

          if (!element) {
            return null;
          }

          return element.innerHTML;
        }));
    });
})(typeof Drupal === 'undefined' ? {} : Drupal);
