const btnGroupFolderElt = document.getElementById('btnGF');
const btnGroupElt = document.getElementById('btnGroup');
const inputGroupFoldrElt = document.getElementById('inputGF');
const form = document.getElementById('workspaceform');

btnGroupFolderElt.addEventListener('click', function(e){

    const inputGroupFoldrElt = document.getElementById('inputGF');

    var request = new XMLHttpRequest();
    request.open('POST', 'https://nc21.dev.arawa.fr/apps/groupfolders/folders');
    request.setRequestHeader('OCS-APIRequest', 'true');
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.send( 'mountpoint=' + inputGroupFoldrElt.value );
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

inputGroupFoldrElt.addEventListener('input', function(e){
    var espaceManagerName = document.getElementById('espaceManagerName');
    espaceManagerName.value = e.target.value;
});

form.addEventListener('submit', async function(e){
    e.preventDefault();

    let folderId = null;

    if(form.mountpoint.value !== ""){
        folderId = await createGroupFolder(form.mountpoint.value);
    }
    
    if(form.espaceManagerName.value !== ''){
        createGroupEspaceManager(form.espaceManagerName.value);
    }

    if(form.espaceManagerName.value !== '' && folderId !== null){
        addEspaceManagerToGroupFolder(form.espaceManagerName.value, folderId);
    }

});

const createGroupFolder = (mountpoint) => {

    const myHeaders = new Headers();
    myHeaders.append('OCS-APIRequest', 'true');
    myHeaders.append('Content-Type', 'application/x-www-form-urlencoded');

    return fetch( 
        'https://nc21.dev.arawa.fr/apps/groupfolders/folders',
        { method: 'POST', headers: myHeaders, body: "mountpoint=" + mountpoint   }
    )
    .then(response => response.text())
    .then(xmlString => $.parseXML(xmlString))
    .then(function(data){
            return data.documentElement.childNodes[3].childNodes[1].textContent
        }
    );
}

function createGroupEspaceManager(groupname){

    var requestGroup = new XMLHttpRequest();
    requestGroup.open('POST', 'https://nc21.dev.arawa.fr/ocs/v1.php/cloud/groups');
    requestGroup.setRequestHeader('OCS-APIRequest', 'true');
    requestGroup.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    requestGroup.send( "groupid=" + 'GE-' + groupname );
}

function addEspaceManagerToGroupFolder(gid, folderId){

    const myHeaders = new Headers();
    myHeaders.append('OCS-APIRequest', 'true');
    myHeaders.append('Content-Type', 'application/x-www-form-urlencoded');


    fetch(
        'https://nc21.dev.arawa.fr/apps/groupfolders/folders/' + folderId + '/groups',
        { method: 'POST', headers: myHeaders, body: "group="+ "GE-" + gid }
        )
        .then(
            console.log('Add an Espace Manager to groupfolder : Succed !')
        )
        .catch(
            console.log('Add an Espace Manager to groupfolder : Failled !')
        );
}