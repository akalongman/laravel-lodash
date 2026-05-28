# Upgrade laravel-lodash to Laravel 13

## Problem

The laravel-lodash package is a utility layer that sits on top of the Laravel framework. It currently only supports Laravel 12. Anyone running (or planning to move to) Laravel 13 cannot use this package on their up-to-date application, which means our package risks looking outdated, blocks teams from adopting the newest framework version, and silently accumulates technical debt as the package's other supporting libraries (test tooling, AWS, Elasticsearch, etc.) also fall behind.

## Solution

We are publishing a new release of laravel-lodash that targets Laravel 13. As part of the same release we are updating the supporting libraries the package uses internally to their current versions, refreshing the automated test pipeline that runs on every change, and writing down the rules we use for handling dependency upgrades so that the next time we do this (Laravel 14, Laravel 15, and so on) the process is faster and more predictable. Teams already on Laravel 12 can keep using the previous release of laravel-lodash; they only need to move once they upgrade their own application to Laravel 13.

## Business Outcome

Teams running Laravel 13 will be able to install laravel-lodash again. The package stays current and credible as a maintained dependency. Future framework upgrades become routine rather than disruptive because we now have written-down rules that explain how to do them. The maintenance team spends less time figuring out the upgrade playbook each time.

## Risks & Timing

This is a small-to-medium effort upgrade. The main risks are: (1) some of our supporting libraries may not yet have a Laravel-13-compatible release, in which case we hold them at the latest version we can and revisit later; (2) tightening the framework version means anyone still on Laravel 12 must stay on the prior release of laravel-lodash until they upgrade their app, which we will call out clearly in the release notes; (3) Laravel 13 may have changed small internal contracts that our package extends, so we need a focused review of the parts of laravel-lodash that wrap Laravel's caching, Redis, queue, and Elasticsearch features. None of these are blockers, and the work can ship as a single release.
