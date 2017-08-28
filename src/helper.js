/**
 * WebPage Initialize for alert-dismissible
 */

$(".alert-dismissible").fadeTo(2000, 500).slideUp(500, function () {
  $(".alert-dismissible").alert('close');
});

/**
 * renderUploadFileList
 *
 * @param {Array} items
 * @returns {HTMLElement}
 */
function renderUploadFileList(items) {
  var uploadFileTables = $(
    '<hr />\n' +
    '<span id="in-transcoding"><span class="fa fa-refresh fa-spin"></span> In transcoding....</span>\n' +
    '<table class="table table-striped">\n' +
    '    <thead>\n' +
    '    <tr>\n' +
    '        <th class="upload-file-key">Upload file key</th>\n' +
    '        <th class="title">Title</th>\n' +
    '        <th class="transcoding-stage">Transcoding stage</th>\n' +
    '        <th class="created-at">Created at</th>\n' +
    '    </tr>\n' +
    '    </thead>\n' +
    '    <tbody>\n' +
    '    </tbody>\n' +
    '</table>'
    ),
    tableRow;

  $.each(items, function (key, item) {
    tableRow = $(
      '<tr>\n' +
      '    <td class="upload-file-key"><span class="label label-default">' + item.upload_file_key + '</span></td>\n' +
      '    <td class="title">' + item.title + '</td>\n' +
      '    <td class="transcoding-stage"><span class="label label-' + (item.transcoding_stage === 21 ? 'success' : 'warning') + '">' +
      item.transcoding_stage_name + '</span></td>\n' +
      '    <td class="created-at">' + moment.unix(item.created_at).format('YYYY-DD-MM HH:mm:ss') + '</td>' +
      '</tr>'
    );
    uploadFileTables.find('tbody').append(tableRow);
  });

  return uploadFileTables;
}

/**
 * updateUploadFileList
 *
 * @param {number|undefined} repeator
 */
function updateUploadFileList(repeator)
{
  $.ajax({
    url: '/api/upload_file',
    type: 'GET',
    dataType: 'json',
    cache: false,
    success: function (data) {
      if ('items' in data ) {
        $('#upload-file-list').html(renderUploadFileList(data.items));
      }

      if ('auto_reload' in data && !data.auto_reload) {
        if (data.auto_reload) {
          $('#in-transcoding').show();
        } else {
          $('#in-transcoding').hide();
        }

        if (typeof repeator !== 'undefined') {
          clearInterval(repeator);
        }
      }
    },
    error: function (jqXHR, textStatus) {
      if ('responseJSON' in jqXHR &&
        'message' in jqXHR.responseJSON) {
        showAlert('danger', jqXHR.responseJSON.message);
      } else {
        showAlert('danger', textStatus);
      }
    }
  });
}

/**
 * After complate upload
 *
 * @param {number} period
 * @param {number} duration
 */
function AfterComplateUpload(period, duration) {
  var repeator;

  updateUploadFileList(repeator);

  setTimeout(function() {
    repeator = setInterval(function () {
      updateUploadFileList(repeator)
    }, period); // repeator
  }, duration); // timer
}

// default
AfterComplateUpload(5000, 10000);

/**
 * Show Instant Message.
 *
 * @param {string} type - success|info|warning|danger
 * @param {string} message
 */
function showAlert(type, message) {
  var alertDiv = $(
    '<div class="alert alert-' + type + ' alert-dismissible">\n' +
    '    <button type="button" class="close" data-dismiss="alert">&times;</button>' + message + '\n' +
    '</div>\n'
    ),
    delayDuration = 2000,
    slideUpDuration = 500;

  alertDiv.delay(delayDuration).slideUp(slideUpDuration);

  $('.flashes').append(alertDiv);
}
