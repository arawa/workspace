# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### stableX.X

## Added

- Close menu on group rename error message ([#839](https://github.com/arawa/workspace/pull/839))
- Add an arrow from the create group ([#900](https://github.com/arawa/workspace/pull/900))
- Create occ command to import and create workspace ( [#902](https://github.com/arawa/workspace/pull/902) )

## Fixed

- Fix/move v300 constant/stable3.0 ( [#845](https://github.com/arawa/workspace/pull/845) )
- Repair the migration v3.0.0 and v3.0.1 ( [#843](https://github.com/arawa/workspace/pull/843/files) )
- fix/delete-a-user-when-adding-him-to-another-group/901 ( [#907](https://github.com/arawa/workspace/pull/907) )
- Check if an user is present in the WorkspacesManagers group before remove ( [#915](https://github.com/arawa/workspace/pull/915) )
- Rename a workspace name from plural to singular or inversely ( [#893](https://github.com/arawa/workspace/pull/893) )
- Display the highlight for new members from a workspace ([#888](https://github.com/arawa/workspace/pull/888))
- Take into account the limitation of searches between members of the same group, except for specefic groups ( [#802](https://github.com/arawa/workspace/pull/802) )
- Disable users import ( [#995](https://github.com/arawa/workspace/pull/995) )

## [3.2.0] - 2024-06-04

### Added

- Update the changelog for stable3.2 ( [#1006](https://github.com/arawa/workspace/pull/1006) )
- Update to 3.2.0 and compatible with NC29 ( [#1005](https://github.com/arawa/workspace/pull/1005) )
- Create occ command to import and create workspace (created multiple workspaces, assign 1 Workspace Manager to each by email or ID, set a quota) and optimization of workspaces listing - backport version ( #1003 ) - Feature sponsored by the CNRS
- Errors in the namespace for a few Exceptions ( [#1002](https://github.com/arawa/workspace/pull/1002) )

## [3.1.0] - 2024-05-20

### Fixed

* Preventing accidental deletion of the SPACE-GE ( [981](https://github.com/arawa/workspace/pull/981), [982](https://github.com/arawa/workspace/pull/982), [983](https://github.com/arawa/workspace/pull/983), [985](https://github.com/arawa/workspace/pull/985), [987](https://github.com/arawa/workspace/pull/987) )
* New Workspace 3.1.0 release [988](https://github.com/arawa/workspace/pull/988)

## [3.0.6] - 2024-04-4

### Fixed

- Reapply the highlight for new members when they are added to a workspace ( [#948](https://github.com/arawa/workspace/pull/948) )
- Fixed of a bad renaming for a workspace (ex: rename a workspace from plural to single or inversely ) ( [#949](https://github.com/arawa/workspace/pull/949) )
- Add an arrow in the create group field ( [#950](https://github.com/arawa/workspace/pull/950) )
- Prevent a user from being removed from a workspace if he/she is a space manager for another workspace ( [#951](https://github.com/arawa/workspace/pull/951) )
- Take into account the limitation of searches between members of the same group, except for specific groups ( [#953](https://github.com/arawa/workspace/pull/953) )

## [3.0.5] - 2024-03-04

## Changed

- Workspace release 3.0.5 and making it available for nc28 ( [#919](https://github.com/arawa/workspace/pull/919) )

## [3.0.4] - 2023-12-11

### Fixed

- Backport/fix delete a user when adding him to another group/907 ( [#908](https://github.com/arawa/workspace/pull/908) )

### Changed

- Backport/inform users refresh browser/911/stable3.0 ( [#912](https://github.com/arawa/workspace/pull/912) )

## [3.0.3] - 2023-07-20

- Compatible with Nextcloud 27 ( [#884](https://github.com/arawa/workspace/pull/884) )

## [3.0.2] - 2023-06-26

### Fixed

- Backport/update to 3.0.0/stable3.0 ( [#868] https://github.com/arawa/workspace/pull/868 )
- Fix/remove space issue/stable3.0 ( [#840](https://github.com/arawa/workspace/pull/840))
- Hotfix/ingore guest virtual group upgrade300/stable3.0 # ( [#848](https://github.com/arawa/workspace/pull/848) )
- Backport/ignore virtual groups/stable3.0 ( [#867](https://github.com/arawa/workspace/pull/867) )
- Backport/rename workspace for 3.x.x/stable3.0 ( [#866](https://github.com/arawa/workspace/pull/866) )

# [3.0.1] - 2023-05-26

### Fixed

-  Hotfix/update change groupsname/834/stable3.0 ( [#835](https://github.com/arawa/workspace/pull/835) )
- Fix workspace removal issue ( [#838](https://github.com/arawa/workspace/pull/838))
- fix(php): Move the V300 constant ( [#841](https://github.com/arawa/workspace/pull/841) )
- Ignore guest virtual group ( [#847](https://github.com/arawa/workspace/pull/847) )
- Get SPACE-GE and SPACE-U groups only ( [#858](https://github.com/arawa/workspace/pull/858) )
- fix(Vue,Php): Rename the groups with the spacename ( [#860](https://github.com/arawa/workspace/pull/860) )

## [3.0.0] - 2023-05-25

### Added

- npm: Bump @nextcloud/vue from 5.4.0 to 7.4.0 ( [#666](https://github.com/arawa/workspace/pull/666) )
- NcModal component to delete a workspace ( [#774](https://github.com/arawa/workspace/pull/774) )
- Experiment/change user group ( [#663](https://github.com/arawa/workspace/pull/663) )

### Changed

- Create the register function ( [#649](https://github.com/arawa/workspace/pull/649) )
- Fix the pattern to check special char ( [#648](https://github.com/arawa/workspace/pull/648) )
- Replace constants ( [#589](https://github.com/arawa/workspace/pull/589))
- replace vue-notification by @nextcloud/dialogs ( [#798](https://github.com/arawa/workspace/pull/798) )
- Remove the space id in the title ( [#794](https://github.com/arawa/workspace/pull/794) )
- replace vue-notification by @nextcloud/dialogs ( [#798](https://github.com/arawa/workspace/pull/798) )
- Change the translation in workspace ( [#808](https://github.com/arawa/workspace/pull/808) )
- Add 5s to notifications in a few places ( [#814](https://github.com/arawa/workspace/pull/814) )
- Refactor Home.vue component ( [#817](https://github.com/arawa/workspace/pull/817) )
- Refactor to be compatible with php8.0 ( [#818](https://github.com/arawa/workspace/pull/818) )

### Fixed

- Bugfix npm test ( [#637](https://github.com/arawa/workspace/pull/637) )
- Re-add the translating of the "admin" ( [#662](https://github.com/arawa/workspace/pull/662) )
- Filter the groupfolderId ( [#661](https://github.com/arawa/workspace/pull/661) )
- Change the index from Nextcloud 25 ( [#681](https://github.com/arawa/workspace/pull/681) )
- Prevent duplicate spacename rename ( [#781](https://github.com/arawa/workspace/pull/781) )
- Prevent the duplication of group ( [#782](https://github.com/arawa/workspace/pull/782) )
- Replace the plus by a dot ( [#784](https://github.com/arawa/workspace/pull/784) )
- Fix/rename group ( [#799](https://github.com/arawa/workspace/pull/799) )
- Fix/modal window hide ( [#801](https://github.com/arawa/workspace/pull/801) )
- Define a parameter for the spacename var ( [#737](https://github.com/arawa/workspace/pull/737) )

## [2.0.1] - 2023-03-03

### Fixed

- Fix/set parameter spacename/737/stable25 ( [#738](https://github.com/arawa/workspace/pull/738) )

### Changed

- chore(): remove @noCSRFRequired tokens from controllers ( [#745](https://github.com/arawa/workspace/pull/745) )

## [2.0.0] - 2023-02-06

### Added

- npm: Bump @nextcloud/vue from 5.4.0 to 7.4.0 ( [#666](https://github.com/arawa/workspace/pull/666) )
- Add coding standard ( [#624](https://github.com/arawa/workspace/pull/624) )
- Init the .editorconfig file ( [#625](https://github.com/arawa/workspace/pull/625) )

### Changed

- Create the register function ( [#649](https://github.com/arawa/workspace/pull/649) )
- Fix the pattern to check special char ( [#648](https://github.com/arawa/workspace/pull/648) )
- Replace constants ( [#589](https://github.com/arawa/workspace/pull/589))
- Backport : Disable the conversion feature - stable25 ( [#704](https://github.com/arawa/workspace/pull/704) )

### Chore

- npm: bump vue and vue-template-compiler ( [#591](https://github.com/arawa/workspace/pull/591) )
- npm: bump @nextcloud/stylelint-config from 2.2.0 to 2.3.0 ( [#595](https://github.com/arawa/workspace/pull/595) )
- npm: bump jest-environment-jsdom from 29.0.3 to 29.3.1 ( [#613](https://github.com/arawa/workspace/pull/613) )
- npm: bump jest from 29.0.3 to 29.3.1 [#613](https://github.com/arawa/workspace/pull/614)
- npm: bump loader-utils from 1.4.0 to 1.4.2 ( [#621](https://github.com/arawa/workspace/pull/621) )
- npm: bump jsdom from 20.0.0 to 20.0.3 ( [#622](https://github.com/arawa/workspace/pull/622) )
- npm: bump decode-uri-component from 0.2.0 to 0.2.2 ( [#628](https://github.com/arawa/workspace/pull/628) )
- Composer: Bump phpunit/phpunit from 9.5.24 to 9.5.27 ( [#634](https://github.com/arawa/workspace/pull/634) )
- npm: bump core-js from 3.25.2 to 3.27.1 ( [#646](https://github.com/arawa/workspace/pull/646) )
- npm: bump @nextcloud/axios from 2.0.0 to 2.3.0 ( [#636](https://github.com/arawa/workspace/pull/636) )
- npm: bump @nextcloud/webpack-vue-config from 5.3.0 to 5.4.0 ( [#652](https://github.com/arawa/workspace/pull/652) )
- chore(): Replace OCP's christophwurst by Nextcloud ( [#654](https://github.com/arawa/workspace/pull/654) )
- chore(): Add informations about the release PHP ( [#655](https://github.com/arawa/workspace/pull/655) )
- Replace ILogger by LoggerInterface ( [#656](https://github.com/arawa/workspace/pull/656) )
- chore(MD): Change the groupfolder release to 9.2.0 ( [#665](https://github.com/arawa/workspace/pull/665) )

### Fixed

- Bugfix npm test ( [#637](https://github.com/arawa/workspace/pull/637) )
- Re-add the translating of the "admin" ( [#662](https://github.com/arawa/workspace/pull/662) )
- Filter the groupfolderId ( [#661](https://github.com/arawa/workspace/pull/661) )
- fix(Vue): Change the index from Nextcloud 25 ( [#683](https://github.com/arawa/workspace/pull/683) )

## [1.3.1] - 2022-12-29

### Fixed

- Fix the deletion of a group after groupfolder converting to workspace ( [#644](https://github.com/arawa/workspace/pull/644) )

## [1.3.0] - 2022-12-23

### Added

- Add Czech localization([#541](https://github.com/arawa/workspace/pull/541) & [#542](https://github.com/arawa/workspace/pull/542) )
- Limiting user search ( [#631](https://github.com/arawa/workspace/pull/631) )

### Chore

- bump @nextcloud/l10n from 1.4.1 to 1.6.0 ( [#494](https://github.com/arawa/workspace/pull/494) )
- bump jsdom from 19.0.0 to 20.0.0 #514 ( [#514](https://github.com/arawa/workspace/pull/514) )
- npm: bump prettier from 2.6.2 to 2.7.1 ( [#513](https://github.com/arawa/workspace/pull/513) )
- bump vue and vue-template-compiler ( [#524](https://github.com/arawa/workspace/pull/524) )
- bump jest-environment-jsdom from 28.1.0 to 29.0.3 ( [#562](https://github.com/arawa/workspace/pull/562) )
- bump jest from 28.1.0 to 29.0.3 ( [#563](https://github.com/arawa/workspace/pull/563) )
- bump babel-jest from 28.1.0 to 29.0.3 ( [#564](https://github.com/arawa/workspace/pull/564) )
- bump @nextcloud/axios from 1.10.0 to 2.0.0 ( [#565](https://github.com/arawa/workspace/pull/565) )

### Changed

- Refactoring the creation workspace part ( [#609](https://github.com/arawa/workspace/pull/609) )
- Refactorised the converting groupfolders to workspace ( [#633](https://github.com/arawa/workspace/pull/633) )

### Style 

- Change the settings button ( [#504](https://github.com/arawa/workspace/pull/504) )

## [1.2.3] - 2022-10-28

### Changed

- Comment the convert feature ( [#604](https://github.com/arawa/workspace/pull/604) )

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


[Unreleased]: https://github.com/arawa/workspace/compare/v3.2.0...main
[3.2.0]: https://github.com/arawa/workspace/compare/v3.1.0...3.2.0
[3.1.0]: https://github.com/arawa/workspace/compare/v3.0.6...3.1.0
[3.0.6]: https://github.com/arawa/workspace/compare/v3.0.5...3.0.6
[3.0.5]: https://github.com/arawa/workspace/compare/v3.0.4...3.0.5
[3.0.4]: https://github.com/arawa/workspace/compare/v3.0.3...3.0.4
[3.0.3]: https://github.com/arawa/workspace/compare/v3.0.2...3.0.3
[3.0.2]: https://github.com/arawa/workspace/compare/v3.0.1...3.0.2
[3.0.1]: https://github.com/arawa/workspace/compare/v3.0.0...3.0.1
[3.0.0]: https://github.com/arawa/workspace/compare/v2.0.1...3.0.0
[2.0.1]: https://github.com/arawa/workspace/compare/v2.0.0...2.0.1
[2.0.0]: https://github.com/arawa/workspace/compare/v1.3.1...2.0.0
[1.3.1]: https://github.com/arawa/workspace/compare/v1.3.0...1.3.1
[1.3.0]: https://github.com/arawa/workspace/compare/v1.2.3...1.3.0
[1.2.3]: https://github.com/arawa/workspace/compare/v1.2.2...1.2.3
[1.2.2]: https://github.com/arawa/workspace/compare/v1.2.1...1.2.2
[1.2.1]: https://github.com/arawa/workspace/compare/v1.2.0...v1.2.1
[1.2.0]: https://github.com/arawa/workspace/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/arawa/workspace/compare/v1.0.1...v1.1.0
[1.0.0]: https://github.com/arawa/workspace/releases/tag/v1.0.0
