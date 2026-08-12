# AI Coding Rules — Create Only, Do Not Modify Existing Code

## Core Rule

Before generating or changing any code, always inspect and follow these rules.

### 1. Do Not Edit Existing Code

* Do NOT modify existing files unless I explicitly ask you to edit them.
* Do NOT refactor existing code automatically.
* Do NOT rename existing classes, methods, variables, routes, tables, columns, folders, or files.
* Do NOT remove existing code.
* Do NOT replace working code with a different implementation.
* Do NOT change formatting in unrelated code.
* Do NOT change existing business logic unless explicitly requested.

### 2. Create Only What I Request

If I ask you to create something:

* Create only the requested file, class, method, migration, controller, model, service, middleware, route, or configuration.
* Do not change unrelated files.
* Do not add extra features unless they are required for the requested code to work.
* Do not create duplicate functionality that already exists.

### 3. Check Existing Project Structure First

Before creating code:

1. Check whether the file already exists.
2. Check whether the class already exists.
3. Check whether the method already exists.
4. Check existing models and relationships.
5. Check existing database tables and columns.
6. Check existing routes.
7. Check existing middleware.
8. Check existing services and helpers.
9. Follow the project's current naming conventions and folder structure.

If something already exists, reuse it instead of creating a duplicate.

### 4. Never Guess Existing Structure

Do not assume that a table, column, method, route, model, relationship, environment variable, package, or configuration exists.

Verify the existing project first.

If verification is impossible, clearly mark the assumption instead of silently changing the project.

### 5. Protect Existing Database Schema

Do NOT:

* rename columns,
* delete columns,
* delete tables,
* change column types,
* change foreign keys,
* change indexes,
* modify existing migrations,

unless I explicitly request it.

For a new database change, create a new migration instead of editing an old migration when the old migration may already have been executed.

### 6. Protect Existing APIs

Do not change existing:

* endpoint URLs,
* HTTP methods,
* request fields,
* response structures,
* status codes,
* authentication behavior,
* permission names,

unless I specifically request the change.

Backward compatibility should be preserved by default.

### 7. Follow Existing Architecture

Use the architecture already used by the project.

For example, if the project uses:

* Controllers → Services → Repositories,
* Form Requests,
* API Resources,
* Policies,
* Middleware,
* Events,
* Jobs,
* DTOs,

continue using that architecture.

Do not introduce a new architecture without permission.

### 8. No Unrequested Refactoring

While working on one feature, you may notice other code that could be improved.

Do NOT automatically fix it.

Instead, mention:

> I found another possible improvement, but I did not modify it because it is outside the requested task.

### 9. Keep Changes Small

Use the smallest possible change necessary to complete the task.

Prefer:

```text
Requested feature
    ↓
Required new code
    ↓
Minimal integration
```

Avoid:

```text
Requested feature
    ↓
Refactor project
    ↓
Rename classes
    ↓
Change database
    ↓
Change unrelated APIs
```

### 10. Check Before Producing Code

Before providing code, internally verify:

```text
[ ] Did I understand the requested task?
[ ] Did I check whether this functionality already exists?
[ ] Am I modifying an existing file?
[ ] Did the user explicitly allow that modification?
[ ] Am I changing unrelated code?
[ ] Am I introducing duplicate logic?
[ ] Am I changing an existing API?
[ ] Am I changing the database unnecessarily?
[ ] Does the code follow the current project architecture?
[ ] Is this the minimum required change?
```

### 11. When Existing Code Must Be Changed

If the requested feature requires an existing file to be changed, do not rewrite the whole file unnecessarily.

Show only the required addition/change and clearly identify:

```text
File:
app/Http/Controllers/AuthController.php

Change:
Add the following method only.

Do not modify other methods.
```

### 12. Preserve User Code

User-written code has priority.

Existing code should be treated as intentional unless the user explicitly asks:

* fix this,
* refactor this,
* update this,
* replace this,
* remove this,
* modify this.

### 13. Do Not Invent Dependencies

Do not install or recommend a new package when the project already has a suitable solution.

Before adding a dependency:

* check existing packages,
* check existing framework features,
* prefer the current project stack.

### 14. Security

Never weaken existing:

* authentication,
* authorization,
* permissions,
* JWT validation,
* session validation,
* password hashing,
* CSRF protection,
* input validation,
* rate limiting,

just to make code easier to implement.

### 15. Final Response Format

When creating code, explain:

```text
Created:
- What was added

Modified:
- Nothing
```

If modifications are required:

```text
Created:
- New files

Modified:
- Exact existing files changed
- Exact reason each file needed modification

Not Modified:
- Unrelated existing code
```

## Most Important Rule

**If I ask you to CREATE something, CREATE it without modifying existing code unless modification is absolutely required or I explicitly ask you to modify it.**

When uncertain, preserve existing code.
