<?php

namespace OCA\Workspace\Files\Csv\ImportUsers;

<<<<<<< HEAD
class Header {
<<<<<<< HEAD
	public const DISPLAY_NAME = [
		"user"
	];
	
	public const ROLE = [
		"role",
	];

	public const FIELDS_REQUIRED = [ 'user', 'role' ];
=======
	public const DISPLAY_NAME = ["username", "displayname", "name"];
	public const ROLE = ["role", "status", "userrole"];
>>>>>>> 5d45ff9 (style(php): run composer cs:fix)
=======
class Header
{
    public const DISPLAY_NAME = ["username", "displayname", "name"];
	public const ROLE = ["role", "status", "userrole"];
>>>>>>> 4e419d2 (refactor(php): Split the import users code)
}
