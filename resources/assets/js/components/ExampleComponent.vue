<template>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card card-default">
                    <div class="card-header main-app-header">Translate tool</div>

                    <div class="card-body row">
                            <div class="col-6 take-excel-field">
                                <div class="loadFieldTitle row justify-content-center">Excel -> js||php</div>
                                <div>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="excelf" @change="getTextFromExcel($event)">
                                        <label class="custom-file-label" for="excelf">Choose file</label>
                                    </div>
                                    <div>
                                        <div>Type of output</div>
                                        <label><input type="radio" v-model="typeOfOutput" name="typeOfOutput" value="js"> JS</label>
                                        <label><input type="radio" v-model="typeOfOutput" name="typeOfOutput" value="php"> PHP</label>
                                    </div>
                                    <div class="div-with-buttons">
                                        <button class="btn" type="button" @click="clearInput">Clear</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                            <div class="loadFieldTitle row justify-content-center">js||php -> Excel</div>
                            <template v-for="(value,index) in fileInputs">
                                <div class="row col-12 input-translate-block m-0">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="transf" @change="getTextFromFile($event,index)">
                                        <label class="custom-file-label" for="transf">Choose file</label>
                                    </div>
                                    <label class = "col-12 p-0" for="`langfield${index}`">Enter language:
                                    <input class="form-control col-12" type="text" :id="`langfield${index}`" @change="setLang($event,index)" :disabled="value === -1">
                                    </label>
                                </div>
                            </template>
                            <div class="div-with-buttons">
                                <button class="btn" type="button" @click="addField">Add field</button>
                                <button class="btn btn-primary" type="button" @click="sendTranslates">Send</button>
                                <button type="button" v-if="fileInputs.length !==1" class="btn" @click="removeField">Remove last field</button>
                            </div>
                            </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import api from '../api/api.js';
    import XLSX from 'xlsx';
    import swal from 'sweetalert2';
    export default {
        data() {
            return {
                posts: [],
                exel:{},
                typeOfOutput:"",
                mainTranslateObj:{},
                fileInputs: [],
            }
        },
        created() {
            this.fileInputs[0] = -1;
            this.typeOfOutput = "js";
            this.mainTranslateObj["files"] = {};
        },
        methods: {
            async getTextFromExcel(e){               //Transform file into excel format
                if(e.target.files[0].name.split('.').pop() !== "xlsx")
                {
                    e.target.value ="";
                    swal({
                        type: 'error',
                        title: 'Oops...',
                        text: 'Wrong file format, require ".xlsx"',
                    });
                    return;
                }

                e.target.nextElementSibling.textContent = e.target.files[0].name;

                let files = e.target.files, f = files[0];
                let reader = new FileReader();
                reader.readAsBinaryString(e.target.files[0]);
                reader.addEventListener('load', (e)=>{
                    let data = e.target.result;
                    let wb = XLSX.read(data, {type: 'binary'});
                    this.handleExel(wb);
                });

            },
            handleExel(text){                           //Parsing excel worbook into object
                let table = {};
                let excelObj = text;
                let excelSheets = excelObj["Sheets"];
                let sheetObjects = {};
                for(let sheet in excelSheets){
                    sheetObjects[sheet] = XLSX.utils.sheet_to_json(excelSheets[sheet]);
                }

                //console.log(sheetObjects);

                for(let sheet in sheetObjects){
                    table[sheet] = {};
                    let keys = {};
                    for(let i=0;i<sheetObjects[sheet].length;i++){
                        for(let cell in sheetObjects[sheet][i]){
                            if(cell === "Key"){
                                //console.log(sheetObjects[sheet][i][cell]);
                                keys[i] = sheetObjects[sheet][i][cell];
                            }else{
                                break;
                            }
                        }

                        for(let cell in sheetObjects[sheet][i]){
                            if(cell !== "Key"){
                                if (i === 0) {
                                    table[sheet][cell] = {};
                                }
                                let val = sheetObjects[sheet][i][cell];
                                val = val.replace(/\\/g,"\\\\");
                                val = val.replace(/'/g,"\\'");
                                table[sheet][cell][keys[i]] = val;
                            }
                        }
                    }
                }

                //console.log(table);

                const data = {
                    type : this.typeOfOutput,
                    sheets: table,
                };

                api.sendExcel(data);
                //console.log(data);


            },
            getTextFromFile(e,index){                                       //get text content from file
                if(e.target.files[0].name.split('.').pop() !== "js" && e.target.files[0].name.split('.').pop() !== "php")
                {
                    e.target.value ="";
                    swal({
                        type: 'error',
                        title: 'Oops...',
                        text: 'Wrong file format, require ".php" or ".js")',
                    });
                    return;
                }

                e.target.nextElementSibling.textContent = e.target.files[0].name;

                let text,
                    fileReader = new FileReader(),
                    parts = e.target.files[0].name.split('.'),
                    ext = parts.pop(),
                    filename = parts.pop();

                fileReader.readAsText(e.target.files[0],'UTF-8');
                fileReader.addEventListener('load', ()=>{
                   text = fileReader.result;
                   this.handlerTranslate(text,ext,filename,index);
                });
            },
            async handlerTranslate(text,ext,filename,index){                // sending key/value object
                //console.log(ext);
                //console.log(filename);

                let rows = await this.transformToRows(text,ext);

                let obj = await this.makeKeyValueObject(rows,ext);


                this.mainTranslateObj["files"][index] = {};
                this.mainTranslateObj["files"][index]["filename"] = filename;
                this.mainTranslateObj["files"][index]["lang"] = obj;
                if (this.fileInputs[index] === -1) {
                    this.$set(this.fileInputs, index, 0);
                }else if(this.fileInputs[index] === 1){
                    console.log(document.getElementById("langfield" + index));
                    this.mainTranslateObj["files"][index]["language"] = document.getElementById("langfield" + index).value;
                }
            },
            transformToRows(text,ext){                      //transform text from file into rows
                if(ext === "php"){

                    let str = text,
                        excessPhpSyntax = /(^[^\[]*)|([/][*][^*]*[*][/])|(;\s*$)/g,
                        globalSquareBrackets= /(^[[])|(\]$)/g,
                        keyValDelimiter = /\s*=>\s*/g;

                    str = str.replace(excessPhpSyntax,"");
                    str = str.replace(globalSquareBrackets,"");
                    str = str.replace(keyValDelimiter,">");
                    str = str.trim();

                    let rows = str.split("\n");

                    for(let i=0;i<rows.length;i++){
                        rows[i] = rows[i].trim();
                        if(rows[i] === ""){
                            rows.splice(i,1);
                            i--;
                        }
                    }
                    return rows;
                }else{
                    let str = text,
                        excessJsSyntax = /(^[^{]*{[^{]*{)|([/][*][^*]*[*][/])|(}[^}]*}[^}]*$)/g,
                        keyValDelimiter = /\s*:\s*/g;

                    str = str.replace(excessJsSyntax,"");
                    str = str.replace(keyValDelimiter,">");
                    str = str.trim();

                    let rows = str.split("\n");

                    for(let i=0;i<rows.length;i++){
                        rows[i] = rows[i].trim();
                        if(rows[i] === ""){
                            rows.splice(i,1);
                            i--;
                        }
                    }

                    return rows;
                }
            },
            makeKeyValueObject(rows,ext) {      //parse rows & create key/val object
                if (ext === "php") {
                    let obj = {},
                        pair,
                        subkeys = false,
                        prefix = "",
                        deep = 0;

                    for (let i = 0; i < rows.length; i++) {
                        if (rows[i][rows[i].length - 1] !== "[" && subkeys === false) {
                            pair = rows[i].split('>');
                            for (let j = 0; j < pair.length; j++) {
                                pair[j] = pair[j].replace(/(^')|(',$)|('$)/g, "");
                                pair[j] = pair[j].replace(/(^")|(",$)|("$)/g, "");
                            }
                            pair[1] = pair[1].replace(/\\'/g,"'");
                            pair[1] = pair[1].replace(/\\\\/g,"\\");
                            obj[pair[0]] = pair[1];
                        } else {
                            subkeys = true;
                            if (rows[i][rows[i].length - 1] === "[") {
                                deep++;
                                if (prefix === "") {
                                    prefix += rows[i].split('>')[0].replace(/(^')|('$)/g, "");
                                } else {
                                    prefix += "->>";
                                    prefix += rows[i].split('>')[0].replace(/(^')|('$)/g, "");
                                }
                            } else if (rows[i][rows[i].length - 2] === "]") {
                                deep--;
                                if (deep === 0) {
                                    subkeys = false;
                                    prefix = "";
                                }
                            } else {
                                pair = rows[i].split('>');
                                for (let j = 0; j < pair.length; j++) {
                                    pair[j] = pair[j].replace(/(^')|(',$)|('$)/g, "");
                                }
                                pair[1] = pair[1].replace(/\\'/g,"'");
                                pair[1] = pair[1].replace(/\\\\/g,"\\");
                                obj[prefix + '->>' + pair[0]] = pair[1];
                            }
                        }

                    }

                    return obj;
                } else {
                    let obj = {},
                        pair;

                    for (let i = 0; i < rows.length; i++) {
                        pair = rows[i].split('>');
                        pair[1] = pair[1].replace(/(^')|(',$)|('$)/g, "");
                        pair[1] = pair[1].replace(/\\'/g,"'");
                        pair[1] = pair[1].replace(/\\\\/g,"\\");
                        obj[pair[0]] = pair[1];
                    }

                    return obj;
                }
            },
            addField(){
                this.fileInputs.push(-1);
            },
            sendTranslates(){
                //console.log(this.fileInputs);
                for(let i = 0; i < this.fileInputs.length ; i++){
                    if(this.fileInputs[i] === -1){
                        swal({
                            type: 'error',
                            title: 'Oops...',
                            text: 'Не все файлы добавлены в поля, уберите лишнее поле или добавьте недостающие файлы.',
                        });
                        return;
                    }
                }

                for(let i = 0; i < this.fileInputs.length ; i++){
                  if(this.fileInputs[i] === 0){
                      swal({
                      type: 'error',
                       title: 'Oops...',
                       text: 'Не все поля ввода языка заполнены.',
                       });
                      return;
                  }
                }
                api.sendTranslate(this.mainTranslateObj);
            },
            setLang(e,index){
                this.mainTranslateObj["files"][index]["language"] = e.target.value;
                this.fileInputs[index] = 1;
            },
            removeField(){
                this.mainTranslateObj["files"][this.fileInputs.length -1 ] = null;
                delete this.mainTranslateObj["files"][this.fileInputs.length -1] ;
                this.fileInputs.pop();
            },
            clearInput(){
                const excelInput = document.getElementById("excelf");
                excelInput.value = null;
                excelInput.nextElementSibling.textContent = "Choose file";
            },
        },
    }
</script>
