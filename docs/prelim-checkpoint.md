# Preliminary Examination Checkpoint

## Developer Information

Name: Jullie Anne A. Temporosa
GitHub Username: missanning
Primary Technology Stack: PHP with Laravel
T03 Branch: feature/t03-resident-persistence

---

## My T03 Implementation

The SQLite database is stored as a file at `database/database.sqlite` inside the project directory. The class responsible for Resident persistence is `ResidentRepository`, located at `app/Repositories/ResidentRepository.php`. When `save()` is called, it calls Eloquent's built-in `save()` method on the Resident model, which inserts a new record into the `residents` table in SQLite. The Resident ID is assigned automatically by the database after the record is inserted, and Eloquent updates the model instance with the generated ID. `findById()` uses Eloquent's `find()` method to query the `residents` table by primary key and returns the matching Resident model. If no record is found with the given ID, `find()` returns `null`, which is passed back to the caller as a safe not-found result.

---

## My Persistence Design Decision

I decided to include Laravel timestamps (`created_at` and `updated_at`) in the migration. I implemented it this way because Laravel Eloquent automatically manages these columns when saving a model, and including them avoids having to disable timestamps in the model. The alternative I considered was disabling timestamps by setting `public $timestamps = false` in the model and omitting the columns from the migration, but I chose to keep timestamps since they provide useful audit information and follow Laravel conventions.

---

## My Migration Design

Migration file: `database/migrations/2026_08_26_112017_create_residents_table.php`
Primary key design: `$table->id()` - uses Laravel's auto-incrementing unsigned big integer primary key
Contact number column type: `string` - preserves the leading zero and treats the value as text
Status column type: `string` with a default of `Active`
Timestamp decision: Included `$table->timestamps()` to follow Laravel Eloquent conventions

I chose `string` for `contact_number` because storing it as a numeric type would remove the leading zero from values like `09171234567`. Using `string` ensures the value is stored and retrieved exactly as entered. I used `$table->id()` so the database generates the primary key automatically without manual assignment in PHP.

---

## Files I Changed

File: `database/migrations/2026_08_26_112017_create_residents_table.php`
Purpose: Defines the `residents` table structure in SQLite

File: `app/Repositories/ResidentRepository.php`
Purpose: Implements Resident persistence with `save()` and `findById()`

File: `tests/Feature/ResidentRepositoryTest.php`
Purpose: Automated tests for Resident persistence scenarios

File: `docs/prelim-checkpoint.md`
Purpose: Preliminary examination checkpoint documentation

---

## Laravel Persistence Behavior I Can Explain

Behavior selected: Generated IDs

When a Resident is saved using `$resident->save()`, Eloquent inserts the record into the database and the database generates the primary key automatically. Eloquent then updates the model instance's `id` attribute with the generated value. This means that before calling `save()`, `$resident->id` is `null`, and after calling `save()`, it contains the database-assigned integer. My implementation relies on this behavior in `ResidentRepository::save()`, which returns the same Resident instance after saving so the caller can access the newly assigned ID.

---

## Problem I Encountered

Problem or error: The `ResidentValidatorTest` was failing because the test creates residents using `snake_case` keys (`first_name`, `contact_number`) but the Resident model's `$fillable` only contained `camelCase` keys (`firstName`, `contactNumber`), so the values were never stored and validation always failed.
Cause: Mismatch between the field names used in the test and the field names in the model's `$fillable` array.
How I resolved it: Updated `ResidentValidator` to read attributes using `getAttributes()` so it can handle both `camelCase` and `snake_case` keys regardless of how the Resident was created.

---

## My Student-Designed Test

Test name: `test_multiple_residents_can_be_persisted_and_retrieved_independently`
What it verifies: That two different Resident records can be saved and retrieved independently without one overwriting or interfering with the other, and that each receives a unique identifier.
Why I chose this scenario: A persistence layer that only works correctly for a single record could hide bugs where records overwrite each other or share the same ID. This test ensures the repository handles multiple residents correctly.

---

## Tools and References Used

- Laravel Documentation (https://laravel.com/docs)
- Laravel Eloquent ORM documentation for `save()`, `find()`, and `fillable`
- Laravel Migration documentation for column types
- Amazon Q (AI coding assistant) — helped with setting up the repository implementation, test structure, and resolving the camelCase/snake_case mismatch between the validator and model
