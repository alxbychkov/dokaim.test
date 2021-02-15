/**
 * Пример получения списка сертификатов
 * */
;(function () {
  'use strict';

  var $certificate = document.getElementById('certificate'),
    $createSignature = document.getElementById('createSignature'),
    $certificateError = document.getElementById('certificateListError');

  $certificate.addEventListener('change', function handleCertSelection() {
    var thumbprint = $certificate.value;

    $createSignature.disabled = !thumbprint;
  });

  window.cryptoPro.getUserCertificates().then(function (certificateList) {
    certificateList.forEach(function (certificate) {
      var $certOption = document.createElement('option');

      $certOption.textContent = certificate.name + ' (действителен до: ' + certificate.validTo + ')';
      $certOption.value = certificate.thumbprint;

      $certificate.appendChild($certOption);
    });
  }, function (error) {
    $certificateError.textContent = error.message;
  });
})();
