export {parseXML}
function parseXML(file) {
    const data = {}
    const parser = new DOMParser()
    const xmlDoc = parser.parseFromString(file, 'text/xml')
    // eslint-disable-next-line max-len
    data.externalKey = xmlDoc.getElementsByTagName('fssp:ExternalKey')[0] ? xmlDoc.getElementsByTagName('fssp:ExternalKey')[0].textContent : ''
    // eslint-disable-next-line max-len
    data.docNumber = xmlDoc.getElementsByTagName('fssp:IdDocNo')[0] ? xmlDoc.getElementsByTagName('fssp:IdDocNo')[0].textContent : ''
    // eslint-disable-next-line max-len
    data.docDate = xmlDoc.getElementsByTagName('fssp:IdDocDate')[0] ? xmlDoc.getElementsByTagName('fssp:IdDocDate')[0].textContent : ''
    return data
}
