# CSMS Git Cheatsheet

## Check Repository Status

```bash
git status
```

## Show Current Branch

```bash
git branch --show-current
```

## View Recent Commits

```bash
git log --oneline -5
```

## Switch to Main

```bash
git switch main
```

## Update Main

```bash
git pull origin main
```

## Create a Feature Branch

```bash
git switch -c feature/<ticket>-<short-description>
```

Example:

```bash
git switch -c feature/t01-resident-domain-model
```

## View Changes

```bash
git diff
```

## Stage a File

```bash
git add <file>
```

Example:

```bash
git add app/Models/Resident.php
```

## Stage Multiple Specific Files

```bash
git add app/Models/Resident.php tests/Unit/ResidentTest.php
```

## Review Staged Changes

```bash
git diff --staged
```

## Commit

```bash
git commit -m "type: short description"
```

Example:

```bash
git commit -m "feat: define resident domain model"
```

## Push a New Branch

```bash
git push -u origin <branch-name>
```

Example:

```bash
git push -u origin feature/t01-resident-domain-model
```

## Push an Existing Tracked Branch

```bash
git push
```

## View Remote Repository

```bash
git remote -v
```

## Restore an Unstaged File

```bash
git restore <file>
```

Use this carefully. Uncommitted changes in that file will be discarded.

## Unstage a File

```bash
git restore --staged <file>
```

This removes the file from the staging area without deleting the working copy changes.

## Recommended Activity Sequence

1. Check status.

```bash
git status
```

2. Switch to `main`.

```bash
git switch main
```

3. Update `main`.

```bash
git pull origin main
```

4. Create the activity branch.

```bash
git switch -c feature/<ticket>-<description>
```

5. Implement and test the activity.

6. Review changes.

```bash
git status
git diff
```

7. Stage only the required files.

```bash
git add <files>
```

8. Review staged changes.

```bash
git diff --staged
```

9. Commit.

```bash
git commit -m "<required commit message>"
```

10. Push.

```bash
git push -u origin <branch-name>
```

## Important Rules

- Do not perform activity development directly on `main`.
- Do not commit `.env`.
- Do not commit `vendor/`.
- Do not commit unrelated files.
- Do not use another student's repository.
- Do not rewrite Git history unless specifically instructed.
- Do not force push unless specifically instructed.
- Always inspect `git status` before committing.