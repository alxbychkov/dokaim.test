/**
 * Пример извлечения информации о сертификате
 * */
;(function () {
  'use strict';

  var $certificate = document.getElementById('certificate'),
    $certificateInfo = document.getElementById('certificateInfo'),
    $certificatesError = document.getElementById('certificateInfoError');

  function handleError(error) {
    $certificatesError.textContent = '\n' + error.message;
  }
})();
