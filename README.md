# Drainpipe
## Dependencies
This project comes with the linux binary for [Task v3.4.3](https://github.com/go-task/task/releases/tag/v3.4.3).

By adding additional task binaries in folders and naming them based on what `php_uname('s')` returns can enable
additional OS support.
## Setup
Copy Taskfile.yml to the root directory of your project and modify it as needed for your project.
## Usage
Below are some examples based on task files included in this package for certain project types.
### Drupal
```
task drupal:update
```

or for a remote site:

```
task drupal:update -- @mysite.dev
```
