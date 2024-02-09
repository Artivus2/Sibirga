<template>
  <div>

    <div class="excel-import">
      <div class="excel-import-modal">
        <div class="excel-import-modal-header">
          <div class="excel-import-modal-header-title">Загрузить из Excel</div>
          <div class="excel-import-modal-header-close" @click.stop="closeExcelImport">
            <img alt="img"
                 src="@/modules/order-system/components/shift-schedule/assets/newGraphicAssets/work-hours-cross.svg">
          </div>
        </div>
        <div class="excel-import-modal-content">
          <template v-if="excelFile.filename === ''">
            <label for="addFile">
              <div id="dropZone" class="excel-import-modal-content-file">
                <div class="excel-import-modal-content-file-icon">
                  <img
                      alt="img"
                      src="@/modules/order-system/components/shift-schedule/assets/newGraphicAssets/grey-upload-icon.svg">
                </div>
                <span class="excel-import-modal-content-file-text">
              Перетащите файл в формате Excel или нажмите, чтобы загрузить
            </span>
              </div>
            </label>
            <input id="addFile" :sort="false" accept="application/vnd.oasis.opendocument.spreadsheet,
                            application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,
                            application/vnd.ms-excel"
                   class="zero-opacity"
                   title=""
                   type="file"
                   @change="uploadFile($event.target)"
                   @dragover.prevent="zoneHover"
                   @dragleave.prevent="zoneOut">
          </template>

          <template v-else>
            <div class="excel-import-modal-content-import">
              <div class="excel-import-modal-content-import-filename">{{ excelFile.filename }}</div>
              <div class="excel-import-modal-content-import-delete" @click.stop="deleteFile()">
                <img alt="img"
                     src="@/modules/order-system/components/shift-schedule/assets/newGraphicAssets/grey-cross.svg">
              </div>
            </div>
          </template>

          <template v-if="excelFile.error_flag = true">
            <span class="excel-import-modal-content-import-error">{{ excelFile.error_message }}</span>
          </template>

          <div class="excel-import-modal-content-text">
            <span>
              Загрузите документ в формате Excel, составленный только на основе файла-шаблона. В шаблоне
              не должны меняться наименования колонок, их расположение, формат данных в ячейках.
            </span>
            <p class="excel-import-modal-content-text-template"
               @click.stop="openExcelDownload">Скачать шаблон</p>
          </div>
        </div>
      </div>
      <div class="excel-import-buttons">
        <div class="excel-import-buttons-save" @click.stop="importFile()">
          <img alt="img" src="@/modules/order-system/components/shift-schedule/assets/newGraphicAssets/save_icon.svg">
          <span>Сохранить</span>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
export default {

  data() {
    return {
      excelFile: {
        filename: "",
        range: "",
        sheet: "",
        error_flag: false,
        error_message: "",
      },
    }
  },

  props: {
    templateUrl: {type: String, default: "", required: false},
  },

  computed: {},

  methods: {
    /**
     * Метод закрытия модалки импорта из Excel
     */
    closeExcelImport() {
      this.$emit('closeModal');
    },

    /**
     * Метод удаления файла для импорта из Excel
     */
    deleteFile() {
      this.excelFile.filename = '';
      this.excelFile.range = '';
      this.excelFile.sheet = '';
      this.excelFile.error_flag = false;
      this.excelFile.error_message = '';
    },

    /**
     * Метод импорта файла из Excel
     */
    importFile() {
      this.excel_import = false;
      if (this.excelFile.sheet === '') {
        showNotify("Файл не выбран");
      } else {
        this.$emit('importFile', {
          excelSheet: this.excelFile.sheet,
          range: this.excelFile.range
        })
      }

      this.excelFile.filename = '';
      this.excelFile.range = '';
      this.excelFile.sheet = '';
      this.excelFile.error_flag = false;
      this.excelFile.error_message = '';
    },

    /**
     * Метод перехода на страницу экспорта в Excel
     */
    openExcelDownload() {
      window.open(this.templateUrl, '_blank');
    },


    /**
     * Загрузка файла из Excel
     */
    uploadFile(inputFileNode) {
      if (inputFileNode.files.length) {
        let file = inputFileNode.files[0];
        this.excelFile.filename = file.name;
        let reader = new FileReader();
        reader.onload = (event) => {
          let data = event.target.result;
          let sheet = XLSX.read(data, {
            type: 'binary',
          });
          sheet.SheetNames.forEach(name => {
            this.excelFile.sheet = sheet.Sheets[name];
            this.excelFile.range = XLSX.utils.decode_range(this.excelFile.sheet["!ref"]);
          });
        }
        reader.readAsBinaryString(file);
        this.excelFile.sheet = "";
      }
    },


    /**
     * Метод наведения файла в зону дропа
     */
    zoneOut() {
      let dragZoneClasses = document.getElementById('dropZone').classList;
      dragZoneClasses.remove('drag-zone-hover');
    },

    /**
     * Метод наведения файла в зону дропа
     */
    zoneHover() {
      let dragZoneClasses = document.getElementById('dropZone').classList;
      dragZoneClasses.add('drag-zone-hover');
    },
  }
}


</script>

<style lang="scss" scoped>
.zero-opacity {
  position: absolute;
  width: 462px;
  height: 83px;
  top: 48px;
  right: 40px;
  opacity: 0;
  cursor: pointer;
}

.excel-import {
  position: absolute;
  top: 35%;
  left: 35%;
  width: 542px;
  height: 226px;
  z-index: 2;
  cursor: default;

  &-modal {
    box-sizing: border-box;
    width: 100%;
    //height: 196px;
    border: 2px solid #6A7080;

    &-header {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      height: 30px;
      background: #6A7080;

      &-title {
        font-size: 16px;
        color: white;
      }

      &-close {
        position: absolute;
        right: 10px;
        bottom: 6px;
        cursor: pointer;
      }
    }

    &-content {
      display: flex;
      flex-direction: column;
      align-items: center;
      width: 100%;
      background: white;

      &-import {
        display: flex;
        justify-content: flex-start;
        position: relative;
        width: 86%;
        margin-top: 30px;

        &-filename {
          color: #56698F;
        }

        &-delete {
          position: absolute;
          right: 10px;
          top: -2px;
          cursor: pointer;
        }

        &-error {
          font-size: 12px;
          color: #B55A6E;
          margin-top: 5px;
          width: 86%;
          text-align: left;
        }
      }

      label {
        margin-bottom: unset;
        font-weight: normal;
        cursor: pointer;
      }

      &-file {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        width: 462px;
        height: 83px;
        background: #F9F9FA;
        border: 2px dashed #CCCCCC;
        margin-top: 16px;
        box-sizing: border-box;

        &-icon {
        }

        &-text {
          color: #999999;
          margin-top: 9px;
        }
      }

      &-text {
        width: 462px;
        text-align: left;
        font-size: 10px;
        color: #999999;
        margin-top: 16px;

        &-template {
          color: #6A7180;
          font-size: 14px;
          width: 108px;

          &:hover {
            text-decoration-line: underline;
            cursor: pointer;
          }
        }
      }
    }
  }

  &-buttons {
    position: relative;
    height: 30px;
    overflow: hidden;

    &-save {
      display: flex;
      align-items: center;
      justify-content: center;
      position: absolute;
      background: #6A7080;
      width: 170px;
      height: 30px;
      right: -15px;
      transform: skew(45deg);
      cursor: pointer;

      img {
        transform: skew(-45deg);
        margin-left: -12px;
      }

      span {
        color: white;
        transform: skew(-45deg);
        margin-left: 10px;
      }
    }
  }
}
</style>