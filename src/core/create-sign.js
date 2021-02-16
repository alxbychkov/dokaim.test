/* eslint-disable max-len */
/**
 * Пример создания подписи данных
 * */
const $createSignature = document.forms.createSignature
const $certificate = document.getElementById('certificate')
// let $message = ''
const $messageFile = document.querySelector('input[data-type="file"]')
// let $messageFileError = ''
// let $hash = ''
// const $hashError = document.getElementById('hashError')
// const $signature = document.getElementById('signature')
// const $signatureError = document.getElementById('signatureError')
const MAX_FILE_SIZE = 25000000

function readFile(messageFile) {
    return new Promise(function (resolve, reject) {
    const fileReader = new FileReader()
    fileReader.readAsText(messageFile)
    fileReader.onload = function () {
        // document.querySelector('.preview__window').innerHTML = this.result
        resolve(this.result)
    }

    if (messageFile.size > MAX_FILE_SIZE) {
        // eslint-disable-next-line max-len
        // eslint-disable-next-line prefer-promise-reject-errors
        reject('Файл для подписи не должен превышать ' + MAX_FILE_SIZE / 1000000 + 'МБ')
        return
    }

    // fileReader.readAsArrayBuffer(messageFile)
    })
}

function createSignature(message, hash) {
    const thumbprint = $certificate.value
    let detachedSignature = 1 // совмещенная подпись
    let signaturePromise

    detachedSignature = Boolean(Number(detachedSignature))

    console.log('hash: ', hash)
    console.log('Создается...')

    if (detachedSignature) {
        signaturePromise = window.cryptoPro.createDetachedSignature(thumbprint, hash)
        console.log(hash)
    } else {
        signaturePromise = window.cryptoPro.createAttachedSignature(thumbprint, message)
        console.log(message)
    }

    signaturePromise.then(function (signature) {
        // console.log('Signature:')
        // console.log(signature)
        // console.log($messageFile.files[0])
        sendData($createSignature, signature, hash, $messageFile.files[0])
    }, function (error) {
        console.log('Не создана')
        console.log('Ошибка: ', error.message)
    })
}

$createSignature.addEventListener('submit', function (event) {
    let messageFile = $messageFile && $messageFile.files.length && $messageFile.files[0]
    if ($messageFile.files.length > 1) {
        const i = document.querySelector('select[name=documents]').selectedIndex
        messageFile = $messageFile.files[i]
    }
    let messagePromise = Promise.resolve('')

    if (messageFile) {
        messagePromise = readFile(messageFile)
    }

    event.preventDefault()

    messagePromise.then(function (message) {
    console.log('Hash Вычисляется...')

    window.cryptoPro.createHash(message).then(createSignature.bind(null, message), function (hashError) {
        console.log('Hash Не вычислен')
        console.log('Hash error: ', hashError.message)
    });
    }, function (fileError) {
        console.log('Error: ', fileError)
    })
})

function sendData(form, signature, hash, file) {
    const xhr = new XMLHttpRequest()
    const fd = new FormData(form)
    fd.append('hash', hash)
    fd.append('signature', signature)
    fd.append('file', file)
    xhr.open( 'POST', '/php/index.php', true )
    xhr.addEventListener('readystatechange', () => {
    if (xhr.readyState === 4 && xhr.status === 200) {
        const data = JSON.parse(xhr.responseText) // получаем ответ
        console.log(data)
        if (data.status) {
            // document.querySelector('input[data-type="clear"]').click()
            alert('File send')
        } else {
            alert('File send ERROR')
        }
    }
    })
    xhr.send( fd )
}
