# Workspace
Create shared workspaces and delegate management of their members and groups.

<p align="center">
<img width="350" src="https://github.com/arawa/workspace/raw/master/img/workspace_logo.png" alt="Workspace Logo">
</p>

Workspace allows managers to :
- Create shared workspaces
- Delegate management of each workspace to users (workspace managers) in order for them to
  - choose members
  - create groups
  - configure advanced permissions on workspace folders
- All through a simple unified interface, designed to simplify your users' experience and make them autonomous

This app is a Nextcloud extension of the Groupfolders app.

## Usage
### Requirements
- PHP < 7.4 (PHP 8 ongoing)
- Nextcloud 23+
  - Nextcloud 21 and 22 require our forked Groupfolders app available on https://github.com/arawa/groupfolders, from the `allow-admin-delegation-stable21` branch.

### Limit the Workspace app to specific groups

In your "application management" administrator interface, limit the application to the following groups: `GeneralManager` and `WorkspaceManagers`

### 🔧 Configure Groupfolders for Workspace

To use the Wokspace app, you need to add the `GeneralManager` group in the `Group folders` field of the `Administration privileges` page.

`Settings` > `Admin privileges` from admin section.

## Development and Build
### Requirements
- npm v7.24.1
- composer v2.0.13
- make v3.82
- git v1.8

### 📦 Building the app

First, clone into your apps directory (example: `/var/www/html/nextcloud/apps/`).

```bash
git clone https://github.com/arawa/workspace.git
```

Then, you can build app :

```bash
cd workspace
make
```

🚨 **Caution** : You must install `npm` and `composer` before use `make` command line.

If it's okay, we can use or dev the Workspace app !

### 📦 Create an artifact

```bash
make source
```

An artifact will be created in the `build/artifacts/source` from the project.



### 📦 For Nextcloud 21 and 22, build [Arawa\Groupfolders](https://github.com/arawa/groupfolders)

Clone this app into your apps directory (example: `/var/www/html/nextcloud/apps/`) and switch of the branch to be in `allow-admin-delegation-stable21`.

```bash
git clone https://github.com/arawa/groupfolders.git
cd groupfolders
git checkout allow-admin-delegation-stable21
```

Then, you can build.

```bash
make
```

🚨 **Caution** : You must install `npm` and `composer` before use `make` command line.

After this, you can enable the Groupfolders app.

### 📋 Running tests

#### Front-end

```bash
npm run test
```

#### Back-end

```bash
composer run test
```

or

```bash
sudo -u nginx /usr/local/bin/composer run test
```
