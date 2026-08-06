# Include paths compose as a set, not a sequence

## Problem

When an application asks our API for a record, it can ask for related records to
come along with it. A course, for example, can be requested together with its
grading criteria, and each criterion together with the course component it
belongs to.

Applications almost always combine two lists to build that request: a set of
related records they always want, and whatever the person using the app asked
for on top. If those two lists overlap, and the overlapping entry lands in the
wrong position, the more detailed request is thrown away and the related data
silently disappears from the response. Nothing errors. Nothing is logged. The
response simply arrives with less data than it should have.

The failure is inverted in the worst possible way: asking for *more* related data
returns *less*. An application that already received the course components by
default, and then explicitly asked for the criteria too, stops receiving the
components. Every version of this package has behaved this way. A change in the
previous release did not cause it, but it did strip away the camouflage that had
been hiding it, which is how it came to light.

## Solution

The request for related records will be treated as a set of wishes rather than an
ordered list of instructions. Asking for a general thing and a specific thing
within it, in either order, will return both. Asking for the same thing twice will
be harmless. Combining a default list with a user-supplied list will be safe no
matter which comes first.

The behaviour that already works is untouched: which related records appear, how
an empty one is represented, and the loud error when an application asks for
something that does not exist all stay exactly as they are.

## Business Outcome

Responses stop losing related data. Applications built on this package will
receive the nested records they asked for, which removes a class of bug that is
very hard to notice from the outside, because the response looks structurally
valid and merely happens to be missing content.

The observable improvement: for any request that combines default related records
with user-selected ones, the response now contains everything requested rather
than an unpredictable subset determined by list order. There is no metric to move,
because the defect produced no error signal to measure.

## Risks & Timing

Small effort. The fix itself is a few lines in one internal function; most of the
work is the tests that prove it and the release notes that explain it.

The one thing to expect: some consuming applications have automated tests that
were passing only because the data was missing. Those tests will start failing on
upgrade, correctly, because the data is now present. The release notes will call
this out and say how to update them. No data migration, no downtime, and reverting
is trivial if anything unexpected surfaces.

The change ships as a minor version rather than a bug-fix version. Nothing about
it is breaking, but a minor bump makes upgraders more likely to read the notes
before they upgrade, which is the point.
