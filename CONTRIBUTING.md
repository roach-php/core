# Contributing to Roach

This is the Contribution Guide for Roach PHP. Please read this document
carefully before opening an issue or a pull request.

## Code of Conduct

Before contributing to the project, please read our 
[Code of Conduct](./CODE_OF_CONDUCT.md).

## Reporting a bug

Before you submit an issue, please search [the issue tracker][issues]. An issue
for your problem might already exist and the discussion might inform you of
workarounds readily available.

You can file new issues by [selecting an issue template][new-issue] and filling
out the necessary information.

## Proposing a Change

If you intend to change the public API or make any non-trivial changes to the
implementation, make sure to [create an issue][new-feature] first. This will let 
us discuss a proposal before you put significant effort into it.

If you're only fixing a bug or a typo, it's fine to submit a pull request right
away without creating an issue, but make sure it contains a clear and concise
description of the bug.

## Working on Issues

Before you start working on an issue make sure that it has been accepted
(indicated by an [`accepted`][label-accepted] label) and that no one has
claimed it yet. Otherwise, you may duplicate other people's efforts. If somebody
claims an issue but doesn't follow up for more than two weeks, it’s fine to take
it over, but you should still leave a comment. You should also leave a comment
on any issue you're working on, to let others know.

## Semantic Versioning

Roach follows [semantic versioning][semver].

## Making a Pull Request

1. Fork the roach-php/core repo.
2. In your forked repo, create a new branch for your changes:
   ```shell
   git checkout -b my-fix-branch main
   ```
3. Update the code. **Make sure that all your changes are covered by tests.**
4. Commit your changes using a **descriptive commit message** that follows the
   [Angular Commit Message Conventions][commit-format].
   ```shell
   git commit --all
   ```
5. Push your branch to GitHub:
   ```shell
   git push origin my-fix-branch
   ```
6. In GitHub, send a pull request to [the main branch][main].

### Addressing review feedback

1. Make required updates to the code.
2. Create a fixup commit and push it to your GitHub repo:
   ```shell
   git commit --all --fixup HEAD
   git push
   ```

## Attribution

This Contribution Guide was adapted from the [Motion Canvas][motion-canvas] 
Contribution guide

[semver]: https://semver.org/
[semantic-release]: https://semantic-release.gitbook.io/semantic-release/support/faq#can-i-set-the-initial-release-version-of-my-package-to-0.0.1
[main]: https://github.com/roach-php/core/tree/main
[issues]: https://github.com/roach-php/core/issues
[new-issue]: https://github.com/roach-php/core/issues/new/choose
[new-feature]: https://github.com/roach-php/core/issues/new?template=feature_request.md
[commit-format]: https://github.com/angular/angular/blob/main/CONTRIBUTING.md#commit
[motion-canvas]: https://github.com/motion-canvas/motion-canvas/blob/main/CONTRIBUTING.md
[label-accepted]: https://github.com/roach-php/core/labels/accepted