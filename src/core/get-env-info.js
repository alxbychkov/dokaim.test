/**
 * Пример получения сведений о системе
*/
window.cryptoPro.getSystemInfo().then(function (systemInfo) {
    window.cryptoPro.isValidSystemSetup().then(function (isValidSystemSetup) {
    systemInfo.isValidSystemSetup = isValidSystemSetup
    const $systemInfo = JSON.stringify(systemInfo, null, '  ')
    console.log($systemInfo)
    }, handleError)
}, handleError)

function handleError(error) {
    console.log(error.message)
}
