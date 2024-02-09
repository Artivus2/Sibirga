import axios from 'axios';



const sendAjax = axios.create({
  baseURL: 'http://10.8.12.118:8080',
  headers: {
    'Access-Control-Allow-Origin': '*',
    'Content-Type': 'application/json',
},
withCredentials: true
  
   // замените на ваш базовый URL
 
});
sendAjax.prototype.$http = axios;
//api.prototype.$http = Axios;
 
export default sendAjax;
