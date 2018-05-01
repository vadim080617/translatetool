import {API_URL} from '../constants';
import axios from 'axios';


export default {
    async sendExcel(data){
        const json = await axios.post(API_URL + '/send/excel', data);
        window.location = `/download/translate/?path_to_file=${json.data.path_to_file}`;
        return json;
    },
    async sendTranslate(data) {
        const json = await axios.post(API_URL + '/send/translate',data);
        window.location = `/download/translate/?path_to_file=${json.data.path_to_file}`;
        return json;
    }
};