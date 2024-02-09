let buildStart = 0, buildEnd = 25;
let debug = true;

createTabButtons(fileList, 0,5);
buildTableFileList(fileList, buildStart, buildEnd);

(function ($) {
    $.fn.uploader = function (options) {
        let settings = $.extend({
            MessageAreaText: "Файл не выбран",
            MessageAreaTextWithFiles: "Нажмите &laquo;Загрузить&raquo;",
            DefaultErrorMessage: "Невозможно открыть файл",
            BadTypeErrorMessage: "Этот тип данных не подходит, выберите другой",
            acceptedFileTypes: ['csv', 'excel', 'odt', 'ods', 'rar', 'zip', "txt", '7z', 'sql', 'pdf', 'word', 'jpg', 'png']
        }, options);

        let uploadId = 1;
        //update the messaging
        $('.file-uploader__message-area span').text(options.MessageAreaText || settings.MessageAreaText);

        //create and add the file list and the hidden input list
        let fileList = $('<ul class="file-list"></ul>');
        let hiddenInputs = $('<div class="hidden-inputs hidden"></div>');
        $('.file-uploader__message-area').after(fileList);
        $('.file-list').after(hiddenInputs);

        //when choosing a file, add the name to the list and copy the file input into the hidden inputs
        $('.file-chooser__input').on('change', function () {
            let file = $('.file-chooser__input').val();
            console.log(file);
            console.log(this.files[0]);
            let fileName = (file.match(/([^\\\/]+)$/)[0]);

            //clear any error condition
            $('.file-chooser').removeClass('error');
            $('.error-message').remove();

            //validate the file
            let check = checkFile(fileName);
            if (check === "valid") {

                // move the 'real' one to hidden list
                $('.hidden-inputs').append($('.file-chooser__input'));

                //insert a clone after the hiddens (copy the event handlers too)
                $('.file-chooser').append($('.file-chooser__input').clone({withDataAndEvents: true}));

                //add the name and a remove button to the file-list
                $('.file-list').html('<li style="display: none;"><span class="file-list__name">' + fileName + '</span><button class="removal-button" data-uploadid="' + uploadId + '"></button></li>');
                $('.file-list > li').show(1200);

                //removal button handler
                $('.removal-button').on('click', function (e) {
                    e.preventDefault();
                    document.getElementsByClassName('file-uploader')[0].reset();
                    //remove the corresponding hidden input
                    $('.hidden-inputs input[data-uploadid="' + $(this).data('uploadid') + '"]').remove();

                    //remove the name from file-list that corresponds to the button clicked
                    $(this).parent().hide("puff").delay(10).queue(function () {
                        $(this).remove();
                    });

                    //if the list is now empty, change the text back

                        $('.file-uploader__message-area span').text("Выберите файл для загрузки");

                });

                //so the event handler works on the new "real" one
                $('.hidden-inputs .file-chooser__input').removeClass('file-chooser__input').attr('data-uploadId', uploadId);

                //update the message area
                $('.file-uploader__message-area span').html(options.MessageAreaTextWithFiles || settings.MessageAreaTextWithFiles);

                uploadId++;

            } else {
                //indicate that the file is not ok
                $('.file-chooser').addClass("error");
                let errorText = options.DefaultErrorMessage || settings.DefaultErrorMessage;

                if (check === "badFileName") {
                    errorText = options.BadTypeErrorMessage || settings.BadTypeErrorMessage;
                }

                $('.file-chooser__input').after('<p class="error-message">' + errorText + '</p>');
            }
            uploadToServer();
        });

        let checkFile = function (fileName) {
            let accepted = "invalid",
                acceptedFileTypes = this.acceptedFileTypes || settings.acceptedFileTypes,
                regex;

            for (let i = 0; i < acceptedFileTypes.length; i++) {
                regex = new RegExp("\\." + acceptedFileTypes[i] + "$", "i");

                if (regex.test(fileName)) {
                    accepted = "valid";
                    break;
                } else {
                    accepted = "badFileName";
                }
            }

            return accepted;
        };
    };
}(jQuery));

//init
$(document).ready(function () {
    $('.fileUploader').uploader({
        MessageAreaText: "Выберите файлы для загрузки"
    });
});


// отправка формы
// function (){
//     if( file_dowload_my.length > 0 ){
//         //если в поле лежит что то
//         $.ajax({
//                 type: "POST", //тип ппередачи данных
//                 url: "file-uploader/uploader-file", //путь к файлу который принимает данные
//                 data: {
//                     'link':
//
//                 }
//             }
//
//
//         )
//     }
// };


function uploadToServer() {
    // console.log('Функция вызвана');
    const formData = new FormData();                                                                                       // FormData зарезервированнаый объект
    let uploadBtn = document.getElementById('uploadButton');                                                                 // левая часть задает переменную , которая ровняется правой части, что бы потом не писать полностью весь метод поиска документа
    uploadBtn.onclick = function (e) {
        // console.log('click');
        let uploadInput = document.getElementById('uploadInput');
        // console.warn(uploadInput.files[0].type);
        // console.warn(uploadInput.files[0]);
        let fileExtension = uploadInput.files[0].name.split('.');
        fileExtension = fileExtension[fileExtension.length - 1];
        console.log('file ext is ' + fileExtension);
        const memorySizeLimit = 50 * 1024 * 1024 + 1024;
        if (uploadInput.files[0].size > memorySizeLimit) {

            if (fileExtension === "sql") {
                console.log('зашли в условие размер файла больше 50 МБ и расширение файла sql');
                formData.append("file", uploadInput.files[0]);                                                                         // у FormData всегда название ключа должна быть строка
                formData.append("file_name", uploadInput.files[0].name);
                //formData.append("file_type", splittedName[1]);
                $.ajax({
                    xhr: function()

                    {
                        console.log("пошел аджакс на загрузку файла");
                        const xhr = new XMLHttpRequest();
                       const progressBar = document.getElementById('uploadProgressBar');
                        progressBar.parentNode.classList.remove("hidden");
                        xhr.addEventListener("progress", function(evt){
                            console.error("progress bar 2");
                            if (evt.lengthComputable) {
                                const progressBar2 = document.getElementById('uploadProgressBar2');

                                let percentComplete = Math.ceil(evt.loaded / evt.total * 100);
                                progressBar2.setAttribute('aria-valuenow', percentComplete + '%');
                                progressBar2.style.width = percentComplete + '%';
                                progressBar2.textContent = percentComplete + '%';

                                if (percentComplete === 100) {
                                    setTimeout(function(){
                                        progressBar.parentNode.classList.add("hidden");
                                        progressBar.setAttribute('aria-valuenow', '0');
                                        progressBar.style.width = '0';
                                        progressBar.textContent = 0 + '%';

                                        e.preventDefault();
                                        document.getElementsByClassName('file-uploader')[0].reset();
                                        //remove the corresponding hidden input
                                        $('.hidden-inputs input').remove();

                                        //remove the name from file-list that corresponds to the button clicked
                                        $('.file-list li').hide("puff").delay(10).queue(function () {
                                            $('.file-list .removal-button').remove();
                                        });

                                        //if the list is now empty, change the text back

                                        $('.file-uploader__message-area span').text("Выберите файл для загрузки");
                                    }, 1000);

                                }

                            }

                        }, false);

                        return xhr;

                    },//тело запроса
                    type: "POST",                                                                                            //тип запроса
                    url: "file-uploader/upload-file",                                                                   //адрес функции в контроллере, принимающий данные
                    dataType: "json",                                                                                        //тип принимаемых данных с сервера
                    data: formData,                                                                                          //передаваемые данные
                    cache: false,                                                                                            //не сохранять в кэш данные
                    processData: false,                                                                                      //не преобразовывать в строку передаваемые данные
                    contentType: false,                                                                                      //не следить за типом данных
                    error: function (jqXHR, exception) {                                                                     //обработчик ошибок
                        showNotify("Сервис временно не доступен.");
                        if (jqXHR.status === 0) {
                            console.error('Запрос отменен или прерван');
                        } else if (jqXHR.status == 404) {
                            console.error('НЕ найдена страница запроса [404])');
                        } else if (jqXHR.status == 500) {
                            console.error('Внутренняя ошибка сервера [500].\n' + jqXHR.responseText);
                        } else if (exception === 'parsererror') {
                            console.error("Ошибка в коде: \n" + jqXHR.responseText);
                        } else if (exception === 'timeout') {
                            console.error('Сервер не ответил на запрос.');
                        } else if (exception === 'abort') {
                            console.error('Прерван запрос Ajax.');
                        } else {
                            console.error('Неизвестная ошибка:\n' + jqXHR.responseText);
                        }
                    },
                    success: function (response) {                                                                          // обработчик события которое наступает когда приходит ответ с сервера без ошибок
                        console.log(response);
                        if (response.errors.length) {                                                                       // проверяем  массив ошибок на наличие элементов
                            showNotify(response.errors[0]);                                                                 // если массив не пустой , то вызываем функцию отображения текста ошибки
                        } else {
                            createTabButtons(fileList, 0,5);
                            buildTableFileList(response.file_list, buildStart, buildEnd);                                                         // иначе строим таблицу со списком
                            showNotify("Файл успешно загружен", 'success');

                        }
                    }
                });
                console.log(formData.get('file'));
                console.log(formData.get('file_name'));
            } else {
                showNotify("Допустимо загружать файлы размером менее 50 МБ");
            }
        } else {
            if (uploadInput.files[0].type === "application/zip" ||
                uploadInput.files[0].type === "application/x-zip-compressed" ||
                uploadInput.files[0].type === "application/pdf" ||
                uploadInput.files[0].type === "application/x-rar-compressed" ||
                fileExtension === "7z" ||
                fileExtension === "rar" ||
                fileExtension === "sql" ||
                uploadInput.files[0].type === "text/csv" ||
                uploadInput.files[0].type === "text/plain" ||
                uploadInput.files[0].type === "image/jpeg" ||
                uploadInput.files[0].type === "image/png" ||
                uploadInput.files[0].type === "application/x-rar-compressed" ||
                uploadInput.files[0].type === "application/vnd.oasis.opendocument.text" ||
                uploadInput.files[0].type === "application/vnd.oasis.opendocument.spreadsheet" ||
                uploadInput.files[0].type === "application/vnd.ms-excel") {
                console.log('зашли в условие размер файла менее 50 мб');

                formData.append("file", uploadInput.files[0]);                                                                         // у FormData всегда название ключа должна быть строка
                formData.append("file_name", uploadInput.files[0].name);
                //formData.append("file_type", splittedName[1]);
                $.ajax({                                                                                                     //тело запроса
                    xhr: function() {
                       console.log("пошел аджакс на загрузку файла");
                        const xhr = new XMLHttpRequest();
                       const progressBar = document.getElementById('uploadProgressBar');
                        // прогресс скачивания с сервера
                        progressBar.parentNode.classList.remove("hidden");
                        xhr.addEventListener("progress", function(evt){
                            if (evt.lengthComputable) {

                                let percentComplete = Math.ceil(evt.loaded / evt.total * 100);
                                progressBar.setAttribute('aria-valuenow', percentComplete);
                                progressBar.style.width = percentComplete + '%';
                                progressBar.textContent = percentComplete + '%';

                                if (percentComplete === 100) {
                                    setTimeout(function(){
                                        progressBar.parentNode.classList.add("hidden");
                                        progressBar.setAttribute('aria-valuenow', '0');
                                        progressBar.style.width = '0';
                                        progressBar.textContent = 0 + '%';

                                        e.preventDefault();
                                        document.getElementsByClassName('file-uploader')[0].reset();
                                        //remove the corresponding hidden input
                                        $('.hidden-inputs input').remove();

                                        //remove the name from file-list that corresponds to the button clicked
                                        $('.file-list li').hide("puff").delay(10).queue(function () {
                                            $('.file-list .removal-button').remove();
                                        });

                                        //if the list is now empty, change the text back

                                        $('.file-uploader__message-area span').text("Выберите файл для загрузки");
                                    }, 1000);

                                }

                            }

                        }, false);

                        return xhr;
                    },
                    type: "POST",                                                                                            //тип запроса
                    url: "file-uploader/upload-file",                                                                   //адрес функции в контроллере, принимающий данные
                    dataType: "json",                                                                                        //тип принимаемых данных с сервера
                    data: formData,                                                                                          //передаваемые данные
                    cache: false,                                                                                            //не сохранять в кэш данные
                    processData: false,                                                                                      //не преобразовывать в строку передаваемые данные
                    contentType: false,                                                                                      //не следить за типом данных
                    error: function (jqXHR, exception) {                                                                     //обработчик ошибок
                        showNotify("Сервис временно не доступен.");
                        if (jqXHR.status === 0) {
                            console.error('Запрос отменен или прерван');
                        } else if (jqXHR.status == 404) {
                            console.error('НЕ найдена страница запроса [404])');
                        } else if (jqXHR.status == 500) {
                            console.error('Внутренняя ошибка сервера [500].\n' + jqXHR.responseText);
                        } else if (exception === 'parsererror') {
                            console.error("Ошибка в коде: \n" + jqXHR.responseText);
                        } else if (exception === 'timeout') {
                            console.error('Сервер не ответил на запрос.');
                        } else if (exception === 'abort') {
                            console.error('Прерван запрос Ajax.');
                        } else {
                            console.error('Неизвестная ошибка:\n' + jqXHR.responseText);
                        }
                    },
                    success: function (response) {                                                                          // обработчик события которое наступает когда приходит ответ с сервера без ошибок
                        console.log(response);
                        if (response.errors.length) {                                                                       // проверяем  массив ошибок на наличие элементов
                            showNotify(response.errors[0]);                                                                 // если массив не пустой , то вызываем функцию отображения текста ошибки
                        } else {
                            createTabButtons(fileList, 0,5);
                            buildTableFileList(response.file_list, buildStart, buildEnd);                                                         // иначе строим таблицу со списком
                            showNotify("Файл успешно загружен", 'success');
                        }
                    }
                });
                // console.log(formData.get('file'));
                // console.log(formData.get('file_name'));
                // console.log(formData.get('file_type'));
            } else {
                showNotify("Выберите нужный тип файла");
            }
        }

    };
}


function showNotify(error_text, type = "danger") {
    ////console.log(error_text);
    $.notify(
        {
            // options
            icon: type == "danger" ? 'glyphicon glyphicon-warning-sign' : 'glyphicon glyphicon-ok',
            message: error_text
        },
        {
            // settings
            element: 'body',
            position: 'fixed',
            type: type,
            allow_dismiss: true,
            newest_on_top: true,
            showProgressbar: false,
            placement: {
                from: "top",
                align: "center"
            },
            offset: 100,
            spacing: 10,
            z_index: 1051,
            delay: 5000,
            timer: 1000,
            mouse_over: null,
            animate: {
                enter: 'animated fadeInDown',
                exit: 'animated fadeOutUp'
            },
            onShow: null,
            onShown: null,
            onClose: null,
            onClosed: null,
            icon_type: 'class',
        }
    );
}

function buildTableFileList(fileArray, start, end) {
    let debug = false;
    if (debug) {
        console.log('функция для построения таблицы со списком файлов');
        console.log('Выводим массив файлов');
        console.log(fileArray);
    }
    let tableBody = document.getElementsByClassName('table-body')[0];                                                   // обращение к элементу про классу , но так как он один. указываем 0
    tableBody.innerHTML = '';                                                                                           // очистка всего содержимоого элемента(узла) , замена .
    tableBody.parentNode.scrollTop = 0;                                                                                   // прокрутка к верху страницы, через родителя.
    let fileArrayLength = fileArray.length,
        strFile = '';
    if (fileArrayLength) {
        // console.log("array is not empty");
        if (end > fileArrayLength) {
            // console.log('first case');
            for (let i = start; i < fileArrayLength; i++) {
                let fileSize = "",
                    tempFileSize = Number(fileArray[i].file_size);
                if (tempFileSize < 1048576 && tempFileSize > 1023) {
                    fileSize = (tempFileSize / 1024).toFixed(2) + ' КБ';
                } else if (tempFileSize >= 1048576 && tempFileSize < 1073741824) {
                    fileSize = (tempFileSize / 1024 / 1024).toFixed(2) + ' МБ'
                } else if (tempFileSize >= 1073741824) {
                    fileSize = (tempFileSize / 1024 / 1024 / 1024).toFixed(2) + ' ГБ'
                } else {
                    fileSize = tempFileSize + (tempFileSize[tempFileSize.length - 1] === 2 ||
                    tempFileSize[tempFileSize.length - 1] === 3 ||
                    tempFileSize[tempFileSize.length - 1] === 4 ? ' Байта' : ' Байт')
                }
                // console.error(fileSize);
                strFile += '<div class="file-rowJs">' +
                    '<div class="tableJS table-ordered-number"><span>' + (i + 1) + '</span></div>' +
                    '<div class="tableJS table-file-name"><span>' + fileArray[i].file_name + '</span></div>' +
                    '<div class="tableJS table-file-size"><span>' + fileSize + '</span></div>' +
                    '<div class="tableJS table-upload-date"><span>' + fileArray[i].date_time + '</span></div>' +
                    '<div class="tableJS table-download-header"><span><a href="' + fileArray[i].url + '" download>скачать</a></span></div>' +
                    '<div class="tableJS table-delete-file"><span><a data-file-name="' + fileArray[i].file_name + '">удалить</a></span></div>' +
                    '</div>';
            }
            tableBody.innerHTML = strFile;
            buildEmpty(fileArrayLength, start, end);
        } else {
            // console.log('case 2');
            for (let i = start; i < end; i++) {
                let fileSize = "",
                    tempFileSize = Number(fileArray[i].file_size);
                if (tempFileSize < 1048576 && tempFileSize > 1023) {
                    fileSize = (tempFileSize / 1024).toFixed(2) + ' КБ';
                } else if (tempFileSize >= 1048576 && tempFileSize < 1073741824) {
                    fileSize = (tempFileSize / 1024 / 1024).toFixed(2) + ' МБ'
                } else if (tempFileSize >= 1073741824) {
                    fileSize = (tempFileSize / 1024 / 1024 / 1024).toFixed(2) + ' ГБ'
                } else {
                    fileSize = tempFileSize + (tempFileSize[tempFileSize.length - 1] === 2 ||
                    tempFileSize[tempFileSize.length - 1] === 3 ||
                    tempFileSize[tempFileSize.length - 1] === 4 ? ' Байта' : ' Байт')
                }
                // console.error(fileSize);
                strFile += '<div class="file-rowJs">' +
                    '<div class="tableJS table-ordered-number"><span>' + (i + 1) + '</span></div>' +
                    '<div class="tableJS table-file-name"><span>' + fileArray[i].file_name + '</span></div>' +
                    '<div class="tableJS table-file-size"><span>' + fileSize + '</span></div>' +
                    '<div class="tableJS table-upload-date"><span>' + fileArray[i].date_time + '</span></div>' +
                    '<div class="tableJS table-download-header"><span><a href="' + fileArray[i].url + '" download>скачать</a></span></div>' +
                    '<div class="tableJS table-delete-file"><span><a data-file-name="' + fileArray[i].file_name + '">удалить</a></span></div>' +
                    '</div>';
            }
            tableBody.innerHTML = strFile;
            switchPage(fileArray);

        }


        let deleteFileButtonArray = document.querySelectorAll(".table-delete-file > span > a"),
            deleteFileButtonArrayLength = deleteFileButtonArray.length;
        for (let i = 0; i < deleteFileButtonArrayLength; i++) {
            deleteFileButtonArray[i].onclick = function () {
                console.log('зашли в удаление теперь что?');
                    document.getElementById('modal-body_span').innerHTML=deleteFileButtonArray[i].dataset.fileName;
                    console.log('dsвыводтим спам');
                    document.getElementById('deleteParameter').style.display='flex';
                    document.getElementById('button-content_delete-yes').onclick =function() {
                        console.log('удаляем файл');
                        ajaxDeleteFile(deleteFileButtonArray[i].dataset.fileName);
                        document.getElementById('deleteParameter').style.display='none';
                    }
                        document.getElementById('button-content_delete-no').onclick = function (){
                        document.getElementById('deleteParameter').style.display="none";
                }
            };

        }
    } else {
        tableBody.innerHTML = '<div class="file-rowJs empty-row">' +
            '<div class="tableJS table-ordered-number"><span></span></div>' +
            '<div class="tableJS table-file-name"><span>Нет файлов на сервере</span></div>' +
            '<div class="tableJS table-file-size"><span></span></div>' +
            '<div class="tableJS table-upload-date"><span></span></div>' +
            '<div class="tableJS table-download-header"><span></span></div>' +
            '<div class="tableJS table-delete-file"><span></span></div>' +
            '</div>';
        buildEmpty(fileArrayLength, start, end);
    }
}

function ajaxDeleteFile(file_name) {                                                                                      ///////////////////функция отправки  запроса на удаление файла
    console.log(file_name);
    $.ajax({                                                                                                             //тело запроса
        type: "POST",                                                                                                    //тип запроса
        url: "file-uploader/delete-file",                                                                                //адрес функции в контроллере, принимающий данные
        dataType: "json",                                                                                                //тип принимаемых данных с сервера
        data: {
            file_name: file_name
        },                                                                                                              //передаваемые данные
        error: function (jqXHR, exception) {                                                                            //обработчик ошибок
            showNotify("Сервис временно не доступен.");
            if (jqXHR.status === 0) {
                console.error('Запрос отменен или прерван');
            } else if (jqXHR.status == 404) {
                console.error('НЕ найдена страница запроса [404])');
            } else if (jqXHR.status == 500) {
                console.error('Внутренняя ошибка сервера [500].\n' + jqXHR.responseText);
            } else if (exception === 'parsererror') {
                console.error("Ошибка в коде: \n" + jqXHR.responseText);
            } else if (exception === 'timeout') {
                console.error('Сервер не ответил на запрос.');
            } else if (exception === 'abort') {
                console.error('Прерван запрос Ajax.');
            } else {
                console.error('Неизвестная ошибка:\n' + jqXHR.responseText);
            }
        },
        success: function (response) {                                                                                  // обработчик события которое наступает когда приходит ответ с сервера без ошибок
            console.log(response);
            if (response.errors.length) {                                                                               // проверяем  массив ошибок на наличие элементов
                showNotify(response.errors[0]);                                                                         // если массив не пустой , то вызываем функцию отображения текста ошибки
            } else {
                createTabButtons(fileList, 0,5);
                buildTableFileList(response.file_list, buildStart, buildEnd);                                                                 // иначе строим таблицу со списком
            }
        }
    });
}

function buildEmpty(arrLength, buildStart, buildEnd) {
    for (let i = arrLength; i < buildEnd; i++) {
        $(".table-body").append('<div class="file-rowJs empty-row">' +
            '<div class="tableJS table-ordered-number"><span></span></div>' +
            '<div class="tableJS table-file-name"><span></span></div>' +
            '<div class="tableJS table-file-size"><span></span></div>' +
            '<div class="tableJS table-upload-date"><span></span></div>' +
            '<div class="tableJS table-download-header"><span></span></div>' +
            '<div class="tableJS table-delete-file"><span></span></div>' +
            '</div>');
    }

}

function switchPage(arr) {
    const nextPage = document.getElementById('nextPage'),
        previousPage = document.getElementById('previousPage');
    previousPage.onclick = function() {
        if ((buildStart + 1) > 1) {
            buildStart -= 25;
            buildEnd -= 25;
            createTabButtons(fileList, 0,5);
            buildTableFileList(arr, buildStart, buildEnd);
        }

    };
    nextPage.onclick = function() {
        if (buildEnd < arr.length) {
            buildStart += 25;
            buildEnd += 25;
            createTabButtons(fileList, 0,5);
            buildTableFileList(arr, buildStart, buildEnd);
        }
    };
    // console.log(start+" : " + end );
}


/*********************************************************
 **               функция работы с футером              **
 **                 сортировка кнопопочек               **
 ********************************************************/
function createTabButtons(model, firstButton, lastButton) {
    /******************************************************************/
    if(debug) console.log('Массив данных:');
    if(debug) console.log(model);
    if(debug) console.log(firstButton);
    if(debug) console.log(lastButton);
    /******************************************************************/
    let n = model.length, buttonsNumber;                                                                             // Объявление переменных: длина массива данных, количество страниц
    $('.handbook-content__footer__pagination').html('');                                                                // Очищаем контейнер подвала
    if(n > 0) {                                                                                                         // Если есть какие-то данные
        buttonsNumber = Math.ceil(n / 25);                                                                              // Получаем количество страниц
        /******************************************************************/
        if(debug) console.log('Количество страниц: '+buttonsNumber);
        /******************************************************************/
        if(buttonsNumber <= 5) {                                                                                        // Если количество страниц меньше или равно пяти
            $('.handbook-content__footer__pagination').append('' +                                                      // Добавляем кнопку первой страницы
                '<button class="handbook-page-switch" id="first-page"><<</button>');
            $('.handbook-content__footer__pagination').append('' +                                                      // Добавляем кнопку предыдущей страницы
                '<button class="handbook-page-switch" id="previous-page"><</button>');
            for(let i = 0; i < buttonsNumber; i++) {                                                                    // В цикле строим кнопки с нумерацией
                $('.handbook-content__footer__pagination').append('' +
                    '<button class="handbook-page-switch numeric" id="page-'+(i + 1)+'">'+(i + 1)+'</button>');
            }
            $('.handbook-content__footer__pagination').append('' +                                                      // Добавляем декоративную кнопку [...]
                '<button class="handbook-page-switch" id="three-dots-button">...</button>');
            $('.handbook-content__footer__pagination').append('' +                                                      // Добавляем кнопку следующей страницы
                '<button class="handbook-page-switch" id="next-page">></button>');
            $('.handbook-content__footer__pagination').append('' +                                                      // Добавляем кнопку последней страницы
                '<button class="handbook-page-switch" id="last-page">>></button>');
        }
        else {                                                                                                          // Если больше пяти
            $('.handbook-content__footer__pagination').append('' +                                                      // Добавляем кнопку первой страницы
                '<button class="handbook-page-switch" id="first-page"><<</button>');
            $('.handbook-content__footer__pagination').append('' +                                                      // Добавляем кнопку предыдущей страницы
                '<button class="handbook-page-switch" id="previous-page"><</button>');
            for(let i = firstButton; i < lastButton; i++) {                                                             // В цикле строим кнопки с нумерацией
                $('.handbook-content__footer__pagination').append('' +
                    '<button class="handbook-page-switch numeric" id="page-'+(i + 1)+'">'+(i + 1)+'</button>');
            }
            $('.handbook-content__footer__pagination').append('' +                                                      // Добавляем декоративную кнопку [...]
                '<button class="handbook-page-switch" id="three-dots-button">...</button>');
            $('.handbook-content__footer__pagination').append('' +                                                      // Добавляем кнопку следующей страницы
                '<button class="handbook-page-switch" id="next-page">></button>');
            $('.handbook-content__footer__pagination').append('' +                                                      // Добавляем кнопку последней страницы
                '<button class="handbook-page-switch" id="last-page">>></button>');
        }
        $('.handbook-page-switch:nth-of-type(3)').addClass('selected-page');                                            // Выделяем кнопку первой страницы
    }
    else {                                                                                                              // Если контента на странице нет
        /******************************************************************/
        if(debug) console.log('======================');
        if(debug) console.log('Количество страниц: '+n);
        if(debug) console.log('Контент отсутствует');
        if(debug) console.log('======================');
        /******************************************************************/
        buttonsNumber = 1;
        $('.handbook-content__footer__pagination').append('' +                                                          // Добавляем кнопку первой страницы
            '<button class="handbook-page-switch" id="first-page"><<</button>');
        $('.handbook-content__footer__pagination').append('' +                                                          // Добавляем кнопку предыдущей страницы
            '<button class="handbook-page-switch" id="previous-page"><</button>');
        $('.handbook-content__footer__pagination').append('' +                                                          // Добавляем одну кнопку
            '<button class="handbook-page-switch numeric" id="page-1">1</button>');
        $('.handbook-content__footer__pagination').append('' +                                                          // Добавляем декоративную кнопку [...]
            '<button class="handbook-page-switch" id="three-dots-button">...</button>');
        $('.handbook-content__footer__pagination').append('' +                                                          // Добавляем кнопку следующей страницы
            '<button class="handbook-page-switch" id="next-page">></button>');
        $('.handbook-content__footer__pagination').append('' +                                                          // Добавляем кнопку последней страницы
            '<button class="handbook-page-switch" id="last-page">>></button>');
        $('.handbook-page-switch:nth-of-type(3)').addClass('selected-page');                                            // Выделяем кнопку первой страницы
    }
    Array.from(document.getElementsByClassName('handbook-page-switch')).forEach(item => {                               // Собираем массив элементов с классом 'handbook-page-switch'
        let currentPage = 0, nextPage = 0, previousPage = 0;
        item.onclick = function() {                                                                                     // Обработка события клика по элементу массива
            if(item.id !== 'three-dots-button' &&                                                                       // Если id элемента не 'three-dots-button
                item.id !== 'first-page' &&                                                                             // И не 'first-page
                item.id !== 'previous-page' &&                                                                          // И не 'previous-page
                item.id !== 'next-page' &&                                                                              // И не 'next-page'
                item.id !== 'last-page') {                                                                              // И не 'last-page
                let start = ((item.id).split('-'))[1] * 25 - 25;                                                        // Задаем стартовую переменную для построения контента
                let end = ((item.id).split('-'))[1] * 25;                                                               // Задаем конечную переменную для построения контента
                Array.from(document.getElementsByClassName('handbook-page-switch')).forEach(item => {                   // Собираем массив элементов с классом 'handbook-page-switch'
                    $('#'+item.id).removeClass('selected-page');                                                        // У всех элементов удаляем класс 'selected-page'
                });
                $('#'+item.id).addClass('selected-page');                                                               // Тому элементу, по которому кликнули добавляем класс 'selected-page'
                /******************************************************************/
                if (debug) console.log('Начало построения: ' + start + ' Конец построения: ' + end);
                buildContent(start, end);                                                                               // Вызов функции заполнения таблицы
                /******************************************************************/
            }
            else if(item.id === 'first-page') {                                                                         // Если id элемента 'first-page
                if(buttonsNumber > 5) {                                                                                 // Если количество страниц больше пяти
                    createTabButtons(0, 5);                                                                             // Отображаем пять кнопок
                }
                else {                                                                                                  // Если меньше или равно
                    createTabButtons(0, buttonsNumber);                                                                 // То отображаем сколько есть
                    Array.from(document.getElementsByClassName('handbook-page-switch')).forEach(item => {               // Собираем массив элементов с классом 'handbook-page-switch'
                        $('#' + item.id).removeClass('selected-page');                                                  // У всех элементов убираем класс 'selected-page'
                    });
                    $('#page-1').addClass('selected-page');                                                             // Кнопке первой страницы добавляем класс 'selected-page'
                    /******************************************************************/
                    if (debug) console.log('Переключение на первую страницу');
                    /******************************************************************/
                }
                buildContent(0, 25);                                                                                    // Вызов функции заполнения таблицы
            }
            else if(item.id === 'last-page') {                                                                          // Если id элемента 'last-page'
                let matematicVar = String(buttonsNumber / 5);
                matematicVar = (matematicVar.split('.'))[1];                                                            // Волшебные математические расчеты для построения последних кнопок
                matematicVar = Number('0.'+matematicVar) * 5;
                if(matematicVar) {
                    createTabButtons(buttonsNumber - matematicVar, buttonsNumber);                                          // Отображаем последние пять кнопок
                }
                else createTabButtons(buttonsNumber - 5, buttonsNumber);
                Array.from(document.getElementsByClassName('handbook-page-switch')).forEach(item => {                   // Собираем массив элементов с классом 'handbook-page-switch'
                    $('#'+item.id).removeClass('selected-page');                                                        // У всех элементов убираем класс 'selected-page'
                });
                $('#page-'+buttonsNumber).addClass('selected-page');                                                    // Кнопке последней страницы добавляем класс 'selected-page'
                /******************************************************************/
                if(debug) console.log('Переключение на последнюю страницу с id: page-'+buttonsNumber);
                let matematicVar2 = String(n / 25);
                matematicVar2 = (matematicVar2.split('.'))[1];
                matematicVar2 = Number('0.'+matematicVar2) * 25;                                                        // Волшебные математические расчеты для построения последней страницы
                buildContent(n - matematicVar2, n);                                                                     // Вызов функции заполнения таблицы
                if(debug) console.log(n - matematicVar2, n);
                /******************************************************************/
            }
            else if(item.id === 'next-page') {                                                                          // Если id элемента 'next-page
                Array.from(document.getElementsByClassName('numeric')).forEach(item => {                                // Собираем массив элементов с классом 'handbook-page-switch
                    if($('#'+item.id).hasClass('selected-page')) {                                                     // Находим элемент с классом 'selected-page'
                        /******************************************************************/
                        if(debug) console.log(item);
                        if(debug) console.log(item.id);
                        if(debug) console.log(((item.id).split('-'))[1]);
                        /******************************************************************/
                        currentPage = ((item.id).split('-'))[1];                                                       // Считаем текущую страницу
                        nextPage = Number(currentPage) + 1;                                                            // Считаем следующую страницу
                    }
                });
                let a = document.querySelectorAll('.numeric');                                                          // Находим последнюю кнопку из кнопок с номерами страниц
                let an = a.length - 1;
                a = a[an];
                if(currentPage < buttonsNumber) {                                                                       // Если это не последняя страница
                    if($(a).hasClass('selected-page')) {                                                                // Если последняя кнопка в ряду имеет класс 'selected-page'
                        if((nextPage - 1) < buttonsNumber - 5) {                                                        // Если на следующую страницу влезет еще пять кнопок
                            createTabButtons(nextPage - 1, nextPage + 4);                                               // Перелистываем и строим пять штук
                            /******************************************************************/
                            if(debug) console.log('Перелистывание списка страниц вперед');
                            /******************************************************************/
                        }
                        else {
                            createTabButtons(nextPage - 1, buttonsNumber);                                              // Если нет - перелистываем и строим, сколько нужно
                        }
                    }
                    /******************************************************************/
                    if(debug) console.log('Текущая кнопка имеет id: page-'+currentPage);
                    if(debug) console.log('Следующая кнопка имеет id: page-'+nextPage);
                    /******************************************************************/
                    $('.handbook-page-switch').removeClass('selected-page');                                            // Удаляем всем кнопкам класс 'selected-page'
                    $('#page-'+nextPage).addClass('selected-page');                                                     // Добавляем текущей кнопке класс 'selected-page'
                    buildContent(((nextPage * 25) - 25), (nextPage * 25));                                              // Вызов функции заполнения таблицы
                }
            }
            else if (item.id === 'previous-page') {                                                                     // Если id элемента 'previous-page'
                Array.from(document.getElementsByClassName('numeric')).forEach(item => {                                // Собираем массив элементов с классом 'numeric'
                    if($('#'+item.id).hasClass('selected-page')) {                                                      // Если у элемента есть класс 'selected-page'
                        /******************************************************************/
                        if(debug) console.log(item);
                        if(debug) console.log(item.id);
                        if(debug) console.log(((item.id).split('-'))[1]);
                        /******************************************************************/
                        currentPage = ((item.id).split('-'))[1];                                                        // Считаем текущую страницу
                        previousPage = currentPage - 1;                                                                 // Считаем предыдущую страницу
                    }
                });
                let a = document.querySelectorAll('.numeric');                                                          // Находим первую кнопку из кнопок с номерами страниц
                a = a[0];
                if(currentPage > 1) {                                                                                   // Если это не первая страница
                    if($(a).hasClass('selected-page')) {                                                                // Если выбрана первая кнопка в ряду
                        createTabButtons(previousPage - 5, previousPage);                                               // Перелистываем на пять кнопок назад
                        /******************************************************************/
                        if(debug) console.log('Перелистывание списка страниц назад');
                        /******************************************************************/
                    }
                    /******************************************************************/
                    if(debug) console.log('Текущая кнопка имеет id: page-'+currentPage);
                    if(debug) console.log('Следующая кнопка имеет id: page-'+nextPage);
                    /******************************************************************/
                    $('.handbook-page-switch').removeClass('selected-page');                                            // Удаляем всем кнопкам класс 'selected-page'
                    $('#page-'+previousPage).addClass('selected-page');                                                 // Добавляем его нужной кнопке
                    buildContent(((previousPage * 25) - 25), (previousPage * 25));                                      // Вызов функции заполнения таблицы
                }
            }
        };
    });
}


