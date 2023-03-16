# Introduction

Hi. Software security researchers are welcome to help us find vulnerabilities in our Workspace application.

If you find a bug that you think is distruptive or critical.
You can open an Issue / Pull Request on the project repository or send us an email.

# Reporting Procedures

## What should I do to report a low or medium bug ?

This section is related to a low or medium bug where it doesn't have a big impact on the working of the application.

To report us a low or medium bug, you have two options :

1. Issues : For no technical persons, create an issue on the GitHub, please look the "Issue" section.
2. Pull Request : As a web developer if you found a bug you can create a Pull Request and propose your fix.

### Issue

To create an Issue on the GitHub, please click-on this link and fill the fields : https://github.com/arawa/workspace/issues/new .

**Note** : You can use the Markdown syntax : https://docs.github.com/fr/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax .

To fill the comment section, you need to follow the structure below :

```
# Where

Specify the page and its url where you found the bug.

# When

What actions should be done to reproduce an issue ?

# Comments (optional)

Leave your comments related to the issue.

# Result

Input the description of the result of the issue you found.
You can write how to reproduce the bug and illustrate it with screenshots.
```

For example :

```
# Where

From the home page of Workspace.

# When

I inputed "Space/01" from the Create Space field.
Please, look up my screenshot below.

# Comments

I get this issue as a Workspace Manager.

# Result

I created the "Space/01" Workspace. But, when I am going to the groupfolder "Space/01", I cannot open it.
```

### Pull Request

As a web developer, please, follow these steps to submit us a fixture :

1. Create a GitHub account
2. Fork the Workspace project : https://github.com/arawa/workspace/fork
3. Please, create a branch type fix : fix/<branch-name>/<issue>
    a. For example : `git checkout -b fix/remove-special-char/42`.
4. Code your fixture
5. Submit your Pull Request
    a. If you are a junior developer, please look the official documentation : https://docs.github.com/fr/pull-requests/collaborating-with-pull-requests/proposing-changes-to-your-work-with-pull-requests/creating-a-pull-request .
6. For the body of your Pull Request use the following template: 

```
# Where

Specify the page and its url where you found the bug.

# When

What actions should be done to reproduce an issue ?

# Comments (optional)

Leave your comments related to the issue.

# Result

The result of the bug you have found with a screenshots or GIF.

# Solutions (optional)

Input the description of the result of the issue you found.
You can write how to reproduce the bug and illustrate it with screenshots.
```

Example of a case :

```
# Where

From the home page of Workspace.

# When

I inputed "Space/01" from the Create Space field.
Please, look up my screenshot below.

# Comments

I get this issue as a Workspace Manager.

# Result

I created the "Space/01" Workspace. But, when I am going to the groupfolder "Space/01", I cannot open it.

# Solutions

Please, look at the "Files changed" tab of my Pull Request.
But, in short, I created a function that parses all special characters before creating a workspace.
```

7. You will be notified when we check your solution.

## What should I do to report a Hight or Critical bug ?

The hight and critical bugs are sensibles and using them hackers can get access and modify data, exploit or damage an application and so on.

We ask that you do not share or publicize an unresolved vulnerability with others. Instead of creating an Issue or a Pull Request, please send a description of the issue to email.

### Vulnerability threat levels

| Level | Description |
|:---|:---|
|Critical | Example : "0-day" attacks or the application is compromised |
| Hight | Example : SQLi, CSRF, XSS, and so on.|

### Send us an email

So please, it's very important to send us an email to security@arawa.fr following the template below for the message body.

```
Title: The title of the issue.
Where: The file(s) involved.
When: The action to exploit the security breach.
Comments (optional) : Description of the issue.
Result: The final result of the security breach.
Solutions (optional) : If you can propose a solution. Please, don't hesitate to share us the solution or a food for thought in the message body or from attachments.
```

Example :

```
Title: I found a SQL Injection in a php code
Where: From the SpaceMapper.php file
When: I inputed ";drop database nextcloud;" (please, look up the screenchot in the attachments).
Result: Delete the database.
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
