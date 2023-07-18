<?php

declare(strict_types=1);

namespace OCA\Workspace\Groupfolders;

use Exception;
use OCA\GroupFolders\Folder\FolderManager;
use OCA\GroupFolders\Service\DelegationService;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\QueryException;
use OCP\Files\IRootFolder;
use OCP\Server;
use Psr\Container\ContainerInterface;

if (class_exists(FolderManager::class)) {
    class Wrapper {
        private FolderManager $fm;
        private DelegationService $delegationServiceGroupFolders;
    
        public function __construct(private IRootFolder $rootFolder)
        {
            try {
                // $this->fm = $appContainer->get(FolderManager::class);
                $this->fm = Server::get(FolderManager::class);
                $this->delegationServiceGroupFolders = Server::get(DelegationService::class);
            } catch (Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
    
        public function createFolder(string $mountpoint): int
        {
            return $this->fm->createFolder($mountpoint);
        }

        public function setFolderACL(int $folderId, bool $acl): void
        {
            $this->fm->setFolderACL($folderId, $acl);
        }

        public function addApplicableGroup(int $id, string $group): void
        {
            $this->fm->addApplicableGroup($id, $group);
        }

        public function setManageACL(int $folderId, string $type, string $id, bool $manageAcl): void
        {
            $this->fm->setManageACL($folderId, $type, $id, $manageAcl);
        }

        public function getFolder(int $id, int $rootStorageId): array
        {
            return $this->fm->getFolder($id, $rootStorageId);
        }

        private function formatFolder(array $folder): array {
            $folder['group_details'] = $folder['groups'];
            $folder['groups'] = array_map(function (array $group) {
                return $group['permissions'];
            }, $folder['groups']);

            return $folder;
        }

        public function getAllFoldersWithSize(int $rootStorageId): array {
            return $this->fm->getAllFoldersWithSize($rootStorageId);
        }

        private function getRootFolderStorageId(): ?int
        {
            return $this->rootFolder->getMountPoint()->getNumericStorageId();
        }
    }
} else {
    class Wrapper {
        private const MESSAGE = 'The groupfolder app was installed.';
        
        public function __construct()
        {
        }
        
        public function createFolder(string $mountpoint): int {
            throw new \Exception(self::MESSAGE);
            return 100;
        }

        public function setFolderACL(int $folderId, bool $acl): void {
            throw new \Exception(self::MESSAGE);
        }

        public function addApplicableGroup(int $id, string $group): void
        {
            throw new \Exception(self::MESSAGE);
        }

        public function setManageACL(int $folderId, string $type, string $id, bool $manageAcl): void
        {
            throw new \Exception(self::MESSAGE);
        }

        public function getFolder(int $id, int $rootStorageId): array
        {
            throw new \Exception(self::MESSAGE);
            return [];
        }
    }
}
