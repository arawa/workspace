const btnGroupFolderElt = document.getElementById('btnGF');
const btnGroupElt = document.getElementById('btnGroup');
const inputGroupFoldrElt = document.getElementById('inputGF');
const form = document.getElementById('workspaceform');

const PREFIX = "wsp_";
const SUFFIX_GE = "_GE";
const SUFFIX_U = "_U";

inputGroupFoldrElt.addEventListener('input', function(e){
    var espaceManagerName = document.getElementById('espaceManagerName');
    var workspaceUserGroupName = document.getElementById('workspaceUserGroupName');
    
    espaceManagerName.value = e.target.value;
    workspaceUserGroupName.value = e.target.value;
});

form.addEventListener('submit', async function(e){
    e.preventDefault();

    let folderId = null;

    if(form.mountpoint.value !== ""){
        folderId = await createGroupFolder(form.mountpoint.value);
    }
    
    if(form.espaceManagerName.value !== ''){
        createGroupEspaceManager(form.espaceManagerName.value);
        await createWorkspaceUserGroup(form.workspaceUserGroupName.value);
    }

    if(form.espaceManagerName.value !== '' && folderId !== null){
        addEspaceManagerToGroupFolder(form.espaceManagerName.value, folderId);
        addWorkspaceUserGroupToGroupFolder(form.workspaceUserGroupName.value, folderId);
    }

    if(form.userEspaceManager.value !== ''){
        addUserToWorkspaceUserGroup(form.userEspaceManager.value, form.workspaceUserGroupName.value);
    }

    if(form.checkBoxAcl.checked && folderId !== null){
        activateAcl(form.espaceManagerName.value, folderId);
    }
});

const activateAcl = (groupname, folderId) => {

    const myHeaders = new Headers();
    myHeaders.append('OCS-APIRequest', 'true');
    myHeaders.append('Content-Type', 'application/x-www-form-urlencoded');

    fetch(
        'https://nc21.dev.arawa.fr/apps/groupfolders/folders/'+ folderId +'/acl',
        {
            method: 'POST',
            headers: myHeaders,
            body: "acl=1"
        }
    );

    fetch(
        'https://nc21.dev.arawa.fr/apps/workspace/space/' + folderId + '/group/' + PREFIX + groupname + SUFFIX_GE + '/acl',
        {
            method: 'GET',
        }
    );

}

const addUserToWorkspaceUserGroup = (uid, gid) =>{

    return fetch(
        'https://nc21.dev.arawa.fr/apps/workspace/add/user/'+ uid + '/toWspUserGroup/' + PREFIX + gid + SUFFIX_GE,
        {
            method: 'POST',
        }
    );

}

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
    requestGroup.send( "groupid=" + PREFIX + groupname + SUFFIX_GE );
}

function createWorkspaceUserGroup(groupname){

    var requestGroup = new XMLHttpRequest();
    requestGroup.open('POST', 'https://nc21.dev.arawa.fr/ocs/v1.php/cloud/groups');
    requestGroup.setRequestHeader('OCS-APIRequest', 'true');
    requestGroup.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    requestGroup.send( "groupid=" + PREFIX + groupname + SUFFIX_U );
}

function addEspaceManagerToGroupFolder(gid, folderId){

    const myHeaders = new Headers();
    myHeaders.append('OCS-APIRequest', 'true');
    myHeaders.append('Content-Type', 'application/x-www-form-urlencoded');


    fetch(
        'https://nc21.dev.arawa.fr/apps/groupfolders/folders/' + folderId + '/groups',
        { method: 'POST', headers: myHeaders, body: "group="+ PREFIX + gid + SUFFIX_GE }
        )
        .then(
            console.log('Add an Espace Manager to groupfolder : Succed !')
        )
        .catch(
            console.log('Add an Espace Manager to groupfolder : Failled !')
        );
}

function addWorkspaceUserGroupToGroupFolder(gid, folderId){

    const myHeaders = new Headers();
    myHeaders.append('OCS-APIRequest', 'true');
    myHeaders.append('Content-Type', 'application/x-www-form-urlencoded');


    fetch(
        'https://nc21.dev.arawa.fr/apps/groupfolders/folders/' + folderId + '/groups',
        { method: 'POST', headers: myHeaders, body: "group="+ PREFIX + gid + SUFFIX_U }
        )
        .then(
            console.log('Add an Espace Manager to groupfolder : Succed !')
        )
        .catch(
            console.log('Add an Espace Manager to groupfolder : Failled !')
        );
}