# GraphQL Structure & Best Practices Audit

## Summary

The GraphQL layer (Lighthouse, schema, resolvers, errors) has been audited against the [GraphQL spec](https://spec.graphql.org/) and common best practices. Several improvements were applied.

---

## What Was Aligned (Already Correct)

- **Naming**: Types in PascalCase (`User`, `SearchUsersResult`), fields and args in camelCase (`searchUsers`, `phoneVerifiedAt`, `q`).
- **Descriptions**: Types and fields have documentation strings.
- **Nullability**: Non-null used appropriately (`SearchUsersResult!`, `[User!]!`, `id: ID!`); `phoneVerifiedAt` correctly nullable.
- **Operation design**: Single `Query` with one field; `searchUsers(q: String!)` with `@guard` and `@rules(apply: ["min:2", "max:100"])`.
- **Validation**: Min/max aligned with `SearchModuleConstants` (min 2, max 100).
- **Errors**: Auth, authz, validation, and `SearchFailedException` use `extensions.code`; messages are safe for clients.
- **Auth**: `@guard` on `searchUsers`; `AttemptAuthentication` middleware; Sanctum.
- **Scalar**: `DateTime` with Lighthouse scalar.
- **Config**: Schema cache, query cache, `parse_source_location: true`, `batchload_relations`, `transactional_mutations`, `force_fill`, tracing.

---

## Changes Applied

### 1. Schema: `status` as Enum

**Before:** `status: String!`  
**After:** `status: SearchUsersStatus!` with `enum SearchUsersStatus { USERS_FOUND, NO_RESULTS_FOUND }`

**Why:** Enums are preferred for fixed value sets: better validation, docs, and client ergonomics.

### 2. Schema: Explicit `phoneVerifiedAt` → `phone_verified_at`

**Before:** `phoneVerifiedAt: DateTime` (implicit model attribute).  
**After:** `phoneVerifiedAt: DateTime @rename(attribute: "phone_verified_at")`

**Why:** Clear mapping from GraphQL camelCase to Eloquent snake_case; avoids reliance on default resolution.

### 3. Security: Query Complexity & Depth

**Before:** `max_query_complexity` and `max_query_depth` set to `DISABLED` (0).  
**After:** Driven by env with default 0:

- `LIGHTHOUSE_MAX_QUERY_COMPLEXITY` (default `0` = disabled). Set to e.g. `1000` in production to limit complexity.
- `LIGHTHOUSE_MAX_QUERY_DEPTH` (default `0` = disabled). Set to e.g. `15` in production to limit depth.

**Why:** Reduces risk of DoS via very complex or deeply nested queries. `0` keeps prior behavior; production can opt in.

---

## Recommendations (Not Implemented)

### Pagination Arguments

- **Current:** `perPage` is fixed from config; no `page`/`perPage` (or `first`/`after`) on `searchUsers`.
- **Suggestion:** Add optional `page: Int` and `perPage: Int` (with a cap, e.g. max 50) and pass through to the search service for client-controlled pagination. Align with REST if desired.

### Relay-Style Connections (Optional)

- **Current:** Offset-style `data` + `meta` (currentPage, perPage, total, lastPage).
- **Suggestion:** If you standardise on Relay, introduce `searchUsers(…): UserConnection` with `edges`, `pageInfo`, and cursor-based `first`/`after`. Your current design is valid and consistent with REST.

### Argument Naming

- **Current:** `q` for the search term.
- **Suggestion:** `searchTerm` or `query` is more self-explanatory; `q` is acceptable and documented.

### Introspection

- **Current:** `LIGHTHOUSE_SECURITY_DISABLE_INTROSPECTION` can disable in production.
- **Suggestion:** Disable introspection in production to reduce information disclosure.

### Schema Modularisation

- **Current:** Single `graphql/schema.graphql`.
- **Suggestion:** As the schema grows, split (e.g. `types.graphql`, `queries.graphql`) and `# import` in the main file.

---

## Error Format (GraphQL)

Errors follow the usual structure:

```json
{
  "errors": [
    {
      "message": "Authentication required. Please provide a valid token.",
      "extensions": { "code": "UNAUTHORIZED" }
    }
  ]
}
```

- `message`: Human-readable, no sensitive data.
- `extensions.code`: Machine-readable (`UNAUTHORIZED`, `FORBIDDEN`, `VALIDATION_ERROR`, `SEARCH_FAILED`).
- Validation errors also use Lighthouse’s `validation` structure.

---

## Resolver and Type Resolution

- **SearchUsers**: Resolves `searchUsers` → `App\GraphQL\Queries\SearchUsers`. Returns `status`, `message`, `data`, `meta`; `data` is `[User]`; `meta` matches `SearchUsersPaginationMeta`. `status` is the enum value name (`USERS_FOUND` / `NO_RESULTS_FOUND`).
- **User**: Resolved from Eloquent `App\Models\User`; `id`, `name`, `username` map directly; `phoneVerifiedAt` via `@rename(attribute: "phone_verified_at")` and the model’s `phone_verified_at` accessor.

---

## Validation

- `php artisan lighthouse:validate-schema` passes.
- `php artisan test` passes.
