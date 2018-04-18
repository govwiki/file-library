(function ($) {
  var dtTable;
  var moveModal;
  var renameModal;

  var SIZE_POSTFIX = [
    'Bytes',
    'KB',
    'MB',
    'GB',
    'TB'
  ];

  var CLICK_HANDLERS = {
    'tbody tr': function (data) {
      window.location = '/' + data.slug;
    },
    '.document--action__download': function(data) {
      window.location = '/' + data.slug;
    },
    '.document--action__move': function (data) {
      moveModal.$('#move-form').attr('action', '/files/' + data.slug + '/move');
      moveModal.setTitle('Move "'+ data.name +'"').show();
    },
    '.document--action__rename': function (data) {
      renameModal.$('#document-name').val(data.name + '.' + data.ext);
      renameModal.$('#rename-form').attr('action', '/files/' + data.slug + '/rename');
      renameModal.setTitle('Rename "' + data.name + '"').show();
    },
    '.document--action__remove': function (data) {
      if (confirm('Remove document "'+ data.name +'"')) {
        api({
          url: '/files/' + data.slug,
          method: 'DELETE'
        })
          .then(function () { dtTable.draw() })
          .fail(function (xhr) { console.log(xhr); alert(JSON.parse(xhr.responseText).error.description) });
      }
    }
  };

  $(function () {
    var columns = [
      {
        title: 'File',
        data: 'name',
        className: 'document--field__name'
      },
      {
        title: 'File size',
        data: 'fileSize',
        className: 'document--field__file-size',
        render: function (fileSize) {
          if (fileSize === null) {
            return '';
          }

          var nextPrettyFileSize = fileSize;
          var postfixIdx = 0;
          var prettyFileSize = 0;

          do {
            prettyFileSize = nextPrettyFileSize;
            nextPrettyFileSize /= 1024;
            postfixIdx++;
          } while (nextPrettyFileSize > 1);

          return prettyFileSize.toFixed(1) + ' ' + SIZE_POSTFIX[postfixIdx - 1];
        }
      }
    ];

    var ACTIONS = [
      {
        'class': 'document-action document--action__download',
        'icon': 'fa-download',
        'title': 'Download'
      }
    ];

    if (documents.showActions) {
      ACTIONS.push({
        'class': 'document-action document--action__move',
        'icon': 'fa-folder-open',
        'title': 'Move'
      });
      ACTIONS.push({
        'class': 'document-action document--action__rename',
        'icon': 'fa-edit',
        'title': 'Rename'
      });
      ACTIONS.push({
        'class': 'document-action document--action__remove',
        'icon': 'fa-trash',
        'title': 'Remove'
      });
    }

    columns.push({
      title: 'Action',
      data: function (data) {
        if (data.type === 'directory') {
          return '';
        }

        return $.map(ACTIONS, function (action) {
          return '<a class="'+ action.class +'" href="#"><i class="fa '+ action.icon +'"></i> '+ action.title +'</a>'
        }).join('');
      },
      className: 'document--field__actions',
      orderable: false
    });

    var $table = $('#documents-table');
    dtTable = $table.DataTable({
      initComplete: function () {
        var $search = $('input[type="search"]');
        var debouncedSearch = debounce(function () {
          dtTable.search($search.val()).draw();
        }, dtTable.settings()[0].searchDelay);
        var $btn = $('<button style="display: none" class="btn btn-small">Reset</button>');

        $btn.click(function () {
          dtTable.search('').draw();
          $btn.hide();
        });

        $search
          .off()
          .on('keyup cut paste', function () {
            if ($search.val() !== '') {
              $btn.show();
            } else {
              $btn.hide();
            }
          })
          .on('keyup cut paste', debouncedSearch);

        $search.parent().append($btn);
      },
      autoWidth: false,
      searching: true,
      searchDelay: 250,
      info: false,
      processing: true,
      serverSide: true,
      ajax: {
        url: $table.data('source'),
        type: 'GET',
        data: function (data) {
          var order = {};

          $.each(data.order, function (idx, orderCfg) {
            order[data.columns[orderCfg.column].data] = orderCfg.dir;
          });

          return {
            draw: data.draw,
            order: order,
            offset: data.start,
            limit: data.length,
            search: data.search.value
          };
        }
      },
      columns: columns,
      createdRow: function(row, data) {
        row.dataset.slug = data.slug;
      }
    });

    renameModal = new Modal('#document-rename-modal');
    moveModal = new Modal('#document-move-modal');

    var $renameForm = renameModal.$('#rename-form');
    var $renameInput = $renameForm.find('#document-name');

    $renameForm.submit(function (event) {
      event.stopPropagation();
      event.preventDefault();

      var newName = $renameInput.val();
      $renameForm.find('.error').hide().text('');
      if (! newName.match(/^[A-Z]{2}\s+.+\s+\d{4}\.\w+$/i)) {
        $renameForm.find('.error').show().text('Invalid document name should be matched to next format: two latter state code, name of document, 4 digit year and then file extension');

        return;
      }

      api({
        url: event.target.getAttribute('action'),
        method: 'PUT',
        data: { name: $renameInput.val() }
      })
        .then(function () {
          dtTable.draw();
          renameModal.hide();
        })
        .fail(function (xhr) { alert(JSON.parse(xhr.responseText).error.description) });
    });

    for (var selector in CLICK_HANDLERS) {
      if (CLICK_HANDLERS.hasOwnProperty(selector)) {
        (function (selector) {
          $table.on('click', selector, function (event) {
            event.stopPropagation();
            event.preventDefault();

            var data = dtTable.row($(this).closest('tr')).data();

            CLICK_HANDLERS[selector](data);
          })
        })(selector);
      }
    }

    var $moveForm = moveModal.$('#move-form');
    var $directorySelector = $moveForm.find('#directory');

    $moveForm.submit(function (event) {
      event.stopPropagation();
      event.preventDefault();

      api({
        url: event.target.getAttribute('action'),
        method: 'PUT',
        data: { topLevelDir: $directorySelector.val() }
      })
        .then(function () {
          dtTable.draw();
          moveModal.hide();
        })
        .fail(function (xhr) { alert(JSON.parse(xhr.responseText).error.description) });
    });

    var uploadModal = new Modal('#document-add-modal');
    var $uploadForm = uploadModal.$('#upload-form');

    $uploadForm.submit(function (event) {
      event.stopPropagation();
      event.preventDefault();

      var fd = new FormData();
      fd.append('file', $uploadForm.find('input').prop('files')[0]);

      api({
        url: event.target.getAttribute('action'),
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false
      })
        .then(function () {
          dtTable.draw();
          uploadModal.hide();
        })
        .fail(function (xhr) { alert(JSON.parse(xhr.responseText).error.description) });
    });

    $('#document-add').click(function (event) {
      event.stopPropagation();
      event.preventDefault();

      uploadModal.show();
    });
  });

  function api(cfg) {
    var _cfg = $.extend({
      type: 'POST'
    }, cfg);

    return $.ajax(_cfg)
  }

  function Modal(selector) {
    var self = this;

    self._$el = $(selector);
    self._$el.find('[data-modal-close]').click(function () { self.hide(); });
    $(window).click(function (event) { (event.target === self._$el[0]) && self.hide() });
  }
  Modal.prototype.setTitle = function setTitle(title) {
    this._$el.find('.modal-title').text(title);

    return this;
  };
  Modal.prototype.show = function show() {
    this._$el.css('display', 'block');

    return this;
  };
  Modal.prototype.hide = function hide() {
    this._$el.css('display', 'none');

    return this;
  };
  Modal.prototype.$ = function find(selector) {
    return this._$el.find(selector)
  };

  function debounce(fn, delay) {
    var timer = null;

    return function debouncer() {
      var context = this;
      var args = arguments;

      clearTimeout(timer);
      timer = setTimeout(function () { fn.apply(context, args) }, delay);
    };
  }
})(jQuery);
