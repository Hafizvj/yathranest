/**
 * Catalog edit: append library picks to gallery_paths textarea.
 */
(function () {
  document.addEventListener('yn:catalog-gallery', function (event) {
    var items = (event.detail && event.detail.items) || [];
    var textarea = document.getElementById('gallery_paths');
    if (!textarea || !items.length) {
      return;
    }
    var lines = textarea.value.split(/\r?\n/).map(function (line) {
      return line.trim();
    }).filter(Boolean);
    items.forEach(function (item) {
      if (item.path && lines.indexOf(item.path) === -1) {
        lines.push(item.path);
      }
    });
    textarea.value = lines.join('\n');
  });
})();
