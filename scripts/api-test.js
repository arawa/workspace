#!/usr/local/bin/node

/**
 * To use this script, you need to install Node.JS 
 * 
 * - run `npm install` to install the required dependencies in this folder
 * 
 * - go on Nextcloud and create an app password for your user
 * 
 *  - report the login and the key in the api-test.conf file
 * 
 * 	- set the nextcloud url in the api-test.conf file
 * 
 * - run 'npm run api -- -v' to execute the script
 * 
 */

import yargs from "yargs";
import axios from "axios";
import toml from "toml";
import convict from "convict";
import readline from 'node:readline/promises';
import { type } from "node:os";

// our .conf is using TOML format
convict.addParser({ extension: 'conf', parse: toml.parse });
const rl = readline.createInterface({ input: process.stdin, output: process.stdout});

const _exit = process.exit;
var verbose = false;

const worskpace_api = 'index.php/apps/workspace/api/v1/'

function WorkspaceApi(nextcloud, login, key) {
	this.login = login
	this.key = key;
	this.url = nextcloud + worskpace_api;
	this._error = null;
}

WorkspaceApi.prototype._api = async function(route, method = 'get', data = null) {
	const url = this.url + route;
	this._error = null;
	try {
		let request = {
            url: url,
            method: method,
            auth: {
                username: this.login,
                password: this.key
            },
            headers: {
                'OCS-APIRequest': true,
            }
        };
		if (data) {
			request.data = data;
		}
        const response = await axios(request);
        return response.data.ocs ? response.data.ocs.data : response.data;
	} catch (error) {
		this._error = error;
        return null;
    }
};

WorkspaceApi.prototype.logError = function (message) {
	if (this._error) {
		if (verbose) {
			console.error('Error: ' + message);
			console.error('Status: ' + this._error.response.status);
			console.error('Data: ' + JSON.stringify(this._error.response.data, null, 2));
		} else {
			console.error('Error: ' + message + ' (status: ' + this._error.response.status + ')');
			if (this._error.response.data && this._error.response.data.message) {
				console.error('Message: ' + this._error.response.data.message);
			}
		}
	}
}


WorkspaceApi.prototype.get = async function(id) {
	return await this._api('spaces/' + id);
};

WorkspaceApi.prototype.delete = async function(id) {
	return await this._api('spaces/' + id, 'delete');
};

WorkspaceApi.prototype.listAll = async function() {
	return await this._api('spaces');
};

WorkspaceApi.prototype.create = async function(name) {
	return await this._api('spaces', 'post', { name: name });
};

WorkspaceApi.prototype.edit = async function(id, params = null) {
	return await this._api('spaces/'+id, 'patch', params);
};

WorkspaceApi.prototype.addSubGroup = async function(id, name) {
	return await this._api('spaces/'+id+'/groups', 'post', { name: name });
};

WorkspaceApi.prototype.getGroups = async function(id) {
	return await this._api('spaces/'+id+'/groups', 'get');
};

WorkspaceApi.prototype.setWM = async function(id, uid, isWM = true) {
	return await this._api('spaces/'+id+'/workspace-manager', isWM ? 'post' : 'delete', { uid: uid });
};

WorkspaceApi.prototype.addUsers = async function(id, uids) {
	return await this._api('spaces/'+id+'/users', 'post', { uids: uids });
};
WorkspaceApi.prototype.removeUsers = async function(id, uids) {
	return await this._api('spaces/'+id+'/users', 'delete', { uids: uids });
};

async function ask_continue() {
	const res = await rl.question("Continue ? ([y]/n) ");
	if (res === 'y' || res === 'Y' || res === '') {
		return true;
	}
	return false;
}

/**
 * Main program.
 */

async function main() {
	
	// Reading configuration
	const configPath = options.conf;
	const config = convict({
		api: {
			nextcloud: 'https://nextcloud.com/',
			login: 'admin',
			key: 'xxx',
			user: 'admin',
		},
	});
	config.loadFile(configPath);
	const apiConf = config.get('api');

	let current_workspace = options.id;
	verbose = options.verbose;

	// Verbose mode
	if (verbose) {
		console.log('Nextcloud api: ' + apiConf.nextcloud);
	}

	// create api
	const workspaceApi = new WorkspaceApi(apiConf.nextcloud, apiConf.login, apiConf.key);

	if (options.step <= 1) {
		console.log('Step 1: Get non-existing workspace');
		const res = await workspaceApi.get(9999); // trying to get a non-existing workspace
		if (res === null) {
			workspaceApi.logError('Error while getting workspace 9999');
		}
		if (!options.yes) {
			if (!await ask_continue()) { return; } 
		}
	}
	// list all workspaces
	if (options.step <= 2) {
		console.log('Step 2: List all workspaces');
		const res = await workspaceApi.listAll(); // trying to get a non-existing workspace
		if (res === null) {
			workspaceApi.logError('Error while getting workspace 9999');
		}
		if (!options.yes) {
			if (!await ask_continue()) { return; } 
		}
	}
	
	// create workspace
	if (options.step <= 3) {
		console.log('Step 3: Creating a workspace "Test Workspace"');
		const res = await workspaceApi.create('Test Workspace');
		if (res === null) {
			workspaceApi.logError('Error while creating workspace');
		} else {
			if (verbose) {
				console.log('Created workspace:');
				console.log(JSON.stringify(res, null, 2));
			} else {
				console.log('Created workspace: ' + res.id + ' - ' + res.name);
			}
		}
		if (!options.yes) {
			if (!await ask_continue()) { return; } 
		}
		current_workspace = res.id; // save the created workspace id for next steps
	}
	
	// get existing workspace
	if (options.step <= 4) {
		console.log('Step 4: Get Created Workspace');
		const spaceId = current_workspace; // use the created workspace id or a default one
		const res = await workspaceApi.get(spaceId);
		if (res === null) {
			workspaceApi.logError('Error while getting workspace ' + spaceId);
		} else {
			if (verbose) {
				console.log('Workspace ' + spaceId);
				console.log(JSON.stringify(res, null, 2));
			} else {
				console.log('Workspace: ' + res.id + ' - ' + res.name + ' - '
					+ Object.keys(res.groups).length + ' groups - '
					+ Object.keys(res.added_groups).length + ' added groups - '
					+ Object.keys(res.users).length + ' users');
			}
		}
		if (!options.yes) {
			if (!await ask_continue()) { return; } 
		}
	}

	// edit existing workspace
	if (options.step <= 5) {
		console.log('Step 5: Edit Created Workspace (name, color, quota)');
		const spaceId = current_workspace;
		const res = await workspaceApi.edit(spaceId, {name: 'Test Workspace Modified', color: '#ff0000', quota: 1000000000});
		if (res === null) {
			workspaceApi.logError('Error while editing workspace ' + spaceId);
		} else {
			if (verbose) {
				console.log('Workspace ' + spaceId);
				console.log(JSON.stringify(res, null, 2));
			} else {
				console.log('Workspace: ' + spaceId + ' - ' + res.name + ' - ' + res.color + ' - ' + res.quota + ' bytes');
			}
		}
		if (!options.yes) {
			if (!await ask_continue()) { return; } 
		}
	}

	// Create subgroup for the workspace
	if (options.step <= 6) {
		console.log('Step 6: Create a subgroup "My SubGroup" in the created workspace');
		const spaceId = current_workspace;
		const res = await workspaceApi.addSubGroup(spaceId, 'MySubGroup');
		if (res === null) {
			workspaceApi.logError('Error while adding subgroup ' + spaceId);
		} else {
			if (verbose) {
				console.log('Workspace ' + spaceId);
				console.log(JSON.stringify(res, null, 2));
			} else {
				console.log('Workspace: ' + spaceId + ' - Group ID: ' + res.gid);
			}
		}
		if (!options.yes) {
			if (!await ask_continue()) { return; } 
		}
	}

	// List subgroup for the workspace
	if (options.step <= 7) {
		console.log('Step 7: List all groups in the created workspace');
		const spaceId = current_workspace;
		const res = await workspaceApi.getGroups(spaceId);
		if (res === null) {
			workspaceApi.logError('Error while getting subgroups ' + spaceId);
		} else {
			if (verbose) {
				console.log('Workspace: ' + spaceId);
				console.log(JSON.stringify(res, null, 2));
			} else {
				console.log('Workspace: ' + spaceId);
				Object.values(res).forEach((group) => {
					console.log(' - Group ID: ' + group.gid + ' - Name: ' + group.displayName + ' - Users : ' + group.usersCount);
				});
			}
		}
		if (!options.yes) {
			if (!await ask_continue()) { return; } 
		}
	}

	// Add user as in the workspace
	if (options.step <= 8 ) {
		console.log('Step 8: Add user in the Workspace');
		const spaceId = current_workspace;
		const res = await workspaceApi.addUsers(spaceId, [ apiConf.user ]);
		if (res === null) {
			workspaceApi.logError('Error while adding user ' + apiConf.user + ' in space ' + spaceId);
		} else {
			if (verbose) {
				console.log('Workspace: ' + spaceId);
				console.log(JSON.stringify(res, null, 2));
			} else {
				console.log('Workspace: ' + spaceId);
				console.log(res.message);
			}
		}
		if (!options.yes) {
			if (!await ask_continue()) { return; } 
		}
	}

	// Promote user as Workspace Manager
	if (options.step <= 9 ) {
		console.log('Step 9: Promote user as Workspace Manager');
		const spaceId = current_workspace;
		const res = await workspaceApi.setWM(spaceId, apiConf.user, true);
		if (res === null) {
			workspaceApi.logError('Error while promoting user ' + apiConf.user + ' in space ' + spaceId);
		} else {
			if (verbose) {
				console.log('Workspace: ' + spaceId);
				console.log(JSON.stringify(res, null, 2));
			} else {
				console.log('Workspace: ' + spaceId);
				console.log('User: ' + res.uid + ' was promoted');
			}
		}
		if (!options.yes) {
			if (!await ask_continue()) { return; } 
		}
	}

	// Degrade user as Workspace Manager
	if (options.step <= 10 ) {
		console.log('Step 10: Degrade user from Workspace Manager as simple user');
		const spaceId = current_workspace;
		const res = await workspaceApi.setWM(spaceId, apiConf.user, false);
		if (res === null) {
			workspaceApi.logError('Error while degrading user ' + apiConf.user + ' from space '+ spaceId);
		} else {
			if (verbose) {
				console.log('Workspace: ' + spaceId);
				console.log(JSON.stringify(res, null, 2));
			} else {
				console.log('Workspace: ' + spaceId);
				console.log('User: ' + apiConf.user + ' was degraded as simple user');
			}
		}
		if (!options.yes) {
			if (!await ask_continue()) { return; } 
		}
	}

	// Remove user from Workspace Manager
	if (options.step <= 11 ) {
		console.log('Step 11: Remove user from Workspace Manager');
		const spaceId = current_workspace;
		const res = await workspaceApi.removeUsers(spaceId, [ apiConf.user ]);
		if (res === null) {
			workspaceApi.logError('Error while removing user ' + apiConf.user + ' from space ' + spaceId);
		} else {
			if (verbose) {
				console.log('Workspace: ' + spaceId);
				console.log(JSON.stringify(res, null, 2));
			} else {
				console.log('Workspace: ' + spaceId);
				console.log('User: ' + apiConf.user + ' was removed');
			}
		}
		if (!options.yes) {
			if (!await ask_continue()) { return; } 
		}
	}

	// Delete existing workspace
	if (options.step <= 99 && current_workspace) {
		console.log('Step 99: Delete Created Workspace');
		const res = await workspaceApi.delete(current_workspace);
		if (res === null) {
			workspaceApi.logError('Error while deleting workspace ' + current_workspace);
		} else {
			if (verbose) {
				console.log('Deleted workspace:');
				console.log(JSON.stringify(res, null, 2));
			} else {
				console.log('Deleted workspace: ' + current_workspace);
			}
		}
		if (!options.yes && options.step == 0) {
			if (!await ask_continue()) { return; } 
		}
	}

	// Create a workspace again with forbidden characters
	if (options.step <= 110) {
		console.log('Step 110: Creating a workspace "Test/Workspace"');
		const res = await workspaceApi.create('Test/Workspace');
		if (res === null) {
			workspaceApi.logError('Error while creating workspace');
		} else {
			if (verbose) {
				console.log('Created workspace:');
				console.log(JSON.stringify(res, null, 2));
			} else {
				console.log('Created workspace: ' + res.id + ' - ' + res.name);
			}
		}
		if (!options.yes) {
			if (!await ask_continue()) { return; } 
		}
	}
}

// -----------------------------------------------------------------------------
// arguments
// -----------------------------------------------------------------------------
const options = yargs(process.argv.slice(2))
 .usage("Usage: $0 [options]")
 .options("c", {alias: "conf", describe: "Config file", demandOption: false, boolean: false, default: 'api-test.conf'})
	.options("s", { alias: "step", describe: "Run from step:\n"
		+ " 1 : get non existing workspace (will fail)\n"
		+ " 2 : list all workspaces\n"
		+ " 3 : create a workspace\n"
		+ " 4 : get existing workspace\n"
		+ " 5 : edit existing workspace\n"
		+ " 6 : create subgroup\n"
		+ " 7 : list groups\n"
		+ " 8 : add user in workspace\n"
		+ " 9 : promote user as Workspace Manager\n"
		+ " 10 : degrade user from Workspace Manager as simple user\n"
		+ " 11 : remove user from workspace\n"
		+ " 99: delete workspace\n"
		+ " 110: create workspace with forbidden characters (will fail)\n"
		, boolean: false, default: 0
	})
 .options("y", {alias: "yes", describe: "Answer yes to all waiting inputs", boolean: true, default: false })
 .options("i", {alias: "id", describe: "Use this id as working workspace", boolean: false, default: 0})
 .options("v", { alias: "verbose", describe: "Verbose mode", boolean: true, default: false })
 .help('h')
 .alias('V', 'version')
 .alias('h', 'help')
 .strictOptions(true)
 .argv;

if (!_exit.exited) {
	await main();
}
rl.close();
