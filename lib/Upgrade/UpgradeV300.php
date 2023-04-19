<?php

namespace OCA\Workspace\Upgrade;

use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\UserGroup;
use OCA\Workspace\WorkspaceManagerGroup;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IGroupManager;
use OCA\Workspace\Upgrade\Upgrade;
use OCA\Workspace\Upgrade\UpgradeInterface;

class UpgradeV300 implements UpgradeInterface
{
    private IGroupManager $groupManager;
    private SpaceMapper $spaceMapper;
    private UserGroup $userGroup;
    private WorkspaceManagerGroup $workspaceManagerGroup;
    private IAppConfig $appConfig;
    
    public function __construct(IGroupManager $groupManager,
        SpaceMapper $spaceMapper,
        UserGroup $userGroup,
        WorkspaceManagerGroup $workspaceManagerGroup,
        IAppConfig $appConfig)
    {
        $this->groupManager = $groupManager;
        $this->spaceMapper = $spaceMapper;
        $this->userGroup = $userGroup;
        $this->workspaceManagerGroup = $workspaceManagerGroup;
        $this->appConfig = $appConfig;
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
        $this->appConfig->setAppValue(Upgrade::CONTROL_MIGRATION_V3, '1');
    }
}
