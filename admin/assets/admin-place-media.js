/**
 * Places edit: library picks become images_keep[] hidden inputs.
 */
(function () {
  document.addEventListener('yn:place-images', function (event) {
    var items = (event.detail && event.detail.items) || [];
    var host = document.getElementById('place-library-keep');
    if (!host || !items.length) {
      return;
    }
    items.forEach(function (item) {
      if (!item.path) {
        return;
      }
      var exists = false;
      document.querySelectorAll('input[name="images_keep[]"]').forEach(function (input) {
        if (input.value === item.path) {
          exists = true;
        }
      });
      if (exists) {
        return;
      }
      var input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'images_keep[]';
      input.value = item.path;
      host.appendChild(input);
      var note = document.createElement('p');
      note.className = 'help-text';
      note.textContent = 'Library: ' + (item.name || item.path);
      host.appendChild(note);
    });
  });
})();
