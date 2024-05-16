# Contributing to Workspace

First off, thanks for taking the time to contribute! ❤️

All types of contributions are encouraged and valued. See the [Table of Contents](#table-of-contents) for different ways to help and details about how this project handles them. Please make sure to read the relevant section before making your contribution. It will make it a lot easier for us maintainers and smooth out the experience for all involved. The community looks forward to your contributions. 🎉

> And if you like the project, but just don't have time to contribute, that's fine. There are other easy ways to support the project and show your appreciation, which we would also be very happy about:
>
> - Star the project
> - Tweet about it
> - Refer this project in your project's readme
> - Mention the project at local meetups and tell your friends/colleagues


## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [I Have a Question](#i-have-a-question)
- [I Want To Contribute](#i-want-to-contribute) 
  - [Reporting Bugs](#reporting-bugs)
  - [Suggesting Enhancements](#suggesting-enhancements)
  - [Your First Code Contribution](#your-first-code-contribution)
  - [Improving The Documentation](#improving-the-documentation)
- [Styleguides](#styleguides) 
  - [Commit Messages](#commit-messages)
- [Join The Project Team](#join-the-project-team)


## Code of Conduct


This project and everyone participating in it is governed by the
[Workspace Code of Conduct](./docs/dev/CODE_OF_CONDUCT.md).
By participating, you are expected to uphold this code. Please report unacceptable behavior
to [contact@arawa.fr](mailto:contact@arawa.fr).


## I Have a Question


> If you want to ask a question, we assume that you have read the available [General Managers documentation](./docs/user-guide/from-3.X.X/Documentation%20Workspace%20pour%20Gestionnaires%20Généraux%20-%20v3.0.pdf) and [Space Managers documentation](./docs/user-guide/from-3.X.X/Documentation%20Workspace%20pour%20Gestionnaires%20d'Espaces%20-%20v3.0.pdf).

Before you ask a question, it is best to search for existing [issues](https://github.com/arawa/workspace/issues) that might help you. In case you have found a suitable issue and still need clarification, you can write your question in this issue. It is also advisable to search the internet for answers first.

If you then still feel the need to ask a question and need clarification, we recommend the following:

- Open an [issue](https://github.com/arawa/workspace/issues/new).
- Provide as much context as you can about what you're running into.
- Provide project and platform versions (nodejs, npm, etc), depending on what seems relevant.

We will then take care of the issue as soon as possible.

## I Want To Contribute

> ### Legal Notice <!-- omit in toc -->
>
> When contributing to this project, you must agree that you have authored 100% of the content, that you have the necessary rights to the content and that the content you contribute may be provided under the project license.


### Reporting Bugs


#### Before Submitting a Bug Report

A good bug report shouldn't leave others needing to chase you up for more information. Therefore, we ask you to investigate carefully, collect information and describe the issue in detail in your report. Please complete the following steps in advance to help us fix any potential bug as fast asBefore Submitting a Bug Report possible.

- Make sure that you are using the latest version.
- Determine if your bug is really a bug and not an error on your side e.g. using incompatible environment components/versions (Make sure that you have read the [General Managers documentation](./docs/user-guide/from-3.X.X/Documentation%20Workspace%20pour%20Gestionnaires%20Généraux%20-%20v3.0.pdf) and [Space Managers documentation](./docs/user-guide/from-3.X.X/Documentation%20Workspace%20pour%20Gestionnaires%20d'Espaces%20-%20v3.0.pdf)) , [install and configuration documentations](./README.md#limit-the-workspace-app-to-specific-groups). If you are looking for support, you might want to check [this section](#i-have-a-question)).
- To see if other users have experienced (and potentially already solved) the same issue you are having, check if there is not already a bug report existing for your bug or error in these bug trackers : 
  - [major bug](https://github.com/arawa/workspace/issues?q=is%3Aopen+is%3Aissue+label%3A%22major+bug%22)
  - [important bug](https://github.com/arawa/workspace/issues?q=is%3Aopen+is%3Aissue+label%3A%22important+bug%22)
  - [minor bug](https://github.com/arawa/workspace/issues?q=is%3Aopen+is%3Aissue+label%3A%22minor+bug%22)
- Also make sure to search the internet (including Stack Overflow) to see if users outside of the GitHub community have discussed the issue.
- Collect information about the bug: 
  - Stack trace (Traceback)
  - OS, Platform and Version (Windows, Linux, macOS, x86, ARM)
  - Version of the interpreter, compiler, SDK, runtime environment, package manager, depending on what seems relevant.
  - Possibly your input and the output
  - Can you reliably reproduce the issue? And can you also reproduce it with older versions?


#### How Do I Submit a Good Bug Report?


> You must never report security related issues, vulnerabilities or bugs including sensitive information to the issue tracker, or elsewhere in public. Instead sensitive bugs must be sent by email to [security@arawa.fr](mailto:security@arawa.fr).
> <!-- You may add a PGP key to allow the messages to be sent encrypted as well. -->

We use GitHub issues to track bugs and errors. If you run into an issue with the project:

- Open an [issue](https://github.com/arawa/workspace/issues/new). (Since we can't be sure at this point whether it is a bug or not, we ask you not to talk about a bug yet and not to label the issue.)
- Explain the behavior you would expect and the actual behavior.
- Please provide as much context as possible and describe the *reproduction steps* that someone else can follow to recreate the issue on their own. This usually includes your code. For good bug reports you should isolate the problem and create a reduced test case.
- Provide the information you collected in the previous section.

Once it's filed:

- The project team will label the issue accordingly.
- A team member will try to reproduce the issue with your provided steps. If there are no reproduction steps or no obvious way to reproduce the issue, the team will ask you for those steps and mark the issue as `needs-repro`. Bugs with the `needs-repro` tag will not be addressed until they are reproduced.
- If the team is able to reproduce the issue, it will be marked `needs-fix`, as well as possibly other tags (such as `critical`), and the issue will be left to be [implemented by someone](#your-first-code-contribution).


### Suggesting Enhancements


This section guides you through submitting an enhancement suggestion for Workspace, **including completely new features and minor improvements to existing functionality**. Following these guidelines will help maintainers and the community to understand your suggestion and find related suggestions.


#### Before Submitting an Enhancement


- Make sure that you are using the latest version.
- Read the [General Managers documentation](./docs/user-guide/from-3.X.X/Documentation%20Workspace%20pour%20Gestionnaires%20Généraux%20-%20v3.0.pdf) and [Space Managers documentation](./docs/user-guide/from-3.X.X/Documentation%20Workspace%20pour%20Gestionnaires%20d'Espaces%20-%20v3.0.pdf), [install and configuration documentations](./README.md#limit-the-workspace-app-to-specific-groups) carefully and find out if the functionality is already covered, maybe by an individual configuration.
- Perform a [search](https://github.com/arawa/workspace/issues) to see if the enhancement has already been suggested. If it has, add a comment to the existing issue instead of opening a new one.
- Find out whether your idea fits with the scope and aims of the project. It's up to you to make a strong case to convince the project's developers of the merits of this feature. Keep in mind that we want features that will be useful to the majority of our users and not just a small subset. If you're just targeting a minority of users, consider writing an add-on/plugin library.


#### How Do I Submit a Good Enhancement Suggestion?


Enhancement suggestions are tracked as [GitHub issues](https://github.com/arawa/workspace/issues).

- Use a **clear and descriptive title** for the issue to identify the suggestion.
- Provide a **step-by-step description of the suggested enhancement** in as many details as possible.
- **Describe the current behavior** and **explain which behavior you expected to see instead** and why. At this point you can also tell which alternatives do not work for you.
- You may want to **include screenshots and animated GIFs** which help you demonstrate the steps or point out the part which the suggestion is related to. You can use [this tool](https://www.cockos.com/licecap/) to record GIFs on macOS and Windows, and [this tool](https://github.com/colinkeenan/silentcast) or [this tool](<https://github.com/phw/peek>) on Linux. <!-- this should only be included if the project has a GUI -->
- **Explain why this enhancement would be useful** to most Workspace users. You may also want to point out the other projects that solved it better and which could serve as inspiration.


### Your First Code Contribution


#### Requirements


- npm v10.0.0
- node v20.0.0
- php v8.0
- composer v2.0.13
- make v3.82
- git v1.8


#### Fork the project


First, you must [fork the project](<https://github.com/arawa/workspace/fork>).


#### 📦 Building the app


Clone into your apps directory (example: `/var/www/html/nextcloud/apps/`).

```bash
git clone https://github.com/<your-username>/workspace.git
```

Then, you can build app :

```bash
cd workspace
make
```

You're ready to develop your feature or fix. Then you can create a [Pull Request](<https://github.com/arawa/workspace/compare>).


## Styleguides


### Commit Messages


We are using the [Angular convention](<https://github.com/angular/angular/blob/68a6a07/CONTRIBUTING.md#-commit-message-format>) for the message format.

For example, a feature commit : 

```bash
git commit -m "feat(controller): Add user to the workspace" -m "I added this new feature :" -m "- You can use this API/Rest, `/workspace/spaces/{id}/groups/{gid}/users/{uid}` with the POST HTTP, to add a user to a workspace."
```


## Attribution


This work is licensed under a Creative Commons Attribution-ShareAlike 4.0 International License.
