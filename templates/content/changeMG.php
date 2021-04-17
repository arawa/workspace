<h1 class="inlineblock"><?php p($l->t('Change Manager General')); ?></h1>

<br>

<?php
    // p(var_dump($_['users']));
?>

<form id="formChangeGroup">

    <br>

    <select name="selectGroupFolders">
        <option value="None">None</option>
        <?php foreach(($_['users']) as $user){?>
            <?php foreach($user['gids'] as $gid){ ?>
                <?php
                    $userByGroup = [
                        "gid" => $gid,
                        "uid" => $user['uid']
                    ];
                ?>

                <option value='<?php p(json_encode($userByGroup)) ?>'><?php p($userByGroup['uid'])?> - <?php p($userByGroup['gid']) ?></option>
            <?php } ?>
        <?php } ?>
    </select>

    <p>
        <input type="radio" name="choiceGroup" value="Manager" id="btnManager" disabled><label for="btnManager">Manager</label>
        <br>
        <input type="radio" name="choiceGroup" value="Users" id="btnUsers" disabled><label for="btnUsers">Users</label>
        <br>
    </p>

    <button type="submit">Change</button>
</form>
