(function ($) {
  var dtTable;
  var moveModal;
  var renameModal;
  var $butchRemoveBtn = $('#document-butch-remove');

  var butchDelete = [];

  var SIZE_POSTFIX = [
    'Bytes',
    'KB',
    'MB',
    'GB',
    'TB'
  ];

  var CLICK_HANDLERS = {
    'tbody tr': {
      cb: function (data) {
        window.location = '/' + data.slug;
      },
      prevent: true
    },
    '.document--field__checkbox': {
      cb: function (data, event) {
        var $checkbox = $(event.currentTarget).find('input');
        var id = data.id;

        if ($checkbox.is(':checked')) {
          butchDelete.push(id);
        } else {
          var idx = butchDelete.indexOf(id);

          if (idx !== -1) {
            butchDelete.splice(idx, 1);
          }
        }

        if (butchDelete.length) {
          $butchRemoveBtn.attr('disabled', false);
        } else {
          $butchRemoveBtn.attr('disabled', true);
        }
      },
      prevent: false
    },
    '.document--action__download': {
      cb: function(data) {
        window.location = data.downloadUrl;
      },
      prevent: true
    },
    '.document--action__move': {
      cb: function (data) {
        moveModal.$('#move-form').attr('action', '/files/' + data.slug + '/move');
        moveModal.setTitle('Move "'+ data.name +'"').show();
      },
      prevent: true
    },
    '.document--action__rename': {
      cb: function (data) {
        renameModal.$('#document-name').val(data.name + '.' + data.ext);
        renameModal.$('#rename-form').attr('action', '/files/' + data.slug + '/rename');
        renameModal.setTitle('Rename "' + data.name + '"').show();
      },
      prevent: true
    },
    '.document--action__remove': {
      cb: function (data) {
        if (confirm('Remove document "'+ data.name +'"')) {
          api({
            url: '/files/' + data.slug,
            method: 'DELETE'
          })
            .then(function () { dtTable.draw() });
        }
      },
      prevent: true
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

    if (documents.showCheckboxes) {
      columns.unshift({
        title: '',
        data: function (data) {
          return '<input type="checkbox" data-id="'+ data.id +'" />';
        },
        className: 'document--field__checkbox',
        orderable: false,
        searchable: false
      })
    }

    var ACTIONS = [
      {
        'class': 'document-action document--action__download',
        icon: 'fa-download',
        title: 'Download',
        href: function (data) { return data.downloadUrl; }
      }
    ];

    if (documents.showActions) {
      ACTIONS.push({
        'class': 'document-action document--action__move',
        icon: 'fa-folder-open',
        title: 'Move'
      });
      ACTIONS.push({
        'class': 'document-action document--action__rename',
        icon: 'fa-edit',
        title: 'Rename'
      });
      ACTIONS.push({
        'class': 'document-action document--action__remove',
        icon: 'fa-trash',
        title: 'Remove'
      });
    }

    columns.push({
      title: 'Action',
      data: function (data) {
        if (data.type === 'directory') {
          return '';
        }

        return $.map(ACTIONS, function (action) {
          var href = '#';
          if (action.hasOwnProperty('href') && action.href) {
            href = action.href(data);
          }

          return '<a class="'+ action.class +'" href="'+ href +'"><i class="fa '+ action.icon +'"></i> '+ action.title +'</a>'
        }).join('');
      },
      className: 'document--field__actions',
      orderable: false,
      searchable: false
    });

    var $table = $('#documents-table');
    dtTable = $table.DataTable({
      order: [[ documents.showCheckboxes ? 1 : 0, window.documents.defaultOrder ]],
      pageLength: 100,
      lengthMenu: [
        [ 10, 25, 50, 100, -1 ],
        [ 10, 25, 50, 100, 'All']
      ],
      initComplete: function () {
        var $search = $('input[type="search"]');
        var debouncedSearch = debounce(function () {
          dtTable.search($search.val()).draw();
        }, dtTable.settings()[0].searchDelay);
        var $btn = $('<button style="display: none" class="btn btn-small">Reset</button>');
        var $statesForm = $('#states-form').show();
        var $statesFormLabel = $('#states-form-label').show();

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
        $search.parent().append($statesFormLabel);
        $search.parent().append($statesForm);

          $statesForm.select2({
              templateSelection: function (state) {
                  if (!state.id) { return state.text; }
                  return $(
                      '<span><img style="display: inline-block; height: 10px;" src="images/icon.jpg" /> ' + state.text + '</span>'
                  );
              }
          });
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
          var state = $('#states-form').val();

          $.each(data.order, function (idx, orderCfg) {
            order[data.columns[orderCfg.column].data] = orderCfg.dir;
          });

          data.state =  (state !== '0') ? state : '';

          return {
            draw: data.draw,
            order: order,
            offset: data.start || 0,
            limit: data.length,
            search: data.search.value,
            state: data.state,
          };
        }
      },
      columns: columns,
      createdRow: function(row, data) {
        row.dataset.slug = data.slug;
      }
    });

      $('#states-form').on('change', function () {
        dtTable.draw();
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

      renameModal.showLoader();
      api({
        url: event.target.getAttribute('action'),
        method: 'PUT',
        data: { name: $renameInput.val() }
      })
        .then(function () {
          dtTable.draw();
          renameModal.hide();
        })
        .always(function () {
          renameModal.hideLoader();
        });
    });

    for (var selector in CLICK_HANDLERS) {
      if (CLICK_HANDLERS.hasOwnProperty(selector)) {
        (function (selector) {
          var cb = CLICK_HANDLERS[selector].cb;
          var prevent = CLICK_HANDLERS[selector].prevent;

          $table.on('click', selector, function (event) {
            event.stopPropagation();

            if (prevent) {
              event.preventDefault();
            }

            cb(dtTable.row($(this).closest('tr')).data(), event);
          })
        })(selector);
      }
    }

    var $moveForm = moveModal.$('#move-form');
    var $directorySelector = $moveForm.find('#directory');

    $moveForm.submit(function (event) {
      event.stopPropagation();
      event.preventDefault();

      moveModal.showLoader();
      api({
        url: event.target.getAttribute('action'),
        method: 'PUT',
        data: { topLevelDir: $directorySelector.val() }
      })
        .then(function () {
          dtTable.draw();
          moveModal.hide();
        })
        .always(function () {
          moveModal.hideLoader();
        });
    });

    var uploadModal = new Modal('#document-add-modal');
    var $uploadForm = uploadModal.$('#upload-form');

    $uploadForm.submit(function (event) {
      event.stopPropagation();
      event.preventDefault();

      var fd = new FormData();
      var files = $uploadForm.find('input').prop('files');

      for (var i = 0; i < files.length; i++) {
          fd.append('file[]', files[i]);
      }

      // fd.append('file', $.makeArray($uploadForm.find('input').prop('files')));

      uploadModal.showLoader();
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
          $uploadForm.find('input').val('');
        })
        .always(function () {
          uploadModal.hideLoader();
        });
    });

    $('#document-add').click(function (event) {
      event.stopPropagation();
      event.preventDefault();

      uploadModal.show();
    });
  });

  $butchRemoveBtn.click(function () {
    api({
      url: documents.butchRemoveUrl,
      type: 'DELETE',
      data: {
        ids: butchDelete
      }
    })
      .then(function () {
        butchDelete = [];
        $butchRemoveBtn.attr('disabled', true);
        dtTable.draw();
      })
  });

  function api(cfg) {
    var _cfg = $.extend({
      type: 'POST'
    }, cfg);

    return $.ajax(_cfg)
      .catch(function (xhr) {
        var message = 'Can\'t process request due to server error';

        try {
          message = JSON.parse(xhr.responseText).error.description;
        } catch (error) {
          if (xhr.status === 404) {
            message = 'File of directory not found';
          }
        }

        alert(message);

        throw new Error(message);
      })
  }

  function Modal(selector) {
    var self = this;

    self._$el = $(selector);
    self._$el.find('[data-modal-close]').click(function () { self.hide(); });
    self._$loader = self._$el.find('.loader-wrapper');
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

  Modal.prototype.showLoader = function showLoader() {
    this._$loader.show();
  };

  Modal.prototype.hideLoader = function showLoader() {
    this._$loader.hide();
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
