# Testing structure assertions

## Problem

The package's test helper that checks API list responses only inspects the first item in the list. If the second or hundredth item is malformed, the test still passes, giving teams false confidence that their API responses are well-formed. The same first-item-only blind spot exists inside nested related data (for example, a student group's list of programs), and combining certain test declarations can silently discard part of what a test meant to check. On top of that, the helpers can only check that expected fields are present; they cannot detect fields that should not be there, so an API response can silently grow unintended extra data (a common source of accidental data exposure and contract drift) without any test noticing. Finally, tests that want to prove "this endpoint returned exactly these records" have to hand-write that check every time, so most tests settle for weaker "returned five things" counting, which misses bugs where the right number of wrong records comes back.

## Solution

Four improvements to the testing helpers. First, list checks now inspect every item in the list, including nested related data, and malformed responses fail with clear, readable messages. Second, the way tests declare which related data to check gets a cleaner, standard notation that matches how the framework itself is used day to day: declarations can be combined freely without discarding each other, and mistakes that previously corrupted a check silently now stop the test with an explanation of how to fix the declaration. Third, an opt-in "exact" mode lets a test declare the complete expected shape of the response data: any missing field or unexpected extra field fails the test, while the surrounding response envelope keeps working as before. Fourth, a new one-line assertion ties a response to the exact records it should contain: it checks the response shape, the declared record type, and the specific record identities in one call, with exactness on by default.

## Business Outcome

Consumers of the package catch malformed responses, drifting response shapes, and wrong-record bugs (for example, data leaking across scopes such as semesters or tenants) in their test suites instead of in production or in partner integrations. The new one-line record assertion turns weak "how many rows" tests into strong "exactly which rows" tests at no extra effort per test.

## Risks & Timing

Small-to-medium effort (two classes, two new test files, package documentation, a changelog entry). The stricter checks may cause some existing downstream tests to newly fail; those failures reveal real, previously hidden response defects, and the change ships inside the already-planned major release where consumers are updating their suites anyway. Exact mode is opt-in on the existing helpers; the new record assertions default to strict but are new, so nothing changes for teams until they adopt them. One legacy declaration form requires a small mechanical rewrite; affected tests stop with a clear fix-it message rather than passing incorrectly.
