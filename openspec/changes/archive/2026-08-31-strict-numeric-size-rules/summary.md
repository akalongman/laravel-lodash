# Number limits on strictly typed fields are enforced

## Problem

This package lets an application insist that an incoming value is genuinely a
number, not text that merely looks like one. Applications combine that check
with limits: a quantity must be at least 1, an identifier must fit in the
database column that stores it, a score must fall between 1 and 10.

Today those limits are not enforced on such fields. The framework, when asked
"is this value at most 32767?", does not recognize the package's type check as
proof that it is dealing with a number, so it falls back to measuring the value
the way it measures text: by counting characters. The number 2147483648 has ten
characters, ten is less than 32767, and the value sails through. The same
happens for lower bounds and ranges. A rule that reads as a clear limit in the
application's code is, in practice, doing nothing, and nothing reports that.

This came to light as a security fix in a downstream application, where a value
larger than its database column allowed was accepted by validation and rejected
only when the database refused to store it. The workaround the application had
to adopt, declaring the field as a number twice using two different rules, is
the kind of thing nobody remembers to do on the next field.

## Solution

When a field is declared as strictly a number, every limit rule on that field
will treat it as a number: too large is rejected, too small is rejected, out of
range is rejected, and the error message says "must not be greater than 32767"
rather than talking about characters. No second rule is needed.

Fields declared as strictly text, strictly true-or-false, or with no strict
declaration at all behave exactly as before. Applications that already applied
the double-declaration workaround see no change either.

The package's documentation gains a section on the strict rule, which it did
not have, and the release notes spell out what changes and for whom.

## Business Outcome

Limits written in validation rules on strictly typed numeric fields are
enforced. Out-of-range values are refused at the front door with a clear
message, instead of being accepted and failing later, or worse, being stored.
One class of silent data-integrity gap is closed for every application on this
package, and the workaround that had to be applied field by field becomes
unnecessary.

There is no metric to move, because the old behavior produced no error signal:
the values were simply accepted.

## Risks & Timing

Small effort. The fix is a handful of lines in one class; most of the work is
the tests that prove it, the documentation, and the release notes.

This is released as a new major version (16.0.0) because it is, deliberately, a
behavior change: requests that were being accepted with out-of-range numbers
will start being rejected. That is the point, but any application that has
grown to depend on the old leniency, or has automated tests that assert it,
will see failures on upgrade. The release notes call this out and say what to
correct. The one known case, a downstream application with a test that
deliberately asserts the old behavior, is expected to fail and is being handled
on that application's side.

No data migration, no downtime, and reverting is trivial if anything unexpected
surfaces.
