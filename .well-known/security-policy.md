# Introduction
Welcome dear users and contributors

Software security researchers are welcome to help us find vulnerabilities in our Workspace application.

# Reporting Procedures
## Determine wether you want to report a bug or a vulnerability
First, you need to determine the nature of the reported problem, so you know which procedure you should follow.

A bug is a functional or technical problem that affects the performance or usability of the application. 
Some bugs may turn out to be vulnerabilities that can lead to security breaches. A security breach is a security vulnerability that can be exploited to access sensitive information or harm the application. Vulnerabilities allow hackers to access and modify data, exploit or damage an application, etc.

* a bug can be reported through an issue or corrected through a pull request
* **a vulnerability must be reported by email only**. **Making a vulnerability public increases the risks of the application beeing attacked**

## What should I do to report a vulnerability?

This section is related to vulnerabilities, which refers to bugs that can be exploited to access sensitive information or harm the application. Vulnerabilities allow hackers to access and modify data, exploit or damage an application, etc.

We ask that you do not share or publicize an unresolved vulnerability with others. Making a vulnerability public increases the risks of attack on an application. Instead of creating an Issue or a Pull Request, please send a description of the issue by email.

### Vulnerability threat levels

| Level | Description |
|:---|:---|
|Critical | Example : "0-day" attacks or the application is compromised |
| High | Example : SQLi, CSRF, XSS, and so on.|

### Send us an email

It's crucial to send us an email to security@arawa.fr following the template below for the message body. Making a vulnerability public (with an issue for example) increases the risks of the application beeing attacked.

```
Title: write a title for this issue.
Where: specify the file(s) involved.
When: describe the actions that led to the discovery of this vulnerability.
Comments (optional) : add details related to the issue if necessary.
Result: describe the final result of the security breach.
Solutions (optional) : if you can suggest a solution or a food for thought, please share it with us in the message body or attachments.
```

Example :

```
Title: I found a SQL Injection in a php code
Where: From the SpaceMapper.php file
When: I inserted ";drop database nextcloud;" in the "Create a new Workspace" field on the Workspace homepage (please, look up the screenchot in the attachments).
Result: The database got deleted.
Solutions : I created a function to parse the SQL injection below.

<?php
class SpaceMapper {
    function create (string $spacename, string $groupfolder_id) {
        $insertSpace = $mysqlClient->prepare('INSERT INTO oc_work_spaces(spacename, groupfolder_id) VALUES (:spacename, :groupfolderid);');
    $insertSpace->execute([
        'spacename' => $spacename,
        'groupfolderid' => $groupfolder_id
    ]);
    }
}

```


## What should I do to report a bug ?

This section is related to bugs, which refers to functional or technical problems that affect the performance of the application.

To report a bug, you have two options :

1. Issues : for people with no technical knowledge, please consult the "Issue" section of this document and create an issue on GitHub.
2. Pull Request : as developer, please consult the "Pull Request" section of this document and create a Pull Request to propose your fix.

### Issue

To create an Issue on GitHub, please click on this link and fill the fields : https://github.com/arawa/workspace/issues/new .

**Note** : You can use the Markdown syntax : https://docs.github.com/fr/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax .

To fill the comment section, you need to follow the structure below :

```
# Where

Specify the page where you found the bug and its url.

# When

Describe the actions taken to reproduce the bug and illustrate it with screenshots or GIF.

# Result

Describe in detail the result obtained by performing the actions that led to the bug.

# Expected behavior

Describe the original expected behavior (if the bug didn't happen).

# Comments (optional)

Leave your comments related to the issue.
```

For example :

```
# Where

From the home page of Workspace (https://nc.my-instance.fr/apps/workspace/)

# When

I typed "Space/01" in the `Create Space` field.
Please, look up my screenshot below.

# Result

I created the "Space/01" Workspace. But, when I went to the Workspace "Space/01", I couldn't open it.

# Expected behavior

I should have a Workspace named "Space/01" and be able to manage it (see its users and perform actions on this Workpace).

# Comments

I get this issue as a Workspace Manager.
```

### Pull Request

As a web developer, please, follow these steps to submit a fixture :

1. Create a GitHub account
2. Fork the Workspace project : https://github.com/arawa/workspace/fork
3. Create a branch type fix : `fix/<branch-name>/<issue>`. For example : `git checkout -b fix/remove-special-char/42`.
4. Code your fixture
5. Submit your Pull Request. *If you are a junior developer, please look at the official documentation : https://docs.github.com/fr/pull-requests/collaborating-with-pull-requests/proposing-changes-to-your-work-with-pull-requests/creating-a-pull-request.*
6. Use the template below to fill your Pull Request.
7. You will be notified once we review your solution.

#### Pull Request template
```
# Where

Specify the page where you found the bug and its url.

# When

What actions should be done to reproduce this issue?
    
# Result

Describe the actions taken to reproduce the bug and illustrate it with screenshots or GIF.

# Comments (optional)

Leave your comments related to the issue.

# Solutions (optional)

Explain your solution.
```

Example of a case :

```
# Where

From the home page of Workspace.

# When

I inserted "Space/01" in the "Create Space" field.
Please, look up my screenshot below.

# Result

I created the "Space/01" Workspace. But, when I am going to the Workspace "Space/01", I cannot open it.
    
# Comments

I get this issue as a Workspace Manager.

# Solutions

Please, look at the "Files changed" tab of my Pull Request.
In short, I created a function that parses all special characters before creating a workspace.
```
