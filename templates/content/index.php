<h1 class="inlineblock"><?php p($l->t('Groupfolders for General Manager')); ?></h1>
<div class="section">
    <?php
        for($number = 0; $number < count($_['users']); $number++){
            p( "- " . $_['users'][$number]->getDisplayName());
            print('<br/>');

            p( "- " . $_['users'][$number]->getUID());
            print('<br/>');

            p( "- " . $_['users'][$number]->getBackendClassName());
            print('<br/>');

        }

    ?>
</div>
<div class="section">
    <form id="workspaceform" action="https://nc21.dev.arawa.fr/apps/groupfolders/folders" method="POST">
        <input type="text" id="inputGF" placeholder="<?php p($l->t('Enter the groupfolder name')); ?>" name="mountpoint" style="width: 320px;">

        <br>

        <label><?php p($l->t('This is the Espace Manager name :')) ?></label>
        <input type="text" disabled value="wsp_" style="width:42px;" ><input name="espaceManagerName" id="espaceManagerName" type="text" disabled><input type="text" disabled value="_GE" style="width:42px;" >

        <br>

        <label><?php p($l->t('This is the User Workspace Group name :')) ?></label>
        <input type="text" disabled value="wsp_" style="width:42px;" ><input name="workspaceUserGroupName" id="workspaceUserGroupName" type="text" disabled><input type="text" disabled value="_U" style="width:42px;" >
        
        <br>

        <label for="userEspaceManager"><?php p($l->t('Select the Espace Manager')) ?></label>
        <input type="text" name="userEspaceManager" id="userEspaceManager" style="width:200px;">
       
        <br>

        <div>
            <input type="checkbox" name="checkBoxAcl" id="checkBoxAcl"><label for="checkBoxAcl">Enabled Advanced Permissions</label>
        </div>


        <br>

        <div>
            <select name="spaceQuota" id="spaceQuota">
                <option value="1073741274">1 GB</option>
                <option value="5368709120">5 GB</option>
                <option value="10737418240">10 GB</option>
                <option value="-3" selected>Unlimited</option>
            </select>
        </div>

        <br>

        <button type="submit" id='workspaceSubmit'><?php p($l->t('Send')); ?></button>

    </form>
</div>