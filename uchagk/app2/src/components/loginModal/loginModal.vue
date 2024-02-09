<template>
  <div class="login-modal-background" @click.stop>
    <div class="login-modal">
      <div class="login-modal-header">
        <div class="center-block">
          <div class="center-block-background"></div>
          <div class="center-block-mask-layer white-mask"></div>
          <div class="center-block-mask-layer grey-mask"></div>
          <span class="center-block-title">Авторизация</span>
        </div>
        <button class="login-modal-header-close-btn" title="Закрыть окно авторизации"
                @click.stop="$emit('closeLoginModalWindow', 'closedStatus')">
          <span></span>
        </button>
      </div>
      <div class="login-modal-body">
        <div class="login-form">
          <div class="login-field">
            <input v-model="userLogin" placeholder="Введите логин" type="text">
          </div>
          <div class="password-field">
            <input ref="passwordInput" v-model="userPassword" placeholder="Введите пароль" type="password">
            <div id="changePasswordVisibility" class="glyphicon glyphicon-eye-close"
                 title="Показать пароль"
                 @mousedown.stop="changePasswordVisibility($event, 'text')"
                 @mouseup.stop="changePasswordVisibility($event, 'password')"></div>
          </div>
          <div class="active-directory-field">
            <label :class="{'checked': activeDirectoryFlag}" for="activeDirectoryCheckbox">
              <input id="activeDirectoryCheckbox" v-model="activeDirectoryFlag" type="checkbox">
              Авторизация по ActiveDirectory
            </label>
          </div>
          <!--                    <div class="errors-field">-->
          <!--                        {{ responseErrors }}-->
          <!--                    </div>-->
          <div class="log-in-button" @click.stop="ajaxLogIntoSystem()">
            <div class="log-in-button-mask-layer"></div>
            <span class="log-in-button-title">Войти</span>
          </div>
        </div>
      </div>
    </div>
  </div>

</template>

<script>
export default {
  name: "loginModal",
  data() {
    return {
      userPassword: '',
      userLogin: '',
      activeDirectoryFlag: false,
      // responseErrors: '',
    }
  },

  methods: {
    async ajaxLogIntoSystem() {
      if (!this.userPassword) {
        // this.responseErrors = 'Заполните поле "Пароль"';
        showNotify('Заполните поле "Пароль"');
        return;
      }
      if (!this.userLogin) {
        // this.responseErrors = 'Заполните поле "Логин"';
        showNotify('Заполните поле "Логин"');
        return;
      }
      if (this.userLogin && this.userPassword) {
        let config = {
          controller: 'UserAutorization',
          method: 'actionLogin',
          subscribe: '',
          date_time_request: new Date(),
          page_request: window.location.href,
          data: JSON.stringify({
            login: this.userLogin,
            password: this.userPassword,
            activeDirectoryFlag: this.activeDirectoryFlag
          })
        };
        let response = await this.sendAjaxRequestWithoutCheckingSession(config);
        console.log("ajaxLogIntoSystem", response);
        if (response.status) {
          localStorage.setItem("serialWorkerData", JSON.stringify(response.Items));                       // записываем в локальное хранилище
          this.$emit('closeLoginModalWindow', 'successStatus');
        } else {
          // this.responseErrors = response.errors;
          localStorage.removeItem("serialWorkerData");
        }
      }
    },

    sendAjaxRequestWithoutCheckingSession(config) {
      return $.ajax({                                                                                                 //тело запроса в нотации JQuery
        url: '/read-manager-amicum',                                                                                //путь до менеджера чтения
        type: 'post',                                                                                               //тип запроса POST
        data: config,                                                                                               //передаваемые на сервер данные
        dataType: 'json',                                                                                           //тип ответа json
        beforeSend: () => {                                                                                         //обработчик, который запускается перед отправкой запроса
          $('#preload').removeClass('hidden');                                                                    //отображаем прелоадер
        },
        error: (jqXHR, exception) => {                                                                              //обработчик ошибок, которые могут возникнуть в ходе передачи/выполнения запроса
          catchError(jqXHR, exception, "", config);                                                                           //вызов функции обработки ошибок
        },
        success: (response) => {                                                                                    //обработчик при успешном выполнении запроса
          $('#preload').addClass('hidden');                                                                       //скрываем прелоадер
          if (response.status !== 1) {                                                                                 //если статус равен 1
            console.error(`Ошибка! Метод ${config.method} не был выполнен или выполнен с ошибкой`,              //иначе отображаем соответствующее сообщение как ошибку и выводим массивы errors и warnings, которые приходят в ответе запроса
                response.errors, response.warnings);
            response.errors.forEach(error => showNotify(error));
          }
        }
      });
    },

    changePasswordVisibility(event, newType) {
      // console.log('loginModal.vue. changePasswordVisibility. ref.passwordInput ', this.$refs.passwordInput);
      if (this.$refs.passwordInput) {
        this.$refs.passwordInput.setAttribute('type', newType);
      }
      if (newType === 'text') {
        event.target.classList.remove('glyphicon-eye-close');
        event.target.classList.add('glyphicon-eye-open');
        event.target.setAttribute('title', 'Скрыть пароль');
      } else {
        event.target.classList.remove('glyphicon-eye-open');
        event.target.classList.add('glyphicon-eye-close');
        event.target.setAttribute('title', 'Показать пароль');
      }
    }
  }
}
</script>

<style lang="less" scoped>
.login-modal-background {
  position: absolute;
  top: 0;
  left: 0;
  z-index: 9999;
  background: rgba(0, 0, 0, 0.35);
  width: 100vw;
  height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  min-width: 1200px;
  min-height: 500px;

  .login-modal {
    width: 500px;
    height: 300px;
    border: 4px solid #6A7180;
    background: #fff;

    &-header {
      width: 100%;
      height: 40px;
      position: relative;


      .center-block {
        width: 80%;
        height: 100%;
        margin: 0 auto;
        filter: drop-shadow(3px 6px 2px rgba(0, 0, 0, 0.35));
        position: relative;
        overflow: hidden;

        &-background {
          background: #4A5160;
          width: 96%;
          height: 34px;
          z-index: 2;
          position: absolute;
          top: 0;
          left: 2%;
          clip-path: polygon(0 0, 100% 0, calc(100% - calc(0.6 * 34px)) 100%, calc(0.6 * 34px) 100%);
        }

        &-title {
          margin: auto;
          color: #fff;
          font-size: 20px;
          position: relative;
          z-index: 5;
          top: 3px;
          letter-spacing: 2px;
        }

        &-mask-layer {
          position: absolute;

          &.white-mask {
            background: #fff;
            left: 0;
            top: -2px;
            width: 100%;
            height: 40px;
            z-index: 1;
            clip-path: polygon(0 0, 100% 0, calc(100% - calc(0.6 * 40px)) 100%, calc(0.6 * 40px) 100%);
          }

          &.grey-mask {
            background: #6A7180;
            width: 96%;
            height: 14px;
            top: 0;
            left: 2%;
            z-index: 3;
            clip-path: polygon(0 0, 100% 0, calc(100% - calc(0.6 * 14px)) 100%, calc(0.6 * 14px) 100%);
          }
        }
      }

      &-close-btn {
        outline: none;
        background: none;
        height: 16px;
        width: 16px;
        border: none;
        z-index: 10;
        position: absolute;
        top: 0;
        right: 4px;

        &:hover {
          cursor: pointer;

          span::before, span::after {
            background-color: #4A5160;
          }
        }

        span {
          display: inline-block;
          position: relative;
          width: 100%;
          height: 100%;

          &::before, &::after {
            content: '';
            position: absolute;
            top: 10px;
            left: 0;
            display: block;
            width: 18px;
            height: 2px;
            background-color: #6A7180;
          }

          &::before {
            transform: rotate(-45deg);
          }

          &::after {
            transform: rotate(45deg);
          }
        }
      }
    }

    &-body {
      width: 100%;
      height: calc(100% - 40px);
      display: flex;
      justify-content: center;
      align-items: center;

      .login-form {
        width: 60%;
        height: 70%;

        & > div {
          width: 100%;
          height: 30px;
          color: #666;

          input {
            background: none;
            border: none;
            outline: none;
            text-align: center;
            width: 100%;
            height: 100%;

            &::placeholder {
              color: #666;
            }
          }

          &.login-field, &.password-field {
            background: #e6e6e6;
          }

          &.password-field {
            margin-top: 30px;
            margin-bottom: 15px;
            display: flex;
            flex-flow: row nowrap;
            align-items: center;
            position: relative;

            .glyphicon {
              cursor: pointer;
              font-size: 20px;
              position: absolute;
              top: 5px;
              right: 5px;

              &::before {
                color: #9b9d96;
              }

              &:hover::before {
                color: #7c7c7c;
              }
            }
          }

          &.active-directory-field {
            display: flex;
            justify-content: flex-start;
            align-items: center;

            label {
              position: relative;
              margin: 0;
              height: 100%;
              width: 100%;
              display: flex;
              justify-content: flex-start;
              align-items: center;
              -webkit-user-select: none;
              -moz-user-select: none;
              -ms-user-select: none;
              user-select: none;

              &:hover {
                cursor: pointer;
              }

              #activeDirectoryCheckbox {
                position: relative;
                top: 0;
                left: 0;
                visibility: hidden;
                opacity: 0;
                width: 25px;
                height: 25px;
                margin: 0 10px 0 0;
              }

              &::before {
                position: absolute;
                width: 25px;
                height: 25px;
                display: block;
                content: '';
                background-color: #fff;
                background-image: none;
                border: 1px solid #ccc;
              }

              &.checked {
                &::before {
                  background-image: url('./assets/checkedFlag.png');
                  background-size: cover;
                }
              }
            }
          }
        }

        .log-in-button {
          background: #4D897C;
          position: relative;
          display: flex;
          justify-content: center;
          align-items: center;
          margin-top: 15px;
          cursor: pointer;

          &-mask-layer {
            background: #5D998C;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 50%;
          }

          &-title {
            color: #fff;
            text-transform: lowercase;
            font-size: 20px;
            letter-spacing: 2px;
            position: relative;
          }
        }
      }
    }
  }
}
</style>
