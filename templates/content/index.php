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
        <button id='btnGF' type="button">Create</button>

        <br>

        <input type="text" id="inputGroup" placeholder="<?php p($l->t('Enter the group name'));?>" name="groupid" style="width: 320px;">
        <button type="button" id='btnGroup'>Create</button>

        <br>
        <label><?php p($l->t('This is the Espace Manager name :')) ?></label>
        <input type="text" disabled value="GE-" style="width:42px;" ><input name="espaceManagerName" id="espaceManagerName" type="text" disabled>

        <p>
            <label for="espacemanager"><?php p($l->t('Select the Espace Manager')) ?></label>
            <select name="espacemanager" id="espacemanager">
                <option value="default"><?php p($l->t('No Espace Manager')) ?></option>
                <?php for($number = 0; $number < count($_['usersByEspaceManagerGroup']) ; $number++){?>
                        <option value="<?php p($_['usersByEspaceManagerGroup'][$number]['gid']); ?>"><?php p($_['usersByEspaceManagerGroup'][$number]['email_address']); ?> - <?php p($_['usersByEspaceManagerGroup'][$number]['gid']) ?> </option>
                <?php } ?>
            </select>
        </p>

        <button type="submit" id='workspaceSubmit'><?php p($l->t('Send')); ?></button>

    </form>
</div>