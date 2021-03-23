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
    <form id="groupfoldernoadminform" action="https://nc21.dev.arawa.fr/apps/groupfolders/folders" method="POST">
        <input type="text" id="inputGF" placeholder="<?php p($l->t('Enter the groupfolder name')); ?>" name="mountpoint" style="width: 320px;">
        <button id='btnGF' type="button">Create</button>

        <br>

        <input type="text" id="inputGroup" placeholder="<?php p($l->t('Enter the group name'));?>" name="groupid" style="width: 320px;">
        <button type="button" id='btnGroup'>Create</button>
    </form>
</div>