const HEADER_API = { 'key': 'OCS-APIRequest', 'value': 'true' };
const CONTENT_TYPE = { 'key': 'Content-Type', 'value': 'application/x-www-form-urlencoded' };
const HEADER_JSON = { 'key': 'Accept', 'value': 'application/json' };

const form = document.getElementById('createSubGroup');

const myHeaders = new Headers();
myHeaders.append(HEADER_API.key, HEADER_API.value);
myHeaders.append(CONTENT_TYPE.key, CONTENT_TYPE.value);
myHeaders.append(HEADER_JSON.key, HEADER_JSON.value);


function getAllFolders()
{
    return fetch(
        'https://nc21.dev.arawa.fr/apps/groupfolders/folders',
        {
            method: 'GET',
            headers: myHeaders
        })
        .then(response => response.json())
        .then(function(data) {
            // WHY: Don't I sucess to retun data.ocs.data ... ?
            // return data.ocs.data;

            for(let i in data.ocs.data){
                const newElt = document.createElement('option');

                newElt.value = data.ocs.data[i].id;
                newElt.textContent = data.ocs.data[i].mount_point;

                form.selectCreateSubGroup.appendChild(newElt);
            }

        });

}

function createSubGroup(groupname){
    var request = new XMLHttpRequest();
    request.open('POST', 'https://nc21.dev.arawa.fr/ocs/v1.php/cloud/groups');
    request.setRequestHeader('OCS-APIRequest', 'true');
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.setRequestHeader('Accept', 'application/json');
    request.send( "groupid=" + groupname );
}


function attachSubGroupToGroupFolder(gid, folderId){
    const myHeaders = new Headers();
    myHeaders.append('OCS-APIRequest', 'true');
    myHeaders.append('Content-Type', 'application/x-www-form-urlencoded');


    fetch(
        'https://nc21.dev.arawa.fr/apps/groupfolders/folders/' + folderId + '/groups',
        { method: 'POST', headers: myHeaders, body: "group="+ gid }
        )
        .then(
            console.log('Add an Espace Manager to groupfolder : Succed !')
        )
        .catch(
            console.log('Add an Espace Manager to groupfolder : Failled !')
        );

}

getAllFolders();

form.selectCreateSubGroup.addEventListener('change', function(e){
    console.log("Par là");
    console.log(e.target.value);

    if(e.target.value === "None"){
        form.subGroup.value = "wsp_";
        form.subGroup.setAttribute('disabled', true);
    }

    var request = new XMLHttpRequest();
    request.open('GET', 'https://nc21.dev.arawa.fr/apps/groupfolders/folders/'+ e.target.value );
    request.setRequestHeader('OCS-APIRequest', 'true');
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.setRequestHeader('Accept', 'application/json');

    request.onreadystatechange = function(){
        if(this.readyState == XMLHttpRequest.DONE && this.status == 200){
            var response = JSON.parse(this.responseText);
                form.subGroup.value = "wsp_" + response.ocs.data.mount_point + '_champLibre';
                form.subGroup.removeAttribute('disabled');
        }
    }
    
    request.send();

});

form.addEventListener('submit', async function(e){

    e.preventDefault();

    await createSubGroup(form.subGroup.value);
    attachSubGroupToGroupFolder(form.subGroup.value, form.selectCreateSubGroup.value);
});