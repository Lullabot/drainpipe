# Drainpipe

## Dependencies
This project requires [Task v3.4.3](https://github.com/go-task/task/releases/tag/v3.4.3) be installed.
Other versions may be acceptable but this is the version the example Taskfile.yml has been tested with.

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
