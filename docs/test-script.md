# Test Script

A test script is available in `tests/local-test.sh` to setup a Drupal site
with Drainpipe packages installed from your local checkout.

Go to the directory above `drainpipe` and run the `tests/local-test.sh` script
to setup a new test site e.g.
```
❯ pwd
/home/justafish/repos/drainpipe

❯ cd ../
```

If you previously created a test site, remove it:
```
❯ cd drainpipe-test
❯ ddev stop --remove-data --omit-snapshot
❯ cd ../
```

Run the test script:
```
❯ pwd
/home/justafish/repos
❯ ./drainpipe/tests/local-test.sh
cd drainpipe-test
```
