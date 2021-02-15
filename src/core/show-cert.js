/**
 * Пример извлечения информации о сертификате
 * */
// eslint-disable-next-line no-unused-vars
const $certificate = document.getElementById('certificate')
// eslint-disable-next-line no-unused-vars
const $certificateInfo = document.getElementById('certificateInfo')
const $certificatesError = document.getElementById('certificateInfoError')

// eslint-disable-next-line no-unused-vars
function handleError(error) {
    $certificatesError.textContent = '\n' + error.message;
}
