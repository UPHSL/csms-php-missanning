# CSMS Development and Release Workflow

## Purpose

This document describes how completed CSMS development work moves from an assigned activity to a reviewed repository state.

For this course, a release represents a verified project state produced through incremental development.

## Development Flow

The standard workflow is:

1. Begin from an updated `main` branch.
2. Create the required feature branch.
3. Implement the assigned requirement.
4. Add or update automated tests.
5. Run the relevant tests.
6. Run the complete test suite.
7. Format and review the code.
8. Commit the completed change.
9. Push the feature branch.
10. Create a Pull Request when required.
11. Complete the required Moodle submission.

## Main Branch

The `main` branch represents the stable integrated state of the student's project.

Activity development should normally occur on feature branches.

Students should avoid making experimental or incomplete changes directly on `main`.

## Feature Branches

Each assigned development ticket should use the branch name specified by the instructor.

Example:

```text
feature/t01-resident-domain-model
```

Feature branches should contain work related to that activity.

Avoid combining unrelated activities in one branch.

## Verification

Before considering a feature complete, run:

```bash
php artisan test
```

Then verify:

```bash
git status
```

When applicable, run Laravel Pint:

```bash
./vendor/bin/pint
```

Review any formatting changes before committing.

## Commit

Use the commit message specified by the activity.

Example:

```bash
git commit -m "feat: define resident domain model"
```

A commit should represent a meaningful development change.

## Push

Push the feature branch to GitHub:

```bash
git push -u origin <branch-name>
```

Verify that the branch and commit are visible in the correct GitHub repository.

## Pull Request

When the activity requires a Pull Request:

1. Open the student's repository on GitHub.
2. Select the completed feature branch.
3. Create a Pull Request targeting `main`.
4. Provide a concise description of the implemented requirement.
5. Verify that the intended files are included.
6. Verify that no unrelated files are included.
7. Submit the Pull Request.

Do not merge the Pull Request if the activity or instructor requires it to remain open for review.

## Definition of Ready for Submission

A development activity is ready for submission when:

- the required functionality has been implemented
- required automated tests exist
- relevant tests pass
- the complete test suite passes
- formatting has been reviewed
- only intended files were changed
- the required commit exists
- the feature branch has been pushed
- the repository is accessible on GitHub
- the required Pull Request has been created when applicable
- Moodle submission requirements have been completed

## Versioning

Project versions may be introduced or updated by the instructor as the semester progresses.

Students should not independently change the application version merely because an activity has been completed unless instructed to do so.

## Principle

A feature is not complete simply because the code was written.

Completion includes implementation, testing, review, version control, and the required submission workflow.