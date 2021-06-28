# Drainpipe

## Dependencies
This project requires [Task](https://github.com/go-task/task), [version 3 or later](https://taskfile.dev/#/taskfile_versions) to be installed.

## Setup
An initial `Taskfile.yml` will be created when installing this package. Be sure to commit it to version control. To simplify future updates, consider including additional Taskfiles instead of adding commands to the file directly.

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

## Validation

Your `Taskfile.yml` can be validated with JSON Schema:
```
curl -O https://json.schemastore.org/taskfile.json
npx ajv-cli validate -s taskfile.json -d Taskfile.yml
```

See [.github/workflows/validate-taskfile.yml](`.github/workflows/validate-taskfile.yml`)
for an example of this in use.