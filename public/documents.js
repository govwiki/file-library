(function ($) {
  var SIZE_POSTFIX = [
    'Bytes',
    'KB',
    'MB',
    'GB',
    'TB'
  ];

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

    if (documents.showActions) {
      var ACTIONS = [
        {
          'class': 'document-action document--action__rename',
          'icon': 'fa-edit',
          'title': 'Rename'
        },
        {
          'class': 'document-action document--action__remove',
          'icon': 'fa-trash',
          'title': 'Remove'
        }
      ];

      columns.push({
        title: 'Action',
        data: function () {
          return $.map(ACTIONS, function (action) {
            return '<a class="'+ action.class +'" href="#"><i class="fa '+ action.icon +'"></i> '+ action.title +'</a>'
          }).join('');
        },
        className: 'document--field__actions',
        orderable: false
      });
    }

    var $table = $('#documents-table');
    var dtTable = $table.DataTable({
      autoWidth: false,
      searching: false,
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
            limit: data.length
          };
        }
      },
      columns: columns,
      createdRow: function(row, data) {
        row.dataset.slug = data.slug;
      }
    });

    var renameModal = new Modal('#document-rename-modal');
    var $renameForm = $('#rename-form');
    var $renameInput = $renameForm.find('#document-name');

    $renameForm.submit(function (event) {
      event.stopPropagation();
      event.preventDefault();

      api({
        url: event.target.getAttribute('action'),
        method: 'PUT',
        data: { publicPath: $renameInput.val() }
      })
        .then(function () {
          dtTable.draw();
          renameModal.hide();
        })
    });

    $table.on('click', 'tbody tr', function (event) {
      event.stopPropagation();

      var data = dtTable.row($(this).closest('tr')).data();
      window.location = '/' + data.slug;
    });

    $table.on('click', '.document--action__rename', function (event) {
      event.stopPropagation();
      event.preventDefault();

      var data = dtTable.row($(this).closest('tr')).data();
      $renameInput.val(data.publicPath);

      $renameForm.attr('action', '/files/' + data.slug);
      renameModal.setTitle('Rename "'+ data.name +'"').show();
    });

    $table.on('click', '.document--action__remove', function (event) {
      event.stopPropagation();
      event.preventDefault();

      var data = dtTable.row($(this).closest('tr')).data();
      if (confirm('Remove document "'+ data.name +'"')) {
        api({
          url: '/files/' + data.slug,
          method: 'DELETE'
        }).then(function () { dtTable.draw() });
      }
    });

    var uploadModal = new Modal('#document-add-modal');
    var $uploadForm = $('#upload-form');

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

    return $.ajax(_cfg).fail(function (xhr) { console.log(JSON.parse(xhr.responseText)) })
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
})(jQuery);
