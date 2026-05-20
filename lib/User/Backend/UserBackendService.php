<?php

namespace OCA\Workspace\User\Backend;

use OCP\IUser;
use OCP\IUserManager;

class UserBackendService {
	public function __construct(
		private IUserManager $userManager,
		// private UserBackend $userBackend,
	)
	{
	}

	private array $users = [];
	

	private function format(IUser $user): array {
		return [
			$user->getUID() => [
				'displayName' => $user->getDisplayName(),
			]
		];
	}
	
	public function initUsers(): void {
		$users = $this->userManager->searchDisplayName('Workspace');
		$users = array_filter($users, fn ($user) => str_starts_with($user->getUID(), 'SPACE-UWS-'));
		$this->users = array_map($this->format(...), $users);
	}

	public function createUser(int $spaceId): void {
		try {
			$user = $this->userManager->createUser('SPACE-UWS-' . $spaceId, 'aaa');
			$user->setDisplayName('Workspace' . $spaceId);
		} catch (\Exception $e) {
			var_dump($e->getMessage());
		}
	}

	public function getUsers(): array {
		if (empty($this->users)) {
			$this->initUsers();
		}
	
		return $this->users;
	}

	public function userExists(string $uid): bool {
		$users = $this->getUsers();
		return isset($users[$uid]);
	}

	public function getUser(string $uid): ?IUser {
		$users = $this->getUsers();
		if (isset($users[$uid])) {
			return $users[$uid];
		}
		return null;
	}

	public function getUserBySpaceId(int $spaceId): array {
		$uid = 'SPACE-UWS-' . $spaceId;
		$users = $this->getUsers();
		if (isset($users[$uid])) {
			return $users[$uid];
		}
		return [];
	}
}
