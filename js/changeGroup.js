const form = document.getElementById('formChangeGroup');


const myHeaders = new Headers();
myHeaders.append('Content-Type', 'application/x-www-form-urlencoded');
myHeaders.append('Accept', 'application/json');

const headersApiGroupFolders = new Headers();
headersApiGroupFolders.append('Content-Type', 'application/x-www-form-urlencoded');
headersApiGroupFolders.append('Accept', 'application/json');
headersApiGroupFolders.append('OCS-APIRequest', 'true');


form.selectGroupFolders.addEventListener('change', function(e){
    
    document.getElementById('btnManager').disabled = false;
    document.getElementById('btnUsers').disabled = false;

    const userByGroup = JSON.parse(e.target.value);

    if(
        /^GE-/.test(userByGroup.gid) ||
        /^Manager_/.test(userByGroup.gid) ||
        /_GE$/.test(userByGroup.gid)
    ){
        document.getElementById('btnManager').checked = true;
    }
    else{
        document.getElementById('btnUsers').checked = true;

    }
});

form.addEventListener('submit', function(e){
    e.preventDefault();
    
    const userByGroup = JSON.parse(form.selectGroupFolders.value);
    let newGroup = "";

    fetch(OC.generateUrl(`apps/workspace/remove/user/${userByGroup.uid}/groups`),
    {
        method: 'DELETE',
        headers: myHeaders,
        body: `gid=${userByGroup.gid}`
    });

    if(form.choiceGroup.value === "Manager"){

        if(/^U-/.test(userByGroup.gid)){
            newGroup = "GE-" + userByGroup.mount_point_groupfolders;
        }
        else if(/^Users_/.test(userByGroup.gid)){
            newGroup = "Manager_" + userByGroup.mount_point_groupfolders;
        }
        else if(/_U$/.test(userByGroup.gid)){
            newGroup =  "wsp_" + userByGroup.mount_point_groupfolders + "_GE";
        }

    }
    else{

        if(/^GE-/.test(userByGroup.gid)){
            newGroup = "U-" + userByGroup.mount_point_groupfolders;
        }
        else if(/^Manager_/.test(userByGroup.gid)){
            newGroup = "Users_" + userByGroup.mount_point_groupfolders;
        }
        else if(/_GE$/.test(userByGroup.gid)){
            newGroup =  "wsp_" + userByGroup.mount_point_groupfolders + "_U";
        }     
    }

    if(newGroup !== ""){
        fetch(OC.generateUrl(`/apps/workspace/add/user/${userByGroup.uid}/toWspUserGroup/${newGroup}`),
        {
            method: 'POST',
            headers: myHeaders,
            body: `gid=${newGroup}`
        });        
    }


});

fetch(OC.generateUrl('/apps/groupfolders/folders'),
{
    method: 'GET',
    headers: headersApiGroupFolders
})
.then(response => response.json())
.then(function(data){

    let jsons = [];

    form.selectGroupFolders.childNodes.forEach(function(node){
        if(node.nodeType === document.ELEMENT_NODE){
            if(node.value !== "None"){
                jsons.push(JSON.parse(node.value));
            }
        }
    });

    const newValuesForOptions = [];

    for(const json of jsons){
        for(let i in data.ocs.data){
            const re = new RegExp(data.ocs.data[i].mount_point);
            if(re.test(json.gid)){
                json.mount_point_groupfolders = data.ocs.data[i].mount_point;
                newValuesForOptions.push(json);
            }
        };
    }

    form.selectGroupFolders.childNodes.forEach(function(node){
        for(const json of jsons){
            if(node.nodeType === document.ELEMENT_NODE){
                if(node.value !== "None"){
                    if(JSON.parse(node.value).gid === json.gid && JSON.parse(node.value).uid === json.uid){
                        node.value = JSON.stringify(json);
                    }
                }
            }
        }
    });
});