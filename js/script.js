const btnGFElt = document.getElementById('btnGF');
const btnGroupElt = document.getElementById('btnGroup');

btnGFElt.addEventListener('click', function(e){

    const inputGFElt = document.getElementById('inputGF');

    var request = new XMLHttpRequest();
    request.open('POST', 'https://nc21.dev.arawa.fr/apps/groupfolders/folders');
    request.setRequestHeader('OCS-APIRequest', 'true');
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.send( 'mountpoint=' + inputGFElt.value );
});

btnGroupElt.addEventListener('click', function(e){
    const inputGroupElt = document.getElementById('inputGroup');

    var myData = new FormData();
    myData.append('groupid', inputGroupElt.value);

    var requestGroup = new XMLHttpRequest();
    requestGroup.open('POST', 'https://nc21.dev.arawa.fr/ocs/v1.php/cloud/groups');
    requestGroup.setRequestHeader('OCS-APIRequest', 'true');
    requestGroup.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    requestGroup.send( "groupid=" + inputGroupElt.value );
});