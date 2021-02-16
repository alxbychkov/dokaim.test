/* eslint-disable new-cap */
/* eslint-disable no-undef */
import './scss/index.scss'
import '../node_modules/chosen-js/chosen.css'
import './core/cert-list'
import './core/show-cert'
import './core/get-env-info'
import './core/create-sign'
import {parseXML} from './core/xmlParser'
import $ from 'jquery'
window.jQuery = $
window.$ = $
import 'chosen-js'



// const parser = new DOMParser()
// const xmlDoc = parser.parseFromString(xml, 'application/xml')
// const xmlSor = new XMLSerializer()
// console.log(xmlSor.serializeToString(xmlDoc))


// eslint-disable-next-line no-unused-vars
function loadFiles(input) {
    const documents = document.querySelector('select[name=documents]')
    const files = input.files
    if (documents && files) {
        documents.innerHTML = '';
        [...files].forEach((file, index) => {
            const fileName = document.createElement('option')
            fileName.value = index
            fileName.innerHTML = file.name
            documents.append(fileName)
        })
        if ([...files].length == 1) {
            readFile(input, 0)
        } else {
            document.querySelector('.preview__window').innerHTML = ''
        }
    }
}

function readFile(input, i) {
    const file = input.files[i]
    const reader = new FileReader()
    const exKEy = document.querySelector('input[name=externalkey]')
    const docNo = document.querySelector('input[name=docnumber]')
    const docDate = document.querySelector('input[name=docdate]')

    reader.readAsText(file)
    reader.onload = function() {
        document.querySelector('.preview__window').innerHTML = reader.result
        if (parseXML(reader.result)) {
            exKEy.value = parseXML(reader.result)['externalKey']
            docNo.value = parseXML(reader.result)['docNumber']
            docDate.value = parseXML(reader.result)['docDate']
        }
    }
    reader.onerror = function() {
        console.log(reader.error)
    }
}

const fileInput = document.querySelector('input[data-type="file"]')
const documents = document.querySelector('select[name=documents]')

if (documents && fileInput) {
    fileInput.addEventListener('change', e=>{
        loadFiles(e.target)
    })
    documents.addEventListener('change', e => {
        const selectedIndex = e.target.selectedIndex
        readFile(fileInput, selectedIndex)
    })
}

const clear = document.querySelector('input[data-type="clear"]')
clear.addEventListener('click', e=> {
    e.target.closest('form').reset()
    document.querySelector('.preview__window').innerHTML = ''
    organization.disabled = true
    roomSelect.innerHTML = ''
    roomSelect.disabled = true
    roomId.disabled = true
    document.querySelector('input[type="submit"]').disabled = true
    documents.innerHTML = ''
})

// выбираем организацию и получаем данные
const organization = document.querySelector('select[data-type="organization"]')
const roomSelect = document.querySelector('select[data-type="room"]')
const roomId = document.querySelector('input[data-type="room_id"]')

if (organization) {
    organization.addEventListener('change', e => {
        const room = e.target.value
        getRoomData(room, text => {
            const data = JSON.parse(text)
            insertRoomData(roomSelect, data)
        })
    })
}

// получаем данные из файлов организаций
function getRoomData(room, callback) {
    const fileName = 'assets/files/' + room + '.json'
    const jsonFile = new XMLHttpRequest()
    jsonFile.overrideMimeType('application/json')
    jsonFile.open('GET', fileName, true)
    jsonFile.onreadystatechange = function() {
        if (jsonFile.readyState === 4 && jsonFile.status == '200') {
            callback(jsonFile.responseText)
        }
    }
    jsonFile.send(null)
}

// подставляем данные
function insertRoomData(room, data) {
    if (room) {
        room.innerHTML = ''
        if (data.length !== 0) {
            let option = document.createElement('option')
            option.selected = true
            option.disabled = true
            option.innerText = ''
            room.appendChild(option)
            for (let i=0; i<data.length; i++) {
                option = document.createElement('option')
                option.value = data[i].value
                option.innerText = data[i].label
                room.appendChild(option)
            }
            room.disabled = false
            roomId.value = ''
            roomId.disabled = true
            // $(room).chosen({disable_search_threshold: 10}).change()
            // $("#form_field").chosen()
        } else {
            room.disabled = true
            roomId.value = ''
            roomId.disabled = true
        }
    }
}

if (roomSelect && roomId) {
    
    roomSelect.addEventListener('change', e => {
        const valueId = e.target.value
        roomId.value = valueId
        roomId.disabled = false
    })
}
