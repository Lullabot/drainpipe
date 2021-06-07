# Drainpipe

## Dependencies
This project requires [Task](https://github.com/go-task/task), [version 3 or later](https://taskfile.dev/#/taskfile_versions) to be installed.

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
