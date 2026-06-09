function setJsonDataAttribute(jsonObject) {
    let jsonString = JSON.stringify(jsonObject);
    return jsonString.replace(/"/g, "&quot;");
}

function createButton(text, eb) {
    let data = setJsonDataAttribute(text)

    if (eb === 'btn_modal_view') {
        return `
        <button class="btn btn-blue" data-bs-toggle="modal"
                data-bs-target="#modal-detail"
                data-val="${data}" title="Detail Data">
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="icon icon-tabler icon-tabler-notes" width="24"
                 height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                 fill="none"
                 stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path
                    d="M5 3m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z"></path>
                <path d="M9 7l6 0"></path>
                <path d="M9 11l6 0"></path>
                <path d="M9 15l4 0"></path>
            </svg>
            Detail
        </button>
        `
    } else if (eb === 'btn_modal_edit') {
        return `
        <button class="btn btn-warning" data-bs-toggle="modal"
                data-bs-target="#modal-edit"
                data-val="${data}" title="Edit Data">
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="icon icon-tabler icon-tabler-edit" width="24"
                 height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                 fill="none"
                 stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path
                    d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"></path>
                <path
                    d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"></path>
                <path d="M16 5l3 3"></path>
            </svg>
            Edit
        </button>
        `
    } else if (eb === 'btn_modal_delete') {
        return `
        <button class="btn btn-danger" data-bs-toggle="modal"
                data-bs-target="#modal-destroy"
                data-val="${data}">
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="icon icon-tabler icon-tabler-trash-x" width="24"
                 height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                 fill="none"
                 stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path d="M4 7h16"></path>
                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                <path d="M10 12l4 4m0 -4l-4 4"></path>
            </svg>
            Hapus
        </button>
        `
    } else if (eb === 'btn_modal_bayar') {
        return `
        <button class="btn btn-success" data-bs-toggle="modal"
                data-bs-target="#modal-payment"
                data-val="${data}">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-cash" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path d="M7 9m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v6a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z"></path>
                <path d="M14 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"></path>
                <path d="M17 9v-2a2 2 0 0 0 -2 -2h-10a2 2 0 0 0 -2 2v6a2 2 0 0 0 2 2h2"></path>
            </svg>
            Bayar
        </button>
        `
    } else if (eb === 'btn_page_detail') {
        return `
        <button class="btn btn-blue btn_page_detail" data-val="${data}" title="detail">
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="icon icon-tabler icon-tabler-notes" width="24"
                 height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                 fill="none"
                 stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path
                    d="M5 3m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z"></path>
                <path d="M9 7l6 0"></path>
                <path d="M9 11l6 0"></path>
                <path d="M9 15l4 0"></path>
            </svg>
            Detail
        </button>
        `
    } else if (eb === 'btn_card') {
        return `
        <button class="btn btn-purple btn_page_kartu" data-val="${data}" title="detail">
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="icon icon-tabler icon-tabler-notes" width="24"
                 height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                 fill="none"
                 stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path
                    d="M5 3m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z"></path>
                <path d="M9 7l6 0"></path>
                <path d="M9 11l6 0"></path>
                <path d="M9 15l4 0"></path>
            </svg>
            Kartu Anggota
        </button>
        `
    } else if (eb === 'btn_cetak_invoice') {
        return `
        <button class="btn btn-purple btn_cetak_invoice" data-val="${data}" title="cetak invoice">
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="icon icon-tabler icon-tabler-notes" width="24"
                 height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
                 fill="none"
                 stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path
                    d="M5 3m0 2a2 2 0 0 1 2 -2h10a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2z"></path>
                <path d="M9 7l6 0"></path>
                <path d="M9 11l6 0"></path>
                <path d="M9 15l4 0"></path>
            </svg>
            Cetak Invoice
        </button>
`
    }
}


// function idevTable(formId, attrs = []) {
//     var datastring = $("#form-filter-" + formId).serialize();
//     var url = $("#form-filter-" + formId).attr("action");
//     var htmlTable = "Processing...";
//     var idTable = "#table-" + formId;
//     var paginateTable = "#paginate-" + formId;
//     var routeKey = $(".route-name").val();
//     $(idTable).css("opacity", "0.7");
//     $("button").attr("disabled", "disabled");
//     $(idTable).append(
//         "<div class='idev-loading loading-table' style='width:100%;'><img src='" +
//         baseUrl +
//         "/idev/img/loading-buffering.gif' width='34px'><br>Processing...</div>"
//     );
//     $(".count-total").text("");
//
//     $.ajax({
//         type: "GET",
//         url: url,
//         data: datastring,
//         contentType: false,
//         processData: false,
//         success: function (responses) {
//             htmlTable = "";
//             var forcePrimary = responses.force_primary;
//             var dataQueries = responses.data_queries;
//             var dataColumns = responses.data_columns;
//             var dataPermissions = responses.data_permissions;
//             var extraButtons = responses.extra_buttons ?? [];
//             var intActionCol = 0;
//             if (dataQueries) {
//                 $.each(dataQueries.data, function (key, item) {
//                     var primaryKey = forcePrimary ? item[forcePrimary] : item.id;
//
//                     var numb =
//                         1 +
//                         key +
//                         (dataQueries.current_page - 1) * dataQueries.per_page;
//                     htmlTable += "<tr>";
//                     htmlTable += "<td>" + numb + "</td>";
//                     $.each(dataColumns, function (key2, col) {
//                         var mItem = item[col] ? item[col] : "";
//                         if (mItem.length > 100) {
//                             mItem = mItem.substr(0,80)+"..."
//                         }
//                         htmlTable +=
//                             "<td class='" +
//                             formId +"-"+primaryKey+
//                             "-" +
//                             col +
//                             "'>" +
//                             mItem +
//                             "</td>";
//                     });
//                     htmlTable +=
//                         "<td class='col-action' style='white-space: nowrap;'>";
//                     $.each(extraButtons, function (key3, eb) {
//                         if (item[eb] && dataPermissions.includes(eb.replace("btn_", ""))) {
//                             htmlTable += item[eb];
//                             intActionCol++;
//                         }
//                     });
//                     htmlTable += "</td>";
//                     htmlTable += "</tr>";
//                 });
//
//                 $(".count-total-" + formId).text(
//                     "Total Data : " + dataQueries.total
//                 );
//
//                 $(idTable + " tbody").html(htmlTable);
//                 $(idTable).css("opacity", "1");
//                 $(paginateTable).html(
//                     generatePaginate(
//                         formId,
//                         dataQueries.current_page,
//                         dataQueries.links
//                     )
//                 );
//                 if (dataQueries.data.length == 0) {
//                     $(paginateTable).html("");
//                 }
//             }
//             if (intActionCol == 0) {
//                 $(idTable + " .col-action").remove();
//             }
//             $(".idev-loading").remove();
//             $("button").removeAttr("disabled");
//         },
//         error: function (xhr, status, error) {
//             $(".progress-loading").remove();
//             $("button").removeAttr("disabled");
//             var messageErr = "Something Went Wrong";
//             if (xhr.responseJSON) {
//                 messageErr =
//                     xhr.responseJSON.message == ""
//                         ? xhr.responseJSON.exception
//                         : xhr.responseJSON.message;
//             }
//             $(".table-responsive").html(
//                 "<div class='card'><div class='card-body'><strong class='text-danger'>" +
//                 messageErr +
//                 "</strong></div></div>"
//             );
//         },
//     });
// }
