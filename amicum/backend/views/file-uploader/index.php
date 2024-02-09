<?php
/* @var $this yii\web\View */

use backend\assets\AppAsset;
use yii\web\View;

$getSourceData = 'let fileList = ' . json_encode($fileList) . ';';
$this->registerJs($getSourceData, View::POS_HEAD, 'file_uploader-js');
$this->title = "Документы";
$this->registerCssFile('/admin/css/file_uploader.css', ['depends' => [AppAsset::className()]]);
?>
<!--Форма загрузки файла-->
<div class="grid_container">
    <section class="grid_forma_left">
        <!--        <h1>Файлообменная сеть AMICUM</h1>-->
        <form class="file-uploader">
            <label for="uploadInput" class="file-uploader__message-area">
                <i class="glyphicon glyphicon-folder-open"></i><span>Выберите файл для загрузки</span>
            </label>
            <div class="file-chooser">
                <!--                    <label for="uploadInput"><i class="glyphicon glyphicon-folder-open"></i>Выберите файл...</label>-->
                <input class="file-chooser__input" type="file" id="uploadInput" accept="application/zip,
                application/x-zip-compressed, application/x-rar-compressed, .7z, .sql, application/x-7z-compressed,
                text/csv, application/vnd.oasis.opendocument.text,
                application/vnd.oasis.opendocument.spreadsheet,
                application/vnd.ms-excel, application/msword, image/jpeg, image/png, application/pdf, text/plain">
            </div>
        </form>
        <button class="file-uploader__submit-button" id="uploadButton">Загрузить</button>
        <div class="progress hidden">
            <div class="progress-bar progress-bar-striped active" id="uploadProgressBar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">
                0%
            </div>
        </div>
    </section>
    <!--Список файлов-->
    <section class="table_and_generation">
        <div class="files-table">
            <div class="table-header">
                <div class="header-title ordered-number"><span>№ п/п</span></div>
                <div class="header-title file-name"><span>Название файла</span></div>
                <div class="header-title file-size"><span>Размер файла</span></div>
                <div class="header-title upload-date"><span>Дата загрузки</span></div>
                <div class="header-title download-header"><span>Скачать файл</span></div>
                <div class="header-title delete-file"><span>Удалить файл</span></div>
            </div>
            <div class="table-body"></div>
        </div>
        <!-- НИЖНЕЕ ПОЛЕ ПРОКРУТКИ -->
        <div class="handbook-content__footer">
            <div class="handbook-content__footer__pagination"></div>
        </div>
    </section>
    <!--    Модальное окно удаления параметра через кнопку рядом с типом параметра-->
    <div class="modal fade" tabindex="-1" role="dialog" id="deleteParameter">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title"><i class="icon-delete-btn"></i>
                        Удаление файла
                    </h4>
                </div>
                <div class="modal-body">
                    <div class="row padding-style">
                        <p>
                            Вы действительно хотите удалить файл : <span class="p-title" id="modal-body_span"></span> ?
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="button-content">
                        <button type="button" class="btn btn-primary delete-parameter-button"
                                data-dismiss="modal" id="button-content_delete-yes">Да</button>
                        <button type="button" class="btn btn-default" data-dismiss="modal" id="button-content_delete-no">Нет</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--ToDo: переименовать классы и идентификаторы-->
<?php
$this->registerJsFile('/js/Blob.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/FileSaver.min.js', ['depends' => [AppAsset::className()],'position' => View::POS_END]);
$this->registerJsFile('/js/bootstrap-notify.min.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
$this->registerJsFile('/admin/js/file_uploader.js', ['depends' => [AppAsset::className()], 'position' => View::POS_END]);
?>