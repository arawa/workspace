# Workspace

Workspace allows managers to : 
- create shared workspaces
- delegate management of each workspace to users (workspace managers)  in order for them to
  - choose members
  - create groups 
  - configure advanced permissions on workspace folders
- all through a simple unified interface

This app is a Nextcloud extension of the Groupfolders app.

## Prerequisites

- npm v7.24.1
- composer v2.0.13
- make v3.82
- git v1.8
- PHP 7.4 max (PHP 8 ongoing)
- Nextcloud 23 minimum (Nextcloud 21 and 22 with our forked Groupfolders app, https://github.com/arawa/groupfolders, from the allow-admin-delegation-stable21 branch).

## 📦 Building the app

First, clone from your apps directory (example: `/var/www/html/nextcloud/apps/`).

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

## 📦 Create an artifact

```bash
make source
```

An artifact will be created in the `build/artifacts/source` from the project.

## Limit the Workspace app to specific groups

Limit the workspace app to groups : GeneralManager, WorkspaceManagers

## 🔧 Configure Groupfolders for Workspace

To use Wokspace app, you need to add the `GeneralManager` group in the `Group folders` field of the `Administration privileges` page.

`Settings` > `Admin privileges` from admin section.

## 📦 For Nextcloud 21 and 22, build [Arawa\Groupfolders](https://github.com/arawa/groupfolders)

Clone this app from apps directory (example: `/var/www/html/nextcloud/apps/`) and switch of the branch to be in `allow-admin-delegation-stable21`.

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

## 📋 Running tests

### Front-end

```bash
npm run test
```

### Back-end

```bash
composer run test
```

or

```bash
sudo -u nginx /usr/local/bin/composer run test
```
