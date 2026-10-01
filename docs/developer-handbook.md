# CSMS PHP Developer Handbook

## Purpose

This handbook defines the standard development practices for the PHP implementation of CSMS.

Students should follow these practices throughout the semester unless an activity provides more specific instructions.

## Before Starting an Activity

Begin from the project directory.

Check your current repository state:

```bash
git status
git branch --show-current
```

Your previous work should be committed before starting another activity.

## Update Main

Switch to `main`:

```bash
git switch main
```

Update it:

```bash
git pull origin main
```

Do not begin a new activity from an unfinished feature branch.

## Create the Required Feature Branch

Use the branch name specified by the activity.

Example:

```bash
git switch -c feature/t01-resident-domain-model
```

Verify:

```bash
git branch --show-current
```

## Understand Before Editing

Before modifying code:

1. Read the complete Moodle activity.
2. Identify the required files.
3. Identify the expected behavior.
4. Identify the required automated tests.
5. Review the existing project structure.
6. Implement only the assigned scope.

Do not begin by randomly modifying files until tests pass.

## Development Cycle

A recommended development cycle is:

1. Make a small change.
2. Save the file.
3. Run the relevant test.
4. Read any error carefully.
5. Correct the implementation.
6. Run the test again.
7. Continue until the requirement is complete.

## Testing

Run a specific test when appropriate.

Example:

```bash
php artisan test --filter=ResidentTest
```

Before completing the activity, run the complete suite:

```bash
php artisan test
```

Existing tests should continue to pass.

A new feature should not unnecessarily break previously completed functionality.

## Code Formatting

Format PHP code using Laravel Pint:

macOS or Linux:

```bash
./vendor/bin/pint
```

Windows PowerShell:

```powershell
.\vendor\bin\pint
```

After formatting, review the changed files:

```bash
git status
git diff
```

Formatting tools may modify files beyond the one currently being edited.

Do not commit unrelated changes without reviewing them.

## Review Your Work

Before staging files:

```bash
git status
git diff
```

Confirm that:

- only expected files changed
- no temporary files were added
- `.env` was not added
- `vendor/` was not added
- database files were not accidentally added
- unrelated starter files were not modified

## Stage Changes

Prefer explicitly staging the files that belong to the activity.

Example:

```bash
git add app/Models/Resident.php
git add tests/Unit/ResidentTest.php
```

Then review:

```bash
git status
git diff --staged
```

## Commit

Use the commit message required by the activity.

Example:

```bash
git commit -m "feat: define resident domain model"
```

Do not use vague messages such as:

```text
update
changes
activity
done
final
fix
```

The commit message should describe the change.

## Push

Push the feature branch:

```bash
git push -u origin feature/t01-resident-domain-model
```

For later pushes on the same tracked branch:

```bash
git push
```

## Pull Requests

When required, create a Pull Request from your feature branch into `main`.

Before creating the Pull Request, verify:

```bash
git status
php artisan test
```

The working tree should be clean and all required tests should pass.

## Debugging

When an error occurs, read the error message before changing code.

Determine:

1. Which command failed?
2. Which file is mentioned?
3. Which line is mentioned?
4. What exception or error type occurred?
5. Is the problem related to code, configuration, dependencies, database setup, or tests?

Avoid making unrelated changes while troubleshooting one problem.

## Common Setup Problems

### `vendor/autoload.php` is missing

Run:

```bash
composer install
```

### Application encryption key is missing

Ensure `.env` exists, then run:

```bash
php artisan key:generate
php artisan config:clear
```

### SQLite database file does not exist

Create:

```text
database/database.sqlite
```

Then run:

```bash
php artisan migrate
```

### Tests fail after formatting

Inspect:

```bash
git diff
```

Formatting tools may have modified additional files.

Restore unrelated changes when appropriate.

## Files That Should Not Be Committed

Do not intentionally commit local environment or generated dependency files such as:

```text
.env
vendor/
node_modules/
```

Follow the repository's `.gitignore`.

## Student Responsibility

Students must understand their implementation.

You may be asked to explain:

- a class
- a method
- a test
- a Git command
- a commit
- a branch
- a design decision
- an error you encountered
- how you verified your solution

Successful execution alone is not sufficient if the submitted work cannot be explained.
