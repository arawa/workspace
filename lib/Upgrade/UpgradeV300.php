<?php

namespace OCA\Workspace\Upgrade;

use OCP\IGroupManager;
use OCA\Workspace\UserGroup;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\GroupsWorkspace;
use OCA\Workspace\Upgrade\Upgrade;
use OCA\Workspace\WorkspaceManagerGroup;
use OCP\AppFramework\Services\IAppConfig;
use OCA\Workspace\Upgrade\UpgradeInterface;
use OCA\Workspace\Db\GroupFoldersGroupsMapper;

class UpgradeV300 implements UpgradeInterface
{
    private IGroupManager $groupManager;
    private SpaceMapper $spaceMapper;
    private UserGroup $userGroup;
    private WorkspaceManagerGroup $workspaceManagerGroup;
    private IAppConfig $appConfig;
    private GroupFoldersGroupsMapper $groupfoldersGroupsMapper;
    
    public function __construct(IGroupManager $groupManager,
        SpaceMapper $spaceMapper,
        UserGroup $userGroup,
        WorkspaceManagerGroup $workspaceManagerGroup,
        IAppConfig $appConfig,
        GroupFoldersGroupsMapper $groupfoldersGroupsMapper)
    {
        $this->groupManager = $groupManager;
        $this->spaceMapper = $spaceMapper;
        $this->userGroup = $userGroup;
        $this->workspaceManagerGroup = $workspaceManagerGroup;
        $this->appConfig = $appConfig;
        $this->groupfoldersGroupsMapper = $groupfoldersGroupsMapper;
    }

    public function upgrade(): void
    {
        // Loop on GE- groups
        $workspaceManagerGroups = $this->groupManager->search(WorkspaceManagerGroup::getPrefix());
        foreach($workspaceManagerGroups as $group) {
            $groupname = $group->getGID();
            $groupnameSplitted = explode('-', $groupname);
            $spaceId = (int)$groupnameSplitted[2];
            $space = $this->spaceMapper->find($spaceId);
            $group->setDisplayName(
                $this->appConfig->getAppValue('DISPLAY_PREFIX_MANAGER_GROUP') . $space->getSpaceName()
            );
        }
        // Loop on U- groups
        $userGroups = $this->groupManager->search(UserGroup::getPrefix());
        foreach($userGroups as $group) {
            $groupname = $group->getGID();
            $groupnameSplitted = explode('-', $groupname);
            $spaceId = (int)$groupnameSplitted[2];
            $space = $this->spaceMapper->find($spaceId);
            $group->setDisplayName(
                $this->appConfig->getAppValue('DISPLAY_PREFIX_USER_GROUP') . $space->getSpaceName()
            );
        }

        // Début du changement des noms de groupes.
        $subgroups = $this->groupfoldersGroupsMapper->getSpacenamesGroupIds();
        foreach ($subgroups as $subgroup) {
            $group = $this->groupManager->get($subgroup['group_id']);
            if (is_null($group)) {
                throw new \Exception('Group not found for the migration of workspace to version 3.0.0');
            }
            $oldSubgroup = $subgroup['group_id'];
            $subgroupExploded = explode('-', $oldSubgroup);
            $subgroupSliced = array_slice($subgroupExploded, 0, -1);
            $groupname = implode('-', $subgroupSliced);
            $groupname = 'G-' . $groupname . '-' . $subgroup['space_name'];
            $group->setDisplayName($groupname);
        }

        $this->appConfig->setAppValue(Upgrade::CONTROL_MIGRATION_V3, '1');
    }
}
