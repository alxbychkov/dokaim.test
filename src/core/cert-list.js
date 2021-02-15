/**
 * Пример получения списка сертификатов
 * */
const $certificate = document.getElementById('certificate')
const $organization = document.querySelector('select[data-type="organization"]')
const $createSignature = document.getElementById('createSignature')
const $certificateError = document.getElementById('certificateListError')

$certificate.addEventListener('change', function handleCertSelection() {
    const thumbprint = $certificate.value
    $createSignature.disabled = !thumbprint
    $organization.disabled = !thumbprint
});

window.cryptoPro.getUserCertificates().then(function (certificateList) {
    certificateList.forEach(function (certificate) {
        const $certOption = document.createElement('option')
        $certOption.textContent = certificate.name + ` 
        (действителен до:  ${certificate.validTo})`
        $certOption.value = certificate.thumbprint;
        $certificate.appendChild($certOption);
    })
    }, function (error) {
        $certificateError.textContent = error.message;
    }
)
