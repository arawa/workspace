# Workspace

Workspace app is the best tool to manage your projects and share between teams !

This app is a plugin for Groupfolders app.

# Prerequisites

- npm v7.24.1
- composer v2.0.13
- make v3.82
- git v1.8
- Nextcloud v21 minimum
- Groupfolders (https://github.com/arawa/groupfolders from the `allow-admin-delegation-stable21` branch).


# 📦 Build [Arawa\Groupfolders](https://github.com/arawa/groupfolders)

You must clone this app from apps directory (example: `/var/www/html/nextcloud/nextcloud21/apps/`) and switch of the branch to be in `allow-admin-delegation-stable21`.

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


# 📦 Building the app

First, you must clone from your apps directory (example: `/var/www/html/nextcloud/nextcloud21/apps/`).

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


# 🔧 Configure Groupfolders for Workspace

To use Wokspace app, you need to add the `GeneralManager` and `WorkspacesManagers` groups from `Groupfolder admin delegation` page.

This page is in `Settings` > `Groupfolders` from admin section.


# 📦 Creating of an artifact

```bash
make source
```

An artifact will be created in the `build/artifacts/source` from the project.


# 🌍 Publish to App Store

First get an account for the [App Store](http://apps.nextcloud.com/) then run:

    make && make appstore

The archive is located in build/artifacts/appstore and can then be uploaded to the App Store.


# 📋 Running tests

## Front-end

```bash
npm run test
```

## Back-end

```bash
composer run test
```

or

```bash
sud -u nginx /usr/local/bin/composer run test
```