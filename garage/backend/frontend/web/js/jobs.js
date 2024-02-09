var datatable = [];
let nextpagecount = 0;
let pagenumber = 1;
//let begin = 0;

window.onload = function () {
getWorkJournalAJAX();
$("#datepicker1").val(moment().format('YYYY-MM-DD'));
$("#datepicker2").val(moment().format('YYYY-MM-DD'));
localStorage.setItem('startdate', moment().format('YYYY-MM-DD'));
localStorage.setItem('enddate', moment().format('YYYY-MM-DD'));
}

$.datepicker.regional['ru'] = {
closeText: "Закрыть",
currentText: "Сегодня",
dateFormat: "yy-mm-dd",
prevText: "Предыдущий",
nextText: "Следующий",
monthNames: ['Январь','Февраль','Март','Апрель','Май','Июнь','Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'],
monthNamesShort: ['Янв','Фев','Мар','Апр','Май','Июн','Июл','Авг','Сен','Окт','Ноя','Дек'],
dayNames: ['воскресение','понедельник','вторник','среда','четверг','пятница','суббота'],
dayNamesShort:['вск','пнд','втр','срд','чтв','птн','сбт'],
dayNamesMin:['вс','пн','вт','ср','чт','пт','сб'],
firstDay: 1,
idRTL: false
};
$.datepicker.setDefaults($.datepicker.regional['ru']);

$("#datepicker1").datepicker(
{
showButtonPanel: true,
defaultDate: new Date()
}).on("change", function() {
const value = $("#datepicker1").val();
//$("#datepicker1").val(value);
localStorage.setItem('startdate', value);
console.log(value);
});

$("#datepicker2").datepicker(
{
showButtonPanel: true,
defaultDate: new Date()
}).on("change", function() {
const value = $("#datepicker2").val();
localStorage.setItem('enddate', value);
//$("#datepicker1").val(value);
console.log(value);
});


function showMessage(message, type = 'danger') {
    $.notify({
        message: message
    }, {
        element: 'body',
        placement: {
            from: "top",
            align: "right"
        },
        allow_duplicates: false,
        showAnimation: 'slideDown',
        showDuration: 400,
        hideAnimation: 'slideUp',
        hideDuration: 200,
        gap: 2,
        offset: 50,
        spacing: 10,
        z_index: 1051,
        delay: 3000,
        type: type
    });
}



function renderheader() {
$('#table-content').empty();
//шапка
$('#table-content').append(''+
'<div id="table-row" class="table-row"><div class = "table-header" style="width: 5%; font-weight: 700;">№ п/п</div>\n'+
'<div class = "table-header" style="width: 15%; font-weight: 700;">ФИО</div>\n'+
'<div class = "table-header" style="width: 5%; font-weight: 700;">Табельный номер</div>\n'+
'<div class = "table-header" style="width: 15%; font-weight: 700;">Заказчик</div>\n'+
'<div class = "table-header" style="width: 10%; font-weight: 700;">Дата время выдачи</div>\n'+
'<div class = "table-header" style="width: 10%; font-weight: 700;">Дата время выполнения</div>\n'+
'<div class = "table-header" style="width: 10%; font-weight: 700;">Смена</div>\n'+
'<div class = "table-header" style="width: 20%; font-weight: 700;">Продолжительность, минут</div></div>');

$('.table-row').css("background", "#657CAB");
}



function showAllJournal(model, begin) {
renderheader();

let n = model.length;
console.log(n);
let page = 10;
let lastpagecount = n % 10; //количество элементов на последней странице
console.log(lastpagecount);
if (n<=page) {
    page = n;
    k = 0;
    } else {
	begin--;
	nextpagecount = Math.trunc(n / page)+1; //количество страниц
	if (begin == 0) {
	    k = begin;
	    } else {
		if (begin == nextpagecount-1) {
		    page = lastpagecount;
		    
		    }
		begin *= 10;
		k = begin;
		}
	}

let t = k + 1;
for (let i=k; i<k+page; i++) {
$('#table-content').append(''+
'<div class="table-row'+i+'">\n'+
'<div class = "table-header" style="width: 5%">'+t+'</div>\n'+
'<div class = "table-header" style="width: 15%">'+model[i].fio+'</div>\n'+
'<div class = "table-header" style="width: 5%">'+model[i].tabelnom+'</div>\n'+
'<div class = "table-header" style="width: 15%">'+model[i].zakazchik+'</div>\n'+
'<div class = "table-header" style="width: 10%">'+model[i].date_begin+'</div>\n'+
'<div class = "table-header" style="width: 10%">'+model[i].date_end+'</div>\n'+
'<div class = "table-header" style="width: 10%">'+model[i].smena+'</div>\n'+
'<div class = "table-header" style="width: 20%">'+model[i].period+'</div></div>');
t++;
if (i % 2) {
$('.table-row'+i).css("background", "seashell");
$('.table-row'+i).css("display", "flex");
} else {
$('.table-row'+i).css("background", "#d9d9d9");
$('.table-row'+i).css("display", "flex");
}
}

console.log('n :'+n+' begin: '+ begin + ' : '+lastpagecount+' k: ' + k + ' : page: ' + page);
if (n>page) {
$('#table-content').append(''+
'<div class="block-pagination">\n'+
'<div class="back" id="backpage"> <<< </div>\n'+
'<div class="forward" id="forwardpage"> >>> </div>\n'+
'</div>');

}
if (begin<10) {
$('.back').css("pointer-events", "none");
}

}


$("#table-content").on("click", ".forward", function() {
pagenumber++;
showAllJournal(datatable, pagenumber);
console.log(pagenumber + ':' + nextpagecount);
$('.back').css("pointer-events", "auto");
if (nextpagecount == pagenumber ) {
$('.forward').css("pointer-events", "none");
}
});

$("#table-content").on("click", ".back", function() {
pagenumber--;
showAllJournal(datatable, pagenumber);
console.log(pagenumber + ':' + nextpagecount);
if (pagenumber == 1) {$('.back').css("pointer-events", "none");}
$('.forward').css("pointer-events", "auto");

});

function getWorkJournalAJAX() {
    $.ajax(
    {
    type: 'post',
    url: 'jobs/get-work-journal',
    dataType: 'json',
    success: function(data) {
    
    //localStorage.clear();
    //console.log(data);
    datatable = data;
    
    //sheet_to_localStorage(data);
    showAllJournal(datatable, 1);
    //var workbook = XLSX.utils.aoa_to_sheet(data);
    //console.log(workbook);
    
    
    },
    'error': function (jqXHR, exception) {
            console.log("Сервис временно не доступен.");
            showMessage("Сервис временно не доступен.", "danger");
            if (jqXHR.status === 0) {
                console.error('Запрос отменен или прерван');
            } else if (jqXHR.status == 404) {
                console.error('НЕ найдена страница запроса [404])');
            } else if (jqXHR.status == 500) {
                console.error('Внутренняя ошибка сервера [500].\n' + jqXHR.responseText);
            } else if (exception === 'parsererror') {
                console.error("Ошибка парсинга: \n" + jqXHR.responseText);
            } else if (exception === 'timeout') {
                console.error('Сервер не ответил на запрос.');
            } else if (exception === 'abort') {
                console.error('Прерван запрос Ajax.');
            } else {
                console.error('Неизвестная ошибка:\n' + jqXHR.responseText);
            }
        }
});
}

$("#refresh").on("click", function() {
nextpagecount = 0;
pagenumber = 1;
getWorkJournalDateAJAX();
});

function getWorkJournalDateAJAX() {
    $.ajax(
    {
    type: 'post',
    url: 'jobs/get-work-journal-date',
    dataType: 'json',
    data: {
    "startdate": localStorage.getItem('startdate'),
    "enddate": localStorage.getItem('enddate'),
    },
    success: function(data) {
    console.log(data);
    datatable = data;
    showAllJournal(datatable, 1);
    },
    'error': function (jqXHR, exception) {
            console.log("Сервис временно не доступен.");
            showMessage("Сервис временно не доступен.", "danger");
            if (jqXHR.status === 0) {
                console.error('Запрос отменен или прерван');
            } else if (jqXHR.status == 404) {
                console.error('НЕ найдена страница запроса [404])');
            } else if (jqXHR.status == 500) {
                console.error('Внутренняя ошибка сервера [500].\n' + jqXHR.responseText);
            } else if (exception === 'parsererror') {
                console.error("Ошибка парсинга: \n" + jqXHR.responseText);
            } else if (exception === 'timeout') {
                console.error('Сервер не ответил на запрос.');
            } else if (exception === 'abort') {
                console.error('Прерван запрос Ajax.');
            } else {
                console.error('Неизвестная ошибка:\n' + jqXHR.responseText);
            }
        }
});
}





$("#back2").on("click", function() {

window.location = '/';

});


$("#excel").on("click", function() {

showMessage("выгрузка выполнена", "success");
console.log(datatable);
var data_headers = ["№ п/п","ФИО","Табельный номер","Зазазчик","Дата и время выдачи","Дата и время выполнения","Смена","Продолжительность, минут"];
var ws = XLSX.utils.json_to_sheet(datatable);

//function fitToColumn(datatable) {
//return datatable[0].map((a,i) => (
//{wch: Math.max(...datatable.map(a2 => a2[i] ? a2[i].toString().length : 0))}));
//}
var wscols = [
{wch: 5},
{wch: 15},
{wch: 18},
{wch: 24},
{wch: 8},
{wch: 24},
{wch: 25},
{wch: 25}
]
var wsrows = [
{hpt: 16},
{hpx: 20}
]
ws['!rows']=wsrows;
ws['!cols']=wscols;
ws["A1"]={v: "№ п/п", t: "s"};
ws["B1"]={v: "ФИО", t: "s"};
ws["C1"]={v: "Табельный номер", t: "s"};
ws["D1"]={v: "Заказчик", t: "s"};
ws["E1"]={v: "Смена", t: "s"};
ws["G1"]={v: "Дата и время выдачи", t: "s"};
ws["H1"]={v: "Дата и время выполнения", t: "s"};
ws["F1"]={v: "Продолжительность, мин", t: "s"};
//console.log(ws);
//var wb = XLSX.utils.book_new();
//XLSX.utils.sheet_add_aoa(ws, [data_headers], {origin: "A1"});
var wb = XLSX.utils.book_new();
XLSX.utils.book_append_sheet(wb,ws,"sheet1");
XLSX.writeFile(wb, "temp.xlsx");
});


$("#report").on("click", function() {
console.log(datatable);
});


