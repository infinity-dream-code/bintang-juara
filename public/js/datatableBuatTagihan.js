function formatColumnName(columnName) {
  if (!columnName.includes('input')) {
    return columnName.replace(/_/g, ' ').replace(/\b\w/g, match => match.toUpperCase());
  } else {
    return columnName;
  }
}

function createColumnsHtml(columns) {
  return columns.map(column => `<th>${formatColumnName(column.name)}</th>`).join('');
}

function createColumns(id, columns, location) {
  const table = $(`#${id}`);
  let headerOrFooter = table.find(location);
  if (headerOrFooter.length === 0) {
    headerOrFooter = $(`<${location}></${location}>`);
    table.append(headerOrFooter);
  }
  headerOrFooter.empty();
  const row = $('<tr class="text-start fw-bold text-uppercase fs-7 gs-0">').html(createColumnsHtml(columns));
  headerOrFooter.html(row);
}

function dataTableCreate(id, dataUrl, dataColumns, formId) {
  let idTable = $(`#${id}`);
  // let transformedData = formId ? $(`#${formId}`).serialize() : null;
  let transformedData = formId ? $(`#${formId}`).serializeArray().reduce((acc, {name, value}) => {
    if (acc[name]) {
      if (!Array.isArray(acc[name])) {
        acc[name] = [acc[name]];
      }
      if (Array.isArray(value)) {
        acc[name] = acc[name].concat(value);
      } else {
        acc[name].push(value);
      }
    } else {
      acc[name] = value;
    }
    return acc;
  }, {}) : {};
  idTable.DataTable({
    autoWidth: false,
    responsive: false,
    search: false,
    paging: false,
    columns: dataColumns,
    language: {
      url: 'https://cdn.datatables.net/plug-ins/2.0.6/i18n/id.json',
    },
    initComplete: function (settings, json) {
      $('#check-all').parent().removeClass('sorting_asc');
    },
    error: function (xhr, error, code) {
      errorAlert('Data tidak dapat dimuat')
    }
  })
}

function dataReload(id) {
  $(`#${id}`).DataTable().reload();
}

function dataReFilter(id, dataUrl, dataColumns, formId) {
  $(`#${id}`).DataTable().destroy();
  dataTableCreate(id, dataUrl, dataColumns, formId);
}

function getDT(id, columnUrl, dataUrl, dataColumns, formId, thead) {
  $.ajax({
    url: columnUrl,
    success: function (data) {
      $.each(data, function (index, column) {
        let renderFunc = ''
        if (column.input) {
          switch (column.input) {
            case 'text':
              renderFunc = function (data, type, row) {
                if (column.currency) {
                  const parsedNumber = parseInt(data.replace(/\./g, ''));
                  return `
                    <div class="input-group input-group-sm input-tagihan">
                        <span class="input-group-text" >
                            Rp
                        </span>
                        <input type="text" class="form-control formattedNumber rounded-end" name="tagihan[${row.check}][${column.data}]" id="${column.data}" autocomplete="off" value="${parsedNumber.toLocaleString('id-ID')}" placeholder="${column.name}">
                        <div class="invalid-feedback" role="alert"></div>
                    </div>
                    `
                } else {
                  return `
                    <div class="input-group input-group-sm input-tagihan">
                        <input type="text" class="form-control" name="tagihan[${row.check}][${column.data}]" id="${column.data}" autocomplete="off" value="1" placeholder="${column.name}">
                        <span class="input-group-text rounded-end">
                            kali
                        </span>
                        <div class="invalid-feedback" role="alert"></div>
                    </div>
                    `
                }
              }
              break;
            case 'check':
              renderFunc = function (data, type, row) {
                return `<input class="form-check-input row-checkbox" type="checkbox" value="${data}" name="tagihan[${data}][${column.data}]"/>`
              }
              break;
          }
        }

        dataColumns.push({
          data: column.data,
          name: column.name,
          orderable: column.orderable ?? false,
          render: renderFunc,
          className: column.className ?? false,
        })
      })
      if (thead) {
        createColumns(id, dataColumns, 'thead');
      }
      dataTableCreate(id, dataUrl, dataColumns, formId)
    }
  });
}
