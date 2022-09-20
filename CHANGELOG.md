# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Chore

- bump @nextcloud/l10n from 1.4.1 to 1.6.0 ( [#494](https://github.com/arawa/workspace/pull/494) )
- bump jsdom from 19.0.0 to 20.0.0 #514 ( [#514](https://github.com/arawa/workspace/pull/514) )
- npm: bump prettier from 2.6.2 to 2.7.1 ( [#513](https://github.com/arawa/workspace/pull/513) )
- bump vue and vue-template-compiler ( [#524](https://github.com/arawa/workspace/pull/524) )
- bump jest-environment-jsdom from 28.1.0 to 29.0.3 ( [#562](https://github.com/arawa/workspace/pull/562) )
- bump jest from 28.1.0 to 29.0.3 ( [#563](https://github.com/arawa/workspace/pull/563) )
- bump babel-jest from 28.1.0 to 29.0.3 ( [#564](https://github.com/arawa/workspace/pull/564) )
- bump @nextcloud/axios from 1.10.0 to 2.0.0 ( [#565](https://github.com/arawa/workspace/pull/565) )

## [1.2.2] - 2022-08-19

### Chore

- Update the readme ( [#535](https://github.com/arawa/workspace/pull/535) )
- Update the info.xml and extend the app for NC24 ( [#356](https://github.com/arawa/workspace/pull/536) )

## [1.2.1] - 2022-06-24

### Fixed

- Prevent the blank into the start or end when creating space ( [#517](https://github.com/arawa/workspace/pull/517) )

## [1.2.0] - 2022-05-04

### Added

- Searching users by email, name or uid ( [#468](https://github.com/arawa/workspace/pull/468) )

### Fixed

- Translating error message creating space ( [#421](https://github.com/arawa/workspace/pull/423) )
- Rounding of the qyota on the front-end ( [#453](https://github.com/arawa/workspace/pull/453) )
- A user who is a GE can create groups ( [#473](https://github.com/arawa/workspace/pull/473) )
- Move the creating space block above the spaces list ( [#424](https://github.com/arawa/workspace/pull/424) )
- Runing the workspace with php8 ( [#439](https://github.com/arawa/workspace/pull/439) )
- Print the right quota ( [#480](https://github.com/arawa/workspace/pull/480) )


## [1.1.0] - 2022-04-01

### Added

- Import groupfolders and convert them into spaces ( [#380](https://github.com/arawa/workspace/pull/380) )

## [1.0.0] - 2022-02-16

- Create, Read, Update, Delete a Workspace
- Manage users and add them to workspaces
- Define users' roles (GeneralManager and WorkspacesManager)


[Unreleased]: https://github.com/arawa/workspace/compare/v1.2.2...main
[1.2.2]: https://github.com/arawa/workspace/compare/v1.2.1...1.2.2
[1.2.1]: https://github.com/arawa/workspace/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/arawa/workspace/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/arawa/workspace/compare/v1.0.1...v1.1.0
[1.0.0]: https://github.com/arawa/workspace/releases/tag/v1.0.0