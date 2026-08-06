# Show empty related data instead of hiding it

## Problem

When an app asks our API for a piece of related information (a user's avatar, for example) and that information does not exist, the API leaves it out of the response entirely rather than saying "there is none".

Silence is ambiguous. The receiving app cannot tell the difference between four very different situations: the user genuinely has no avatar, the app asked for something the API does not recognise, the API decided not to send it, or a bug lost it along the way. All four look exactly the same on the wire.

The practical cost is that client apps write defensive guesswork, and bugs in this area are hard to spot. One team's written specification has stated for some time that this field must come back as empty rather than missing. It never has. The test meant to catch that could not tell the two apart either, so the gap went unnoticed for several releases.

The issue is widespread but easy to miss, because it only affects one of the two ways our developers write this kind of code. The other way already behaves correctly. In one project we counted 90 places written the affected way, across 38 different response types.

## Solution

When an app asks for related information and there is none, the API will say so explicitly instead of staying silent. The field comes back marked as empty rather than being dropped.

Information the app did not ask for is still left out, so responses do not get larger. Only the thing that was explicitly requested is answered, one way or the other.

We are also updating our own testing tools so teams can write a test that says "this field may legitimately be empty". Today they cannot express that, which is a large part of why the original problem survived so long.

Alongside the code, we are updating the changelog and the README so the new behaviour is documented rather than discovered.

## Business Outcome

Client applications can trust the API's answer instead of guessing. "There is no avatar" becomes something the API can actually say, which removes a class of defensive code and a class of silent bugs.

The measurable improvement is that teams can finally write tests that prove this behaviour. The two tests currently switched off in the consuming project because they cannot be expressed become the proof that this works, and future regressions fail the build instead of shipping quietly.

## Risks & Timing

**This is a breaking change**, so it ships as a new major version. Nothing is removed and no existing information changes meaning, but responses gain a field that used to be absent, and any app that checks for the field's absence will behave differently.

The larger migration cost is on the testing side rather than the client side. Teams using our test helpers will need a small, mechanical edit wherever they describe a field that can be empty. In the project we measured, that is the bulk of the work, not the code change itself.

Effort is **medium**. The change to the API response itself is very small. Most of the work is updating our testing tools so the new behaviour can be tested at all, plus the tests and documentation around both.

There is no migration window or downtime, since this is a library that teams adopt when they choose to upgrade. Teams that need the fix urgently have a way to get the correct behaviour today, before this release, by writing the affected code the other way. That approach stays correct after the upgrade, so it is not throwaway work.
